<?php

use PSource\EInvoice\InvoiceValidator;

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'WPsCRM_TABLE', 'wp_smartcrm_' );

$test_options = array(
	'CRM_business_settings' => array(
		'business_name' => 'Verkaeufer GmbH',
		'business_address' => 'Hauptstrasse',
		'business_number' => '1',
		'business_zip' => '10115',
		'business_town' => 'Berlin',
		'crm_business_provinz' => 'Berlin',
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

function get_option( $key, $default = false ) {
	global $test_options;
	return $test_options[ $key ] ?? $default;
}

function wp_strip_all_tags( $value ) {
	return strip_tags( $value );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function __( $value, $domain = null ) {
	return $value;
}

function current_time( $type, $gmt = false ) {
	return '2026-10-27 15:00:00';
}

function is_multisite() {
	return false;
}

function wp_mkdir_p( $path ) {
	return is_dir( $path ) || mkdir( $path, 0777, true );
}

function wp_generate_uuid4() {
	return '12345678-1234-4234-8234-123456789abc';
}

function assert_adapter_true( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

final class EInvoice_Fake_WPDB {

	public $prefix = 'wp_';
	public $insert_id = 0;
	public $last_error = '';
	private $document;
	private $customer;
	private $rows;
	private $metadata = array();
	private $customer_data = array();

	public function __construct() {
		$this->document = array(
			'id' => '7',
			'tipo' => '2',
			'fk_kunde' => '9',
			'progressivo' => '42',
			'data' => '2026-10-27',
			'data_scadenza' => '2026-11-10',
			'totale_imponibile' => '53.97',
			'totale_imposta' => '10.25',
			'totale' => '64.22',
			'totale_netto' => '64.22',
			'tot_cassa_inps' => '0.00',
			'ritenuta_acconto' => '0.00',
			'tipo_sconto' => '0',
			'modalita_pagamento' => '14 Tage netto',
			'riferimento' => 'PO-4711',
			'filename' => 'invoice-42.pdf',
		);
		$this->customer = array(
			'ID_kunde' => '9',
			'firmenname' => 'Kunde AG',
			'name' => '',
			'nachname' => '',
			'adresse' => 'Kundenweg 2',
			'cap' => '20095',
			'standort' => 'Hamburg',
			'provinz' => 'Hamburg',
			'nation' => 'DE',
			'p_iva' => 'DE987654321',
			'cod_fis' => '',
			'email' => 'invoice@buyer.example',
			'telefono1' => '+49 40 123456',
			'tipo_cliente' => '2',
		);
		$this->rows = array(
			array(
				'id' => '1',
				'fk_dokumente' => '7',
				'n_riga' => '1',
				'tipo' => '2',
				'descrizione' => 'Beratung',
				'qta' => '3.0000',
				'prezzo' => '19.9900',
				'sconto' => '10.0000',
				'iva' => '19',
				'unit_code' => 'HUR',
				'tax_category' => 'S',
				'eliminato' => '0',
			),
		);
	}

	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) ? (string) $arg : "'" . addslashes( (string) $arg ) . "'";
			$query = preg_replace( '/%[ds]/', $replacement, $query, 1 );
		}
		return $query;
	}

	public function configure_zero_tax( $category, $stored_rate = '0' ) {
		$this->rows[0]['iva'] = $stored_rate;
		$this->rows[0]['tax_category'] = $category;
		$this->document['totale_imposta'] = '0.00';
		$this->document['totale'] = '53.97';
		$this->document['totale_netto'] = '53.97';
	}

	public function get_row( $query, $output = null ) {
		if ( str_contains( $query, 'dokumente WHERE' ) ) {
			return $this->document;
		}
		if ( str_contains( $query, 'kunde WHERE' ) ) {
			return $this->customer;
		}
		if ( str_contains( $query, 'einvoice_document_data' ) ) {
			return $this->metadata ?: null;
		}
		if ( str_contains( $query, 'einvoice_customer_data' ) ) {
			return $this->customer_data ?: null;
		}
		return null;
	}

	public function get_results( $query, $output = null ) {
		return str_contains( $query, 'dokumente_dettaglio' ) ? $this->rows : array();
	}

	public function insert( $table, $data, $formats ) {
		if ( str_contains( $table, 'einvoice_document_data' ) ) {
			$this->metadata = $data;
			return 1;
		}
		if ( str_contains( $table, 'einvoice_customer_data' ) ) {
			$this->customer_data = $data;
			return 1;
		}
		return false;
	}

	public function get_var( $query ) {
		if ( str_contains( $query, 'einvoice_document_data' ) ) {
			return $this->metadata['document_id'] ?? null;
		}
		if ( str_contains( $query, 'einvoice_customer_data' ) ) {
			return $this->customer_data['customer_id'] ?? null;
		}
		return null;
	}

	public function update( $table, $data, $where, $formats = null, $where_formats = null ) {
		if ( str_contains( $table, 'einvoice_document_data' ) ) {
			$this->metadata = array_merge( $this->metadata, $data );
			return 1;
		}
		if ( str_contains( $table, 'einvoice_customer_data' ) ) {
			$this->customer_data = array_merge( $this->customer_data, $data );
			return 1;
		}
		return false;
	}
}

require_once dirname( __DIR__ ) . '/inc/einvoice/bootstrap.php';

$wpdb = new EInvoice_Fake_WPDB();
$snapshot = ( new WPsCRM_EInvoice_CRM_Adapter( $wpdb ) )->create_snapshot( 7 );
$validation = ( new InvoiceValidator() )->validate( $snapshot );
$xrechnung_validation = ( new \PSource\EInvoice\XRechnungUblExporter() )->validate( $snapshot );

assert_adapter_true( $validation->is_valid(), 'A consistent CRM invoice must produce a valid snapshot.' );
assert_adapter_true( ! $xrechnung_validation->is_valid(), 'XRechnung validation must reject a missing Buyer Reference.' );
assert_adapter_true( 'buyer_reference' === $xrechnung_validation->get_errors()[0]['path'], 'XRechnung validation must return a field path.' );
assert_adapter_true( 'RE-42-2026' === $snapshot->get_invoice_number(), 'The complete displayed invoice number must be frozen.' );
assert_adapter_true( '53.97' === $snapshot->to_array()['lines'][0]['net_amount'], 'The adapter must calculate discounted line net amounts exactly.' );
assert_adapter_true( '10.25' === $snapshot->to_array()['tax_breakdowns'][0]['tax_amount'], 'The adapter must calculate tax exactly.' );
assert_adapter_true( 'HUR' === $snapshot->to_array()['lines'][0]['unit_code'], 'The adapter must preserve the selected UNECE unit code.' );
assert_adapter_true( 'S' === WPsCRM_EInvoice_Line_Classification::resolve_tax_category( '', '19' ), 'Positive legacy VAT rates must map to standard VAT.' );
assert_adapter_true( 'Z' === WPsCRM_EInvoice_Line_Classification::resolve_tax_category( '', '0' ), 'Legacy zero VAT rates must map to zero-rated VAT.' );
assert_adapter_true( 'AE' === WPsCRM_EInvoice_Line_Classification::resolve_tax_category( 'AE', '0' ), 'Existing reverse-charge selection must remain distinct.' );
assert_adapter_true(
	str_contains( WPsCRM_EInvoice_Line_Classification::exemption_reason( 'AE' ), '§ 13b' ),
	'Reverse charge must use the existing German tax rule explanation.'
);

$test_options['CRM_business_settings']['business_type'] = 'kleinunternehmer';
assert_adapter_true( 'E' === WPsCRM_EInvoice_Line_Classification::resolve_tax_category( 'S', '19' ), 'The existing small-business rule must override line VAT categories.' );
$small_business_wpdb = new EInvoice_Fake_WPDB();
$small_business_wpdb->configure_zero_tax( 'S', '19' );
$small_business_snapshot = ( new WPsCRM_EInvoice_CRM_Adapter( $small_business_wpdb ) )->create_snapshot( 7 )->to_array();
assert_adapter_true( 'E' === $small_business_snapshot['lines'][0]['tax_category'], 'Small-business invoices must export the exempt category.' );
assert_adapter_true( '0' === $small_business_snapshot['lines'][0]['tax_rate'], 'Small-business invoices must override legacy line VAT with zero percent.' );
assert_adapter_true( '0.00' === $small_business_snapshot['totals']['tax_amount'], 'Small-business invoices must not calculate VAT.' );
$test_options['CRM_business_settings']['business_type'] = 'standard';

$reverse_charge_wpdb = new EInvoice_Fake_WPDB();
$reverse_charge_wpdb->configure_zero_tax( 'AE' );
$reverse_charge_snapshot = ( new WPsCRM_EInvoice_CRM_Adapter( $reverse_charge_wpdb ) )->create_snapshot( 7 )->to_array();
assert_adapter_true( 'AE' === $reverse_charge_snapshot['lines'][0]['tax_category'], 'Reverse charge must remain distinct in the immutable snapshot.' );
assert_adapter_true( str_contains( $reverse_charge_snapshot['lines'][0]['tax_exemption_reason'], '§ 13b' ), 'Reverse charge must carry its legal exemption reason.' );

$issuer_wpdb = new EInvoice_Fake_WPDB();
$customer_data_repository = new WPsCRM_EInvoice_Customer_Data_Repository( $issuer_wpdb );
$customer_data_repository->save(
	9,
	array(
		'customer_type' => 'public',
		'buyer_reference' => 'LEITWEG-KUNDE',
		'electronic_address' => '0204:991-12345-06',
		'electronic_address_scheme' => '0204',
		'contact_name' => 'E-Rechnungsstelle',
	)
);
$stored_customer_data = $customer_data_repository->get( 9 );
assert_adapter_true( 'public' === $stored_customer_data['customer_type'], 'Public-authority customer type must be stored.' );
assert_adapter_true( '0204' === $stored_customer_data['electronic_address_scheme'], 'Electronic address scheme must be normalized and stored.' );

$xsd_only_wpdb = new EInvoice_Fake_WPDB();
$xsd_only_metadata = new WPsCRM_EInvoice_Document_Metadata_Repository( $xsd_only_wpdb );
$xsd_only_metadata->save( 7, array( 'buyer_reference' => 'B2B-4711' ) );
$xsd_only_xml = ( new WPsCRM_EInvoice_CRM_Exporter( $xsd_only_wpdb ) )->generate( 7 );
assert_adapter_true( str_contains( $xsd_only_xml, '<Invoice' ), 'B2B CRM invoices must export by default.' );

$disabled_wpdb = new EInvoice_Fake_WPDB();
( new WPsCRM_EInvoice_Document_Metadata_Repository( $disabled_wpdb ) )->save(
	7,
	array( 'enabled' => false, 'buyer_reference' => 'B2B-4711' )
);
$disabled_rejected = false;
try {
	( new WPsCRM_EInvoice_CRM_Exporter( $disabled_wpdb ) )->generate( 7 );
} catch ( RuntimeException $exception ) {
	$disabled_rejected = str_contains( $exception->getMessage(), 'disabled' );
}
assert_adapter_true( $disabled_rejected, 'A per-invoice opt-out must prevent E-invoice export.' );

$metadata_repository = new WPsCRM_EInvoice_Document_Metadata_Repository( $issuer_wpdb );
$metadata_repository->save(
	7,
	array(
		'buyer_reference' => 'LEITWEG-123',
		'service_start_date' => '2026-10-01',
		'service_end_date' => '2026-10-27',
		'order_reference' => 'PO-4711',
	)
);
$xml = ( new WPsCRM_EInvoice_CRM_Exporter( $issuer_wpdb ) )->generate( 7 );
assert_adapter_true( str_contains( $xml, 'LEITWEG-123' ), 'Stored metadata must be mapped into the exported XML.' );
assert_adapter_true( str_contains( $xml, 'PO-4711' ), 'Structured order references must be mapped into the exported XML.' );
assert_adapter_true( str_contains( $xml, '0204:991-12345-06' ), 'Customer electronic address must be mapped into the exported XML.' );
assert_adapter_true( str_contains( $xml, 'E-Rechnungsstelle' ), 'Customer E-invoice contact must be mapped into the exported XML.' );

echo "E-invoice CRM adapter tests passed.\n";
