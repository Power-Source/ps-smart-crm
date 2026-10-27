<?php

namespace PSource\EInvoice;

use InvalidArgumentException;

final class InvoiceLine {

	private $id;
	private $name;
	private $quantity;
	private $unit_code;
	private $unit_price;
	private $net_amount;
	private $tax_category;
	private $tax_rate;
	private $price_base_quantity;
	private $tax_exemption_reason;
	private $allowances_charges;

	public function __construct( array $data ) {
		$this->id = trim( (string) ( isset( $data['id'] ) ? $data['id'] : '' ) );
		$this->name = trim( (string) ( isset( $data['name'] ) ? $data['name'] : '' ) );
		$this->quantity = self::normalize_decimal( isset( $data['quantity'] ) ? $data['quantity'] : '' );
		$this->unit_code = strtoupper( trim( (string) ( isset( $data['unit_code'] ) ? $data['unit_code'] : '' ) ) );
		$this->unit_price = self::amount( $data, 'unit_price' );
		$this->net_amount = self::amount( $data, 'net_amount' );
		$this->tax_category = strtoupper( trim( (string) ( isset( $data['tax_category'] ) ? $data['tax_category'] : '' ) ) );
		$this->tax_rate = self::normalize_decimal( isset( $data['tax_rate'] ) ? $data['tax_rate'] : '', true );
		$this->price_base_quantity = self::normalize_decimal( $data['price_base_quantity'] ?? '1' );
		$this->tax_exemption_reason = trim( (string) ( $data['tax_exemption_reason'] ?? '' ) );
		$this->allowances_charges = self::objects( $data['allowances_charges'] ?? array() );
	}

	private static function objects( $values ) {
		if ( ! is_array( $values ) ) {
			throw new InvalidArgumentException( 'Line allowances and charges must be an array.' );
		}

		return array_map(
			static function ( $value ) {
				return $value instanceof AllowanceCharge
					? $value
					: new AllowanceCharge( $value );
			},
			$values
		);
	}

	private static function amount( array $data, $key ) {
		if ( ! isset( $data[ $key ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Invoice line field "%s" is required.', $key ) );
		}

		return $data[ $key ] instanceof Amount
			? $data[ $key ]
			: Amount::from_string( $data[ $key ] );
	}

	private static function normalize_decimal( $value, $allow_zero = false ) {
		$value = str_replace( ',', '.', trim( (string) $value ) );
		if ( ! preg_match( '/^\d+(?:\.\d{1,4})?$/', $value ) ) {
			throw new InvalidArgumentException( 'Decimal value must be positive and have no more than four decimal places.' );
		}

		$digits = str_replace( '.', '', $value );
		$is_zero = '' === trim( $digits, '0' );

		if ( ! $allow_zero && $is_zero ) {
			throw new InvalidArgumentException( 'Quantity must be greater than zero.' );
		}

		if ( $allow_zero ) {
			$parts = explode( '.', $value, 2 );
			if ( (int) $parts[0] > 100 || ( 100 === (int) $parts[0] && isset( $parts[1] ) && '' !== trim( $parts[1], '0' ) ) ) {
				throw new InvalidArgumentException( 'Tax rate must be between zero and 100.' );
			}
		}

		$parts = explode( '.', $value, 2 );
		$fraction = isset( $parts[1] ) ? rtrim( $parts[1], '0' ) : '';

		return '' === $fraction ? (string) (int) $parts[0] : (string) (int) $parts[0] . '.' . $fraction;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_unit_code() {
		return $this->unit_code;
	}

	public function get_net_amount() {
		return $this->net_amount;
	}

	public function get_tax_category() {
		return $this->tax_category;
	}

	public function to_array() {
		return array(
			'id' => $this->id,
			'name' => $this->name,
			'quantity' => $this->quantity,
			'unit_code' => $this->unit_code,
			'unit_price' => $this->unit_price->to_string(),
			'net_amount' => $this->net_amount->to_string(),
			'tax_category' => $this->tax_category,
			'tax_rate' => $this->tax_rate,
			'price_base_quantity' => $this->price_base_quantity,
			'tax_exemption_reason' => $this->tax_exemption_reason,
			'allowances_charges' => array_map(
				static function ( AllowanceCharge $item ) {
					return $item->to_array();
				},
				$this->allowances_charges
			),
		);
	}
}
