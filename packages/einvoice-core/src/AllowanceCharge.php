<?php

namespace PSource\EInvoice;

use InvalidArgumentException;

final class AllowanceCharge {

	private $is_charge;
	private $amount;
	private $base_amount;
	private $reason;
	private $reason_code;
	private $tax_category;
	private $tax_rate;

	public function __construct( array $data ) {
		$this->is_charge = ! empty( $data['is_charge'] );
		$this->amount = self::amount( $data, 'amount' );
		$this->base_amount = isset( $data['base_amount'] )
			&& (
				$data['base_amount'] instanceof Amount
				|| '' !== trim( (string) $data['base_amount'] )
			)
				? self::amount( $data, 'base_amount' )
				: null;
		$this->reason = trim( (string) ( $data['reason'] ?? '' ) );
		$this->reason_code = trim( (string) ( $data['reason_code'] ?? '' ) );
		$this->tax_category = strtoupper( trim( (string) ( $data['tax_category'] ?? '' ) ) );
		$this->tax_rate = self::decimal( $data['tax_rate'] ?? '' );

		if ( $this->amount->is_negative() ) {
			throw new InvalidArgumentException( 'Allowance or charge amount must not be negative.' );
		}
	}

	private static function amount( array $data, $key ) {
		if ( ! isset( $data[ $key ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Allowance or charge field "%s" is required.', $key ) );
		}

		return $data[ $key ] instanceof Amount
			? $data[ $key ]
			: Amount::from_string( $data[ $key ] );
	}

	private static function decimal( $value ) {
		$value = str_replace( ',', '.', trim( (string) $value ) );
		if ( '' !== $value && ! preg_match( '/^\d+(?:\.\d{1,4})?$/', $value ) ) {
			throw new InvalidArgumentException( 'Allowance or charge tax rate must have no more than four decimal places.' );
		}

		return $value;
	}

	public function is_charge() {
		return $this->is_charge;
	}

	public function get_amount() {
		return $this->amount;
	}

	public function to_array() {
		return array(
			'is_charge' => $this->is_charge,
			'amount' => $this->amount->to_string(),
			'base_amount' => $this->base_amount ? $this->base_amount->to_string() : '',
			'reason' => $this->reason,
			'reason_code' => $this->reason_code,
			'tax_category' => $this->tax_category,
			'tax_rate' => $this->tax_rate,
		);
	}
}
