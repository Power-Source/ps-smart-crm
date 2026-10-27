<?php

namespace PSource\EInvoice;

final class Address {

	private $name;
	private $address_line;
	private $postal_code;
	private $city;
	private $country_code;
	private $region;

	public function __construct( array $data ) {
		$this->name = self::text( $data, 'name' );
		$this->address_line = self::text( $data, 'address_line' );
		$this->postal_code = self::text( $data, 'postal_code' );
		$this->city = self::text( $data, 'city' );
		$this->country_code = strtoupper( self::text( $data, 'country_code' ) );
		$this->region = self::text( $data, 'region' );
	}

	private static function text( array $data, $key ) {
		return isset( $data[ $key ] ) ? trim( (string) $data[ $key ] ) : '';
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

	public function to_array() {
		return array(
			'name' => $this->name,
			'address_line' => $this->address_line,
			'postal_code' => $this->postal_code,
			'city' => $this->city,
			'country_code' => $this->country_code,
			'region' => $this->region,
		);
	}
}
