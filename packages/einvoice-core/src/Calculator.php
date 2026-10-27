<?php

namespace PSource\EInvoice;

use InvalidArgumentException;

final class Calculator {

	public static function line_net_amount( $unit_price, $quantity, $discount = '0', $discount_type = 'percentage' ) {
		$price = Amount::from_string( self::decimal_string( $unit_price ) )->to_minor_units();
		$quantity_scaled = self::scaled_decimal( $quantity, 4, false );

		if ( 'fixed' === $discount_type ) {
			$discount_minor = Amount::from_string( self::decimal_string( $discount ) )->to_minor_units();
			if ( $discount_minor > $price ) {
				throw new InvalidArgumentException( 'Fixed line discount must not exceed the unit price.' );
			}

			return Amount::from_minor_units(
				self::divide_and_round( ( $price - $discount_minor ) * $quantity_scaled, 10000 )
			);
		}

		$discount_scaled = self::scaled_decimal( $discount, 2, true );
		if ( $discount_scaled > 10000 ) {
			throw new InvalidArgumentException( 'Percentage line discount must not exceed 100 percent.' );
		}

		return Amount::from_minor_units(
			self::divide_and_round( $price * $quantity_scaled * ( 10000 - $discount_scaled ), 100000000 )
		);
	}

	public static function tax_amount( Amount $taxable_amount, $rate ) {
		$rate_scaled = self::scaled_decimal( $rate, 4, true );
		if ( $rate_scaled > 1000000 ) {
			throw new InvalidArgumentException( 'Tax rate must not exceed 100 percent.' );
		}

		return Amount::from_minor_units(
			self::divide_and_round( $taxable_amount->to_minor_units() * $rate_scaled, 1000000 )
		);
	}

	private static function decimal_string( $value ) {
		return str_replace( ',', '.', trim( (string) $value ) );
	}

	private static function scaled_decimal( $value, $precision, $allow_zero ) {
		$value = self::decimal_string( $value );
		if ( ! preg_match( '/^\d+(?:\.\d{1,' . $precision . '})?$/', $value ) ) {
			throw new InvalidArgumentException( 'Decimal value has an invalid precision.' );
		}

		list( $whole, $fraction ) = array_pad( explode( '.', $value, 2 ), 2, '' );
		$scaled = ( (int) $whole * ( 10 ** $precision ) ) + (int) str_pad( $fraction, $precision, '0' );
		if ( ! $allow_zero && 0 === $scaled ) {
			throw new InvalidArgumentException( 'Decimal value must be greater than zero.' );
		}

		return $scaled;
	}

	private static function divide_and_round( $numerator, $denominator ) {
		if ( $numerator < 0 ) {
			throw new InvalidArgumentException( 'Calculated invoice amounts must not be negative.' );
		}

		return intdiv( $numerator + intdiv( $denominator, 2 ), $denominator );
	}
}
