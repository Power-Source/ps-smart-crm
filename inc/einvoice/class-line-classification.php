<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPsCRM_EInvoice_Line_Classification {

	public static function unit_options() {
		return array(
			'C62' => __( 'Stück', 'cpsmartcrm' ),
			'HUR' => __( 'Stunde', 'cpsmartcrm' ),
			'DAY' => __( 'Tag', 'cpsmartcrm' ),
			'MON' => __( 'Monat', 'cpsmartcrm' ),
			'KGM' => __( 'Kilogramm', 'cpsmartcrm' ),
			'MTR' => __( 'Meter', 'cpsmartcrm' ),
			'LTR' => __( 'Liter', 'cpsmartcrm' ),
			'XPK' => __( 'Packung', 'cpsmartcrm' ),
		);
	}

	public static function tax_category_options() {
		if ( self::is_small_business() ) {
			return array( 'E' => __( 'Steuerbefreit (§ 19 UStG)', 'cpsmartcrm' ) );
		}

		return array(
			'S' => __( 'Normale Umsatzsteuer', 'cpsmartcrm' ),
			'Z' => __( 'Nullsteuersatz (0 %)', 'cpsmartcrm' ),
			'E' => __( 'Steuerbefreit', 'cpsmartcrm' ),
			'AE' => __( 'Reverse Charge (§ 13b UStG)', 'cpsmartcrm' ),
			'K' => __( 'Innergemeinschaftliche Lieferung', 'cpsmartcrm' ),
		);
	}

	public static function normalize_unit_code( $unit_code ) {
		$unit_code = strtoupper( sanitize_text_field( $unit_code ) );
		if ( ! array_key_exists( $unit_code, self::unit_options() ) ) {
			throw new InvalidArgumentException( 'The selected E-invoice unit code is not supported.' );
		}

		return $unit_code;
	}

	public static function resolve_tax_category( $stored_category, $tax_rate ) {
		if ( self::is_small_business() ) {
			return 'E';
		}

		$tax_rate = self::decimal( $tax_rate );
		$stored_category = strtoupper( sanitize_text_field( $stored_category ) );
		if ( '0' !== $tax_rate ) {
			if ( '' !== $stored_category && 'S' !== $stored_category ) {
				throw new InvalidArgumentException( 'Positive VAT rates must use the standard VAT category.' );
			}
			return 'S';
		}

		if ( '' === $stored_category ) {
			return 'Z';
		}
		if ( ! in_array( $stored_category, array( 'Z', 'E', 'AE', 'K' ), true ) ) {
			throw new InvalidArgumentException( 'The selected zero-rate E-invoice tax category is not supported.' );
		}

		return $stored_category;
	}

	public static function exemption_reason( $category ) {
		return match ( $category ) {
			'E' => self::is_small_business()
				? 'Steuerbefreiung nach § 19 UStG'
				: 'Steuerbefreiter Umsatz',
			'AE' => 'Steuerschuldnerschaft des Leistungsempfängers nach § 13b UStG',
			'K' => 'Steuerfreie innergemeinschaftliche Lieferung',
			default => '',
		};
	}

	public static function is_small_business() {
		$business = get_option( 'CRM_business_settings', array() );
		return ! empty( $business['crm_kleinunternehmer'] )
			|| 'kleinunternehmer' === ( $business['business_type'] ?? '' );
	}

	private static function decimal( $value ) {
		$value = str_replace( ',', '.', trim( (string) $value ) );
		if ( ! preg_match( '/^\d+(?:\.\d+)?$/', $value ) ) {
			throw new InvalidArgumentException( 'The VAT rate must be a non-negative decimal number.' );
		}

		return preg_match( '/^0+(?:\.0+)?$/', $value ) ? '0' : $value;
	}
}
