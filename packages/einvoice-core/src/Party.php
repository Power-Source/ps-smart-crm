<?php

namespace PSource\EInvoice;

final class Party {

	private $name;
	private $address_line;
	private $postal_code;
	private $city;
	private $country_code;
	private $region;
	private $vat_id;
	private $tax_id;
	private $email;
	private $electronic_address;
	private $electronic_address_scheme;
	private $contact_name;
	private $contact_phone;
	private $contact_email;

	public function __construct( array $data ) {
		$this->name = self::text( $data, 'name' );
		$this->address_line = self::text( $data, 'address_line' );
		$this->postal_code = self::text( $data, 'postal_code' );
		$this->city = self::text( $data, 'city' );
		$this->country_code = strtoupper( self::text( $data, 'country_code' ) );
		$this->region = self::text( $data, 'region' );
		$this->vat_id = self::text( $data, 'vat_id' );
		$this->tax_id = self::text( $data, 'tax_id' );
		$this->email = self::text( $data, 'email' );
		$this->electronic_address = self::text( $data, 'electronic_address' );
		$this->electronic_address_scheme = self::text( $data, 'electronic_address_scheme' );
		$this->contact_name = self::text( $data, 'contact_name' );
		$this->contact_phone = self::text( $data, 'contact_phone' );
		$this->contact_email = self::text( $data, 'contact_email' );
	}

	private static function text( array $data, $key ) {
		return isset( $data[ $key ] ) ? trim( (string) $data[ $key ] ) : '';
	}

	public function get_name() {
		return $this->name;
	}

	public function get_address_line() {
		return $this->address_line;
	}

	public function get_postal_code() {
		return $this->postal_code;
	}

	public function get_city() {
		return $this->city;
	}

	public function get_country_code() {
		return $this->country_code;
	}

	public function get_electronic_address() {
		return $this->electronic_address;
	}

	public function get_electronic_address_scheme() {
		return $this->electronic_address_scheme;
	}

	public function get_contact_name() {
		return $this->contact_name;
	}

	public function get_contact_phone() {
		return $this->contact_phone;
	}

	public function get_contact_email() {
		return $this->contact_email;
	}

	public function to_array() {
		return array(
			'name' => $this->name,
			'address_line' => $this->address_line,
			'postal_code' => $this->postal_code,
			'city' => $this->city,
			'country_code' => $this->country_code,
			'region' => $this->region,
			'vat_id' => $this->vat_id,
			'tax_id' => $this->tax_id,
			'email' => $this->email,
			'electronic_address' => $this->electronic_address,
			'electronic_address_scheme' => $this->electronic_address_scheme,
			'contact_name' => $this->contact_name,
			'contact_phone' => $this->contact_phone,
			'contact_email' => $this->contact_email,
		);
	}
}
