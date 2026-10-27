<?php

use PSource\EInvoice\XRechnungUblExporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPsCRM_EInvoice_CRM_Exporter {

	private $wpdb;

	public function __construct( $wpdb = null ) {
		if ( null === $wpdb ) {
			global $wpdb;
		}
		$this->wpdb = $wpdb;
	}

	public function generate( $document_id ) {
		$document_id = (int) $document_id;
		$metadata = ( new WPsCRM_EInvoice_Document_Metadata_Repository( $this->wpdb ) )->get( $document_id );
		$invoice = ( new WPsCRM_EInvoice_CRM_Adapter( $this->wpdb ) )->create_snapshot( $document_id );
		$customer_type = $invoice->to_array()['source']['customer_type'] ?? 'b2c';
		$enabled = null === $metadata['enabled']
			? in_array( $customer_type, array( 'b2b', 'public' ), true )
			: (bool) $metadata['enabled'];
		if ( ! $enabled ) {
			throw new RuntimeException( 'E-invoice export is disabled for this invoice.' );
		}

		$exporter = new XRechnungUblExporter();
		$validation = $exporter->validate( $invoice );
		if ( ! $validation->is_valid() ) {
			throw new WPsCRM_EInvoice_Validation_Exception( $validation->get_errors() );
		}

		return $exporter->export( $invoice );
	}
}

final class WPsCRM_EInvoice_Validation_Exception extends RuntimeException {

	private array $validation_errors;

	public function __construct( array $validation_errors ) {
		parent::__construct( 'E-invoice validation failed.' );
		$this->validation_errors = $validation_errors;
	}

	public function get_validation_errors(): array {
		return $this->validation_errors;
	}
}

function WPsCRM_download_einvoice() {
	if ( ! current_user_can( 'manage_crm' ) ) {
		wp_die( esc_html__( 'Du hast keine Berechtigung, E-Rechnungen herunterzuladen.', 'cpsmartcrm' ), '', array( 'response' => 403 ) );
	}

	$document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;
	check_admin_referer( 'wpscrm_download_einvoice_' . $document_id );

	try {
		$xml = ( new WPsCRM_EInvoice_CRM_Exporter() )->generate( $document_id );
	} catch ( WPsCRM_EInvoice_Validation_Exception $exception ) {
		$messages = array_map(
			static fn( array $error ): string => (string) ( $error['message'] ?? __( 'Ungültige Rechnungsdaten.', 'cpsmartcrm' ) ),
			$exception->get_validation_errors()
		);
		wp_die(
			esc_html( implode( "\n", $messages ) ),
			esc_html__( 'E-Rechnung konnte nicht erstellt werden', 'cpsmartcrm' ),
			array( 'response' => 422 )
		);
	} catch ( Throwable $exception ) {
		wp_die(
			esc_html( $exception->getMessage() ),
			esc_html__( 'E-Rechnung konnte nicht erstellt werden', 'cpsmartcrm' ),
			array( 'response' => 422 )
		);
	}

	nocache_headers();
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="e-rechnung-' . $document_id . '.xml"' );
	header( 'Content-Length: ' . strlen( $xml ) );
	echo $xml;
	exit;
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_post_wpscrm_download_einvoice', 'WPsCRM_download_einvoice' );
}
