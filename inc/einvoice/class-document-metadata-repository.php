<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPsCRM_EInvoice_Document_Metadata_Repository {

	private $wpdb;
	private $table;

	public function __construct( $wpdb = null ) {
		if ( null === $wpdb ) {
			global $wpdb;
		}
		if ( ! is_object( $wpdb ) ) {
			throw new InvalidArgumentException( 'A WordPress database connection is required.' );
		}

		$this->wpdb = $wpdb;
		$this->table = $wpdb->prefix . 'smartcrm_einvoice_document_data';
	}

	public function get( $document_id ) {
		$defaults = array(
			'document_id' => (int) $document_id,
			'enabled' => null,
			'buyer_reference' => '',
			'service_start_date' => '',
			'service_end_date' => '',
			'order_reference' => '',
			'contract_reference' => '',
			'project_reference' => '',
		);
		if ( (int) $document_id < 1 ) {
			return $defaults;
		}

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE document_id = %d", (int) $document_id ),
			ARRAY_A
		);

		return $row ? array_merge( $defaults, $row ) : $defaults;
	}

	public function save( $document_id, array $data ) {
		$document_id = (int) $document_id;
		if ( $document_id < 1 ) {
			throw new InvalidArgumentException( 'A valid CRM document ID is required.' );
		}
		$service_start_date = self::date_or_null( $data['service_start_date'] ?? '' );
		$service_end_date = self::date_or_null( $data['service_end_date'] ?? '' );
		if ( null !== $service_start_date && null !== $service_end_date && $service_start_date > $service_end_date ) {
			throw new InvalidArgumentException( 'The service period end date must not precede its start date.' );
		}
		$now = current_time( 'mysql', true );
		$values = array(
			'document_id' => $document_id,
			'enabled' => array_key_exists( 'enabled', $data ) ? (int) (bool) $data['enabled'] : null,
			'buyer_reference' => sanitize_text_field( $data['buyer_reference'] ?? '' ),
			'service_start_date' => $service_start_date,
			'service_end_date' => $service_end_date,
			'order_reference' => sanitize_text_field( $data['order_reference'] ?? '' ),
			'contract_reference' => sanitize_text_field( $data['contract_reference'] ?? '' ),
			'project_reference' => sanitize_text_field( $data['project_reference'] ?? '' ),
			'updated_at' => $now,
		);

		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT document_id FROM {$this->table} WHERE document_id = %d", $document_id )
		);
		if ( $existing ) {
			$result = $this->wpdb->update(
				$this->table,
				$values,
				array( 'document_id' => $document_id ),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$values['created_at'] = $now;
			$result = $this->wpdb->insert(
				$this->table,
				$values,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		if ( false === $result ) {
			throw new RuntimeException( 'E-invoice metadata could not be stored: ' . $this->wpdb->last_error );
		}
	}

	private static function date_or_null( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return null;
		}

		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
			throw new InvalidArgumentException( 'E-invoice dates must use YYYY-MM-DD.' );
		}

		return $value;
	}
}
