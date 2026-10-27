<?php

namespace PSource\EInvoice;

use InvalidArgumentException;

final class TaxBreakdown {

	private $category;
	private $rate;
	private $taxable_amount;
	private $tax_amount;
	private $exemption_reason;

	public function __construct( array $data ) {
		$this->category = strtoupper( trim( (string) ( isset( $data['category'] ) ? $data['category'] : '' ) ) );
		$this->rate = str_replace( ',', '.', trim( (string) ( isset( $data['rate'] ) ? $data['rate'] : '' ) ) );
		$this->taxable_amount = self::amount( $data, 'taxable_amount' );
		$this->tax_amount = self::amount( $data, 'tax_amount' );
		$this->exemption_reason = trim( (string) ( isset( $data['exemption_reason'] ) ? $data['exemption_reason'] : '' ) );

		if ( ! preg_match( '/^\d+(?:\.\d{1,4})?$/', $this->rate ) ) {
			throw new InvalidArgumentException( 'Tax rate must have no more than four decimal places.' );
		}

		$rate_parts = explode( '.', $this->rate, 2 );
		if ( (int) $rate_parts[0] > 100 || ( 100 === (int) $rate_parts[0] && isset( $rate_parts[1] ) && '' !== trim( $rate_parts[1], '0' ) ) ) {
			throw new InvalidArgumentException( 'Tax rate must be between zero and 100.' );
		}
	}

	private static function amount( array $data, $key ) {
		if ( ! isset( $data[ $key ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Tax field "%s" is required.', $key ) );
		}

		return $data[ $key ] instanceof Amount
			? $data[ $key ]
			: Amount::from_string( $data[ $key ] );
	}

	public function get_category() {
		return $this->category;
	}

	public function get_taxable_amount() {
		return $this->taxable_amount;
	}

	public function get_tax_amount() {
		return $this->tax_amount;
	}

	public function to_array() {
		return array(
			'category' => $this->category,
			'rate' => $this->rate,
			'taxable_amount' => $this->taxable_amount->to_string(),
			'tax_amount' => $this->tax_amount->to_string(),
			'exemption_reason' => $this->exemption_reason,
		);
	}
}
