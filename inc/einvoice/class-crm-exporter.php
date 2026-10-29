<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPsCRM_EInvoice_CRM_Exporter {

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

	public function generate( $document_id ) {
		$document = $this->get_invoice( $document_id );
		$customer = $this->get_customer( $document['fk_kunde'] );
		$rows = $this->get_invoice_rows( $document_id );
		$business = get_option( 'CRM_business_settings', array() );
		$settings = get_option( 'CRM_documents_settings', array() );
		$metadata = ( new WPsCRM_EInvoice_Document_Metadata_Repository( $this->wpdb ) )->get( $document_id );

		if ( false === $metadata['enabled'] ) {
			throw new RuntimeException( 'The XML export is disabled for this invoice.' );
		}

		$document_number = trim( (string) ( $settings['invoices_prefix'] ?? '' ) )
			. (string) $document['progressivo']
			. trim( (string) ( $settings['invoices_suffix'] ?? '' ) );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<B2BInvoice xmlns="https://psource.eimen.net/schema/b2b-invoice/1.0" version="1.0">';
		$xml .= '<Header>'
			. self::element( 'InvoiceNumber', $document_number )
			. self::element( 'IssueDate', $document['data'] )
			. self::optional_element( 'DueDate', $document['data_scadenza'] )
			. self::element( 'Currency', 'EUR' )
			. self::optional_element( 'Reference', $document['riferimento'] )
			. '</Header>';
		$xml .= '<Parties>';
		$xml .= self::party( 'Seller', array(
			'name' => $business['business_name'] ?? '',
			'address' => trim( (string) ( $business['business_address'] ?? '' ) . ' ' . (string) ( $business['business_number'] ?? '' ) ),
			'postal_code' => $business['business_zip'] ?? '',
			'city' => $business['business_town'] ?? '',
			'country' => $business['business_country'] ?? '',
			'vat_id' => $business['business_ustid'] ?? '',
			'tax_id' => $business['business_taxid'] ?? '',
			'email' => $business['business_email'] ?? '',
		) );
		$xml .= self::party( 'Buyer', array(
			'name' => $this->customer_name( $customer ),
			'address' => $customer['adresse'] ?? '',
			'postal_code' => $customer['cap'] ?? '',
			'city' => $customer['standort'] ?? '',
			'country' => $customer['nation'] ?? '',
			'vat_id' => $customer['p_iva'] ?? '',
			'tax_id' => $customer['cod_fis'] ?? '',
			'email' => $customer['email'] ?? '',
		) );
		$xml .= '</Parties><Lines>';
		foreach ( $rows as $row ) {
			if ( 3 === (int) $row['tipo'] ) {
				continue;
			}

			$xml .= '<Line number="' . self::escape( $row['n_riga'] ) . '">'
				. self::element( 'Description', wp_strip_all_tags( (string) $row['descrizione'] ) )
				. self::element( 'Quantity', self::decimal( $row['qta'], 4 ) )
				. self::element( 'UnitPrice', self::decimal( $row['prezzo'], 2 ) )
				. self::element( 'Discount', self::decimal( $row['sconto'], 2 ) )
				. self::element( 'TaxRate', self::decimal( $row['iva'], 2 ) )
				. self::element( 'NetAmount', self::decimal( $row['totale'], 2 ) )
				. '</Line>';
		}
		$xml .= '</Lines><Totals>'
			. self::element( 'NetAmount', self::decimal( $document['totale_imponibile'], 2 ) )
			. self::element( 'TaxAmount', self::decimal( $document['totale_imposta'], 2 ) )
			. self::element( 'GrossAmount', self::decimal( $document['totale'], 2 ) )
			. '</Totals><Payment>'
			. self::optional_element( 'Terms', $document['modalita_pagamento'] )
			. self::optional_element( 'IBAN', $business['business_iban'] ?? '' )
			. self::optional_element( 'BIC', $business['business_swift'] ?? '' )
			. '</Payment><References>'
			. self::optional_element( 'Order', $metadata['order_reference'] )
			. self::optional_element( 'Contract', $metadata['contract_reference'] )
			. self::optional_element( 'Project', $metadata['project_reference'] )
			. '</References></B2BInvoice>';

		return $xml;
	}

	private function get_invoice( $document_id ) {
		$document = $this->wpdb->get_row(
			$this->wpdb->prepare( 'SELECT * FROM ' . WPsCRM_TABLE . 'dokumente WHERE id = %d', (int) $document_id ),
			ARRAY_A
		);
		if ( ! $document || 2 !== (int) $document['tipo'] ) {
			throw new RuntimeException( 'CRM invoice not found.' );
		}

		return $document;
	}

	private function get_customer( $customer_id ) {
		$customer = $this->wpdb->get_row(
			$this->wpdb->prepare( 'SELECT * FROM ' . WPsCRM_TABLE . 'kunde WHERE ID_kunde = %d', (int) $customer_id ),
			ARRAY_A
		);
		if ( ! $customer ) {
			throw new RuntimeException( 'CRM invoice customer not found.' );
		}

		return $customer;
	}

	private function get_invoice_rows( $document_id ) {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM ' . WPsCRM_TABLE . 'dokumente_dettaglio WHERE fk_dokumente = %d AND eliminato = 0 ORDER BY n_riga, id',
				(int) $document_id
			),
			ARRAY_A
		);
	}

	private static function party( $name, array $party ) {
		return '<' . $name . '>'
			. self::element( 'Name', $party['name'] )
			. self::optional_element( 'Address', $party['address'] )
			. self::optional_element( 'PostalCode', $party['postal_code'] )
			. self::optional_element( 'City', $party['city'] )
			. self::optional_element( 'Country', $party['country'] )
			. self::optional_element( 'VATId', $party['vat_id'] )
			. self::optional_element( 'TaxId', $party['tax_id'] )
			. self::optional_element( 'Email', $party['email'] )
			. '</' . $name . '>';
	}

	private static function element( $name, $value ) {
		return '<' . $name . '>' . self::escape( $value ) . '</' . $name . '>';
	}

	private static function optional_element( $name, $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value && '0000-00-00' !== $value ) {
			return self::element( $name, $value );
		}

		return '';
	}

	private static function escape( $value ) {
		return htmlspecialchars( (string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8' );
	}

	private function customer_name( array $customer ) {
		$name = trim( (string) ( $customer['firmenname'] ?? '' ) );
		if ( '' === $name ) {
			$name = trim( (string) ( $customer['name'] ?? '' ) . ' ' . (string) ( $customer['nachname'] ?? '' ) );
		}

		return $name;
	}

	private static function decimal( $value, $precision ) {
		$value = str_replace( ',', '.', trim( (string) $value ) );
		if ( ! preg_match( '/^-?\d+(?:\.\d+)?$/', $value ) ) {
			throw new InvalidArgumentException( 'Invoice amounts must be decimal numbers.' );
		}

		return number_format( (float) $value, $precision, '.', '' );
	}
}

function WPsCRM_download_einvoice() {
	if ( ! current_user_can( 'manage_crm' ) ) {
		wp_die( esc_html__( 'Du hast keine Berechtigung, Rechnungs-XML herunterzuladen.', 'cpsmartcrm' ), '', array( 'response' => 403 ) );
	}

	$document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;
	check_admin_referer( 'wpscrm_download_einvoice_' . $document_id );

	try {
		$xml = ( new WPsCRM_EInvoice_CRM_Exporter() )->generate( $document_id );
	} catch ( Throwable $exception ) {
		wp_die(
			esc_html( $exception->getMessage() ),
			esc_html__( 'Rechnungs-XML konnte nicht erstellt werden', 'cpsmartcrm' ),
			array( 'response' => 422 )
		);
	}

	nocache_headers();
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="rechnung-' . $document_id . '.xml"' );
	header( 'Content-Length: ' . strlen( $xml ) );
	echo $xml;
	exit;
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_post_wpscrm_download_einvoice', 'WPsCRM_download_einvoice' );
}
