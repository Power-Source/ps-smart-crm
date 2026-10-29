<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'WPsCRM_TABLE', 'wp_smartcrm_' );

function get_option( $key, $default = false ) {
	$options = array(
		'CRM_business_settings' => array(
			'business_name' => 'Verkaeufer GmbH',
			'business_address' => 'Hauptstrasse',
			'business_number' => '1',
			'business_zip' => '10115',
			'business_town' => 'Berlin',
			'business_country' => 'DE',
			'business_ustid' => 'DE123456789',
			'business_taxid' => '12/345/67890',
			'business_email' => 'billing@seller.example',
			'business_iban' => 'DE02120300000000202051',
			'business_swift' => 'BYLADEM1001',
		),
		'CRM_documents_settings' => array(
			'invoices_prefix' => 'RE-',
			'invoices_suffix' => '-2026',
		),
	);

	return $options[ $key ] ?? $default;
}

function wp_strip_all_tags( $value ) {
	return strip_tags( $value );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function current_time( $type, $gmt = false ) {
	return '2026-10-29 12:00:00';
}

function assert_exporter_true( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

final class EInvoice_Exporter_Fake_WPDB {

	public $prefix = 'wp_';
	public $last_error = '';

	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) {
			$query = preg_replace( '/%d/', (string) $arg, $query, 1 );
		}

		return $query;
	}

	public function get_row( $query, $output = null ) {
		if ( str_contains( $query, 'dokumente WHERE' ) ) {
			return array(
				'id' => '7',
				'tipo' => '2',
				'fk_kunde' => '9',
				'progressivo' => '42',
				'data' => '2026-10-29',
				'data_scadenza' => '2026-11-12',
				'totale_imponibile' => '53.97',
				'totale_imposta' => '10.25',
				'totale' => '64.22',
				'modalita_pagamento' => '14 Tage netto',
				'riferimento' => 'PO-4711',
			);
		}
		if ( str_contains( $query, 'kunde WHERE' ) ) {
			return array(
				'ID_kunde' => '9',
				'firmenname' => 'Kunde AG',
				'adresse' => 'Kundenweg 2',
				'cap' => '20095',
				'standort' => 'Hamburg',
				'nation' => 'DE',
				'p_iva' => 'DE987654321',
				'cod_fis' => '',
				'email' => 'invoice@buyer.example',
			);
		}

		return null;
	}

	public function get_results( $query, $output = null ) {
		return array(
			array(
				'n_riga' => '1',
				'tipo' => '2',
				'descrizione' => 'Beratung & Support',
				'qta' => '3',
				'prezzo' => '19.99',
				'sconto' => '10',
				'iva' => '19',
				'totale' => '53.97',
			),
		);
	}
}

require_once dirname( __DIR__ ) . '/inc/einvoice/bootstrap.php';

$xml = ( new WPsCRM_EInvoice_CRM_Exporter( new EInvoice_Exporter_Fake_WPDB() ) )->generate( 7 );
assert_exporter_true( str_starts_with( $xml, '<?xml version="1.0" encoding="UTF-8"?>' ), 'The XML declaration must be present.' );
assert_exporter_true( str_contains( $xml, '<B2BInvoice xmlns="https://psource.eimen.net/schema/b2b-invoice/1.0" version="1.0">' ), 'The export must use the lightweight B2BInvoice root element.' );
assert_exporter_true( false === str_contains( $xml, 'xrechnung' ), 'The export must not contain XRechnung metadata.' );
assert_exporter_true( false === str_contains( $xml, 'Peppol' ), 'The export must not contain Peppol metadata.' );
assert_exporter_true( str_contains( $xml, '<InvoiceNumber>RE-42-2026</InvoiceNumber>' ), 'The existing CRM invoice number must be exported.' );
assert_exporter_true( str_contains( $xml, 'Beratung &amp; Support' ), 'Line descriptions must be XML escaped.' );
assert_exporter_true( str_contains( $xml, '<GrossAmount>64.22</GrossAmount>' ), 'The existing invoice total must be exported.' );

echo "B2B XML exporter tests passed.\n";
