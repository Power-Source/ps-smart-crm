<?php

namespace PSource\EInvoice;

use InvalidArgumentException;
use OverflowException;

final class Amount {

	private $minor_units;

	private function __construct( $minor_units ) {
		$this->minor_units = $minor_units;
	}

	public static function from_string( $value ) {
		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			throw new InvalidArgumentException( 'Amount must be provided as a decimal string or integer.' );
		}

		$value = trim( (string) $value );
		if ( ! preg_match( '/^-?\d+(?:[.,]\d{1,2})?$/', $value ) ) {
			throw new InvalidArgumentException( 'Amount must have no more than two decimal places.' );
		}

		$negative = 0 === strpos( $value, '-' );
		$value = ltrim( $value, '-+' );
		$parts = preg_split( '/[.,]/', $value );
		$whole = ltrim( $parts[0], '0' );
		$whole = '' === $whole ? '0' : $whole;
		$fraction = isset( $parts[1] ) ? str_pad( $parts[1], 2, '0' ) : '00';

		$maximum_whole = (string) intdiv( PHP_INT_MAX, 100 );
		$maximum_fraction = PHP_INT_MAX % 100;
		if (
			strlen( $whole ) > strlen( $maximum_whole )
			|| (
				strlen( $whole ) === strlen( $maximum_whole )
				&& (
					strcmp( $whole, $maximum_whole ) > 0
					|| ( 0 === strcmp( $whole, $maximum_whole ) && (int) $fraction > $maximum_fraction )
				)
			)
		) {
			throw new OverflowException( 'Amount exceeds the supported range.' );
		}

		$minor_units = ( (int) $whole * 100 ) + (int) $fraction;
		if ( $negative ) {
			$minor_units *= -1;
		}

		return new self( $minor_units );
	}

	public static function zero() {
		return new self( 0 );
	}

	public static function from_minor_units( $minor_units ) {
		if ( ! is_int( $minor_units ) ) {
			throw new InvalidArgumentException( 'Minor units must be provided as an integer.' );
		}

		return new self( $minor_units );
	}

	public function add( Amount $amount ) {
		if (
			( $amount->minor_units > 0 && $this->minor_units > PHP_INT_MAX - $amount->minor_units )
			|| ( $amount->minor_units < 0 && $this->minor_units < PHP_INT_MIN - $amount->minor_units )
		) {
			throw new OverflowException( 'Amount addition exceeds the supported range.' );
		}

		return new self( $this->minor_units + $amount->minor_units );
	}

	public function subtract( Amount $amount ) {
		if (
			( $amount->minor_units < 0 && $this->minor_units > PHP_INT_MAX + $amount->minor_units )
			|| ( $amount->minor_units > 0 && $this->minor_units < PHP_INT_MIN + $amount->minor_units )
		) {
			throw new OverflowException( 'Amount subtraction exceeds the supported range.' );
		}

		return new self( $this->minor_units - $amount->minor_units );
	}

	public function equals( Amount $amount ) {
		return $this->minor_units === $amount->minor_units;
	}

	public function is_negative() {
		return $this->minor_units < 0;
	}

	public function to_minor_units() {
		return $this->minor_units;
	}

	public function to_string() {
		$absolute = abs( $this->minor_units );
		$formatted = intdiv( $absolute, 100 ) . '.' . str_pad( (string) ( $absolute % 100 ), 2, '0', STR_PAD_LEFT );

		return $this->minor_units < 0 ? '-' . $formatted : $formatted;
	}
}
