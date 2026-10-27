<?php

use PSource\EInvoice\Amount;
use PSource\EInvoice\Calculator;
use PSource\EInvoice\InvoiceSnapshot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPsCRM_EInvoice_CRM_Adapter {

	private $wpdb;

	public function __construct( $wpdb = null ) {
		if ( null === $wpdb ) {
			global $wpdb;
		}

		if ( ! is_object( $wpdb ) ) {
			throw new InvalidArgumentException( 'A WordPress database connection is required.' );
		}

		$this->wpdb = $wpdb;
	}

	public function create_snapshot( $document_id ) {
		$document_id = (int) $document_id;
		$document = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT * FROM ' . WPsCRM_TABLE . 'dokumente WHERE id = %d',
				$document_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			throw new RuntimeException( 'CRM invoice not found.' );
		}
		if ( 2 !== (int) $document['tipo'] ) {
			throw new InvalidArgumentException( 'Only CRM invoices can be exported as E-invoices.' );
		}
		if ( ! Amount::from_string( self::money( $document['tot_cassa_inps'] ) )->equals( Amount::zero() ) ) {
			throw new RuntimeException( 'CRM invoices with pension-fund surcharges are not supported yet.' );
		}
		if ( ! Amount::from_string( self::money( $document['ritenuta_acconto'] ) )->equals( Amount::zero() ) ) {
			throw new RuntimeException( 'CRM invoices with withholding tax are not supported yet.' );
		}

		$customer = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT * FROM ' . WPsCRM_TABLE . 'kunde WHERE ID_kunde = %d',
				(int) $document['fk_kunde']
			),
			ARRAY_A
		);
		if ( ! $customer ) {
			throw new RuntimeException( 'CRM invoice customer not found.' );
		}

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM ' . WPsCRM_TABLE . 'dokumente_dettaglio WHERE fk_dokumente = %d AND eliminato = 0 ORDER BY n_riga, id',
				$document_id
			),
			ARRAY_A
		);

		$business = get_option( 'CRM_business_settings', array() );
		$document_settings = get_option( 'CRM_documents_settings', array() );
		$metadata = ( new WPsCRM_EInvoice_Document_Metadata_Repository( $this->wpdb ) )->get( $document_id );
		$customer_data = ( new WPsCRM_EInvoice_Customer_Data_Repository( $this->wpdb ) )->get( (int) $document['fk_kunde'] );
		$lines = array();
		$taxable_by_key = array();

		foreach ( $rows as $row ) {
			if ( 3 === (int) $row['tipo'] ) {
				continue;
			}
			if ( 4 === (int) $row['tipo'] ) {
				throw new RuntimeException( 'Legacy refund lines must be converted to a credit note before E-invoice export.' );
			}

			$rate = WPsCRM_EInvoice_Line_Classification::is_small_business()
				? '0'
				: self::decimal( $row['iva'] );
			$category = WPsCRM_EInvoice_Line_Classification::resolve_tax_category(
				$row['tax_category'] ?? '',
				$rate
			);
			$exemption_reason = WPsCRM_EInvoice_Line_Classification::exemption_reason( $category );
			$discount_type = ! empty( $document['tipo_sconto'] ) ? 'fixed' : 'percentage';
			$net_amount = Calculator::line_net_amount(
				self::money( $row['prezzo'] ),
				self::decimal( $row['qta'] ),
				self::money( $row['sconto'] ),
				$discount_type
			);

			$line = array(
				'id' => (string) $row['n_riga'],
				'name' => trim( wp_strip_all_tags( (string) $row['descrizione'] ) ),
				'quantity' => self::decimal( $row['qta'] ),
				'unit_code' => WPsCRM_EInvoice_Line_Classification::normalize_unit_code( $row['unit_code'] ?? 'C62' ),
				'unit_price' => self::money( $row['prezzo'] ),
				'net_amount' => $net_amount,
				'tax_category' => $category,
				'tax_rate' => $rate,
				'tax_exemption_reason' => $exemption_reason,
			);

			if ( Amount::from_string( self::money( $row['sconto'] ) )->to_minor_units() > 0 ) {
				$base = Calculator::line_net_amount(
					self::money( $row['prezzo'] ),
					self::decimal( $row['qta'] )
				);
				$line['allowances_charges'] = array(
					array(
						'is_charge' => false,
						'amount' => $base->subtract( $net_amount ),
						'base_amount' => $base,
						'reason' => 'Positionsrabatt',
						'tax_category' => $category,
						'tax_rate' => $rate,
					),
				);
			}

			$lines[] = $line;
			$key = $category . '|' . $rate;
			$taxable_by_key[ $key ] = isset( $taxable_by_key[ $key ] )
				? $taxable_by_key[ $key ]->add( $net_amount )
				: $net_amount;
		}

		$line_total = Amount::zero();
		$tax_total = Amount::zero();
		$tax_breakdowns = array();
		foreach ( $taxable_by_key as $key => $taxable_amount ) {
			list( $category, $rate ) = explode( '|', $key, 2 );
			$tax_amount = Calculator::tax_amount( $taxable_amount, $rate );
			$line_total = $line_total->add( $taxable_amount );
			$tax_total = $tax_total->add( $tax_amount );
			$tax_breakdowns[] = array(
				'category' => $category,
				'rate' => $rate,
				'taxable_amount' => $taxable_amount,
				'tax_amount' => $tax_amount,
				'exemption_reason' => WPsCRM_EInvoice_Line_Classification::exemption_reason( $category ),
			);
		}

		$gross_total = $line_total->add( $tax_total );
		self::assert_amount( $document['totale_imponibile'], $line_total, 'CRM invoice net total differs from its positions.' );
		self::assert_amount( $document['totale_imposta'], $tax_total, 'CRM invoice tax total differs from its tax breakdown.' );
		self::assert_amount( $document['totale'], $gross_total, 'CRM invoice gross total differs from its positions.' );
		$invoice_number = trim( (string) ( $document_settings['invoices_prefix'] ?? '' ) )
			. (string) $document['progressivo']
			. trim( (string) ( $document_settings['invoices_suffix'] ?? '' ) );

		$data = array(
			'invoice_number' => $invoice_number,
			'type_code' => '380',
			'issue_date' => $document['data'],
			'due_date' => '0000-00-00' === $document['data_scadenza'] ? '' : $document['data_scadenza'],
			'currency' => 'EUR',
			'buyer_reference' => '' !== $metadata['buyer_reference']
				? $metadata['buyer_reference']
				: $customer_data['buyer_reference'],
			'service_start_date' => $metadata['service_start_date'] ?: '',
			'service_end_date' => $metadata['service_end_date'] ?: '',
			'seller' => self::seller( $business ),
			'buyer' => self::buyer( $customer, $customer_data ),
			'lines' => $lines,
			'tax_breakdowns' => $tax_breakdowns,
			'totals' => array(
				'line_net_amount' => $line_total,
				'tax_exclusive_amount' => $line_total,
				'tax_amount' => $tax_total,
				'tax_inclusive_amount' => $gross_total,
				'payable_amount' => $gross_total,
			),
			'payment_means_code' => empty( $business['business_iban'] ) ? '30' : '58',
			'payment_reference' => $invoice_number,
			'payment_terms' => trim( (string) $document['modalita_pagamento'] ),
			'note' => '',
			'iban' => trim( (string) ( $business['business_iban'] ?? '' ) ),
			'bic' => trim( (string) ( $business['business_swift'] ?? '' ) ),
			'references' => self::references( $metadata, $document ),
			'attachments' => empty( $document['filename'] ) ? array() : array(
				array(
					'id' => 'crm-pdf',
					'filename' => basename( (string) $document['filename'] ),
					'mime_type' => 'application/pdf',
				),
			),
			'source' => array(
				'system' => 'crm',
				'invoice_id' => (string) $document_id,
				'customer_id' => (string) $document['fk_kunde'],
				'customer_type' => '' !== $customer_data['customer_type']
					? $customer_data['customer_type']
					: ( 2 === (int) $customer['tipo_cliente'] ? 'b2b' : 'b2c' ),
			),
		);

		return new InvoiceSnapshot( $data );
	}

	private static function references( array $metadata, array $document ) {
		$references = array();
		foreach (
			array(
				'order_reference' => 'order',
				'contract_reference' => 'contract',
				'project_reference' => 'project',
			) as $field => $type
		) {
			if ( '' !== (string) $metadata[ $field ] ) {
				$references[] = array(
					'type' => $type,
					'id' => (string) $metadata[ $field ],
				);
			}
		}
		if ( empty( $references ) && ! empty( $document['riferimento'] ) ) {
			$references[] = array(
				'type' => 'other',
				'id' => trim( (string) $document['riferimento'] ),
			);
		}

		return $references;
	}

	private static function seller( array $business ) {
		return array(
			'name' => trim( (string) ( $business['business_name'] ?? '' ) ),
			'address_line' => trim( (string) ( $business['business_address'] ?? '' ) . ' ' . (string) ( $business['business_number'] ?? '' ) ),
			'postal_code' => trim( (string) ( $business['business_zip'] ?? '' ) ),
			'city' => trim( (string) ( $business['business_town'] ?? '' ) ),
			'region' => trim( (string) ( $business['crm_business_provinz'] ?? '' ) ),
			'country_code' => strtoupper( trim( (string) ( $business['business_country'] ?? '' ) ) ),
			'vat_id' => trim( (string) ( $business['business_ustid'] ?? '' ) ),
			'tax_id' => trim( (string) ( $business['business_taxid'] ?? '' ) ),
			'email' => trim( (string) ( $business['business_email'] ?? '' ) ),
			'electronic_address' => trim( (string) ( $business['business_email'] ?? '' ) ),
			'electronic_address_scheme' => empty( $business['business_email'] ) ? '' : 'EM',
			'contact_name' => trim( (string) ( $business['business_contact'] ?? $business['business_name'] ?? '' ) ),
			'contact_phone' => trim( (string) ( $business['business_phone'] ?? '' ) ),
			'contact_email' => trim( (string) ( $business['business_email'] ?? '' ) ),
		);
	}

	private static function buyer( array $customer, array $customer_data ) {
		$name = trim( (string) $customer['firmenname'] );
		if ( '' === $name ) {
			$name = trim( (string) $customer['name'] . ' ' . (string) $customer['nachname'] );
		}
		$electronic_address = trim( (string) $customer_data['electronic_address'] );
		$electronic_address_scheme = trim( (string) $customer_data['electronic_address_scheme'] );
		if ( '' === $electronic_address ) {
			$electronic_address = trim( (string) $customer['email'] );
			$electronic_address_scheme = '' === $electronic_address ? '' : 'EM';
		}
		$contact_name = trim( (string) $customer_data['contact_name'] );
		if ( '' === $contact_name ) {
			$contact_name = trim( (string) $customer['name'] . ' ' . (string) $customer['nachname'] );
		}

		return array(
			'name' => $name,
			'address_line' => trim( (string) $customer['adresse'] ),
			'postal_code' => trim( (string) $customer['cap'] ),
			'city' => trim( (string) $customer['standort'] ),
			'region' => trim( (string) $customer['provinz'] ),
			'country_code' => strtoupper( trim( (string) $customer['nation'] ) ),
			'vat_id' => trim( (string) $customer['p_iva'] ),
			'tax_id' => trim( (string) $customer['cod_fis'] ),
			'email' => trim( (string) $customer['email'] ),
			'electronic_address' => $electronic_address,
			'electronic_address_scheme' => $electronic_address_scheme,
			'contact_name' => $contact_name,
			'contact_phone' => trim( (string) ( $customer['telefono1'] ?? '' ) ),
			'contact_email' => trim( (string) $customer['email'] ),
		);
	}

	private static function money( $value ) {
		return self::normalize_database_decimal( $value, 2 );
	}

	private static function decimal( $value ) {
		$value = self::normalize_database_decimal( $value, 4 );
		$value = rtrim( rtrim( $value, '0' ), '.' );
		return '' === $value || '-' === $value ? '0' : $value;
	}

	private static function normalize_database_decimal( $value, $precision ) {
		$value = str_replace( ',', '.', trim( (string) $value ) );
		if ( ! preg_match( '/^-?\d+(?:\.\d+)?$/', $value ) ) {
			throw new InvalidArgumentException( 'Legacy invoice value is not a decimal number.' );
		}

		list( $whole, $fraction ) = array_pad( explode( '.', $value, 2 ), 2, '' );
		if ( strlen( $fraction ) > $precision && '' !== trim( substr( $fraction, $precision ), '0' ) ) {
			throw new InvalidArgumentException( 'Legacy invoice value exceeds the supported decimal precision.' );
		}

		return $whole . '.' . str_pad( substr( $fraction, 0, $precision ), $precision, '0' );
	}

	private static function assert_amount( $legacy_value, Amount $expected, $message ) {
		if ( ! Amount::from_string( self::money( $legacy_value ) )->equals( $expected ) ) {
			throw new RuntimeException( $message );
		}
	}
}
