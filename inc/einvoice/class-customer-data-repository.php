<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPsCRM_EInvoice_Customer_Data_Repository {

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
		$this->table = $wpdb->prefix . 'smartcrm_einvoice_customer_data';
	}

	public function get( $customer_id ) {
		$defaults = array(
			'customer_id' => (int) $customer_id,
			'customer_type' => '',
			'buyer_reference' => '',
			'electronic_address' => '',
			'electronic_address_scheme' => '',
			'contact_name' => '',
		);
		if ( (int) $customer_id < 1 ) {
			return $defaults;
		}

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE customer_id = %d", (int) $customer_id ),
			ARRAY_A
		);

		return $row ? array_merge( $defaults, $row ) : $defaults;
	}

	public function save( $customer_id, array $data ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id < 1 ) {
			throw new InvalidArgumentException( 'A valid CRM customer ID is required.' );
		}

		$customer_type = sanitize_key( $data['customer_type'] ?? '' );
		if ( ! in_array( $customer_type, array( 'b2c', 'b2b', 'public' ), true ) ) {
			throw new InvalidArgumentException( 'The E-invoice customer type must be B2C, B2B, or public authority.' );
		}

		$electronic_address = sanitize_text_field( $data['electronic_address'] ?? '' );
		$electronic_address_scheme = strtoupper( sanitize_text_field( $data['electronic_address_scheme'] ?? '' ) );
		if ( ( '' === $electronic_address ) !== ( '' === $electronic_address_scheme ) ) {
			throw new InvalidArgumentException( 'Electronic address and scheme must be provided together.' );
		}
		if ( '' !== $electronic_address_scheme && ! preg_match( '/^[A-Z0-9]{2,4}$/', $electronic_address_scheme ) ) {
			throw new InvalidArgumentException( 'The electronic address scheme must contain two to four letters or digits.' );
		}

		$now = current_time( 'mysql', true );
		$values = array(
			'customer_id' => $customer_id,
			'customer_type' => $customer_type,
			'buyer_reference' => sanitize_text_field( $data['buyer_reference'] ?? '' ),
			'electronic_address' => $electronic_address,
			'electronic_address_scheme' => $electronic_address_scheme,
			'contact_name' => sanitize_text_field( $data['contact_name'] ?? '' ),
			'updated_at' => $now,
		);

		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT customer_id FROM {$this->table} WHERE customer_id = %d", $customer_id )
		);
		if ( $existing ) {
			$result = $this->wpdb->update(
				$this->table,
				$values,
				array( 'customer_id' => $customer_id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$values['created_at'] = $now;
			$result = $this->wpdb->insert(
				$this->table,
				$values,
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		if ( false === $result ) {
			throw new RuntimeException( 'E-invoice customer data could not be stored: ' . $this->wpdb->last_error );
		}
	}
}
