<?php

namespace PSource\EInvoice;

final class InvoiceValidator {

	public function validate( InvoiceSnapshot $invoice ) {
		$result = new ValidationResult();

		if ( '' === $invoice->get_invoice_number() ) {
			$result->add_error( 'invoice.number.required', 'Die Rechnungsnummer fehlt.', 'invoice_number' );
		}

		if ( ! preg_match( '/^[A-Z]{3}$/', $invoice->get_currency() ) ) {
			$result->add_error( 'invoice.currency.invalid', 'Die Währung muss als ISO-4217-Code angegeben werden.', 'currency' );
		}

		$this->validate_party( $invoice->get_seller(), 'seller', $result );
		$this->validate_party( $invoice->get_buyer(), 'buyer', $result );

		if (
			'' !== $invoice->get_service_start_date()
			&& '' !== $invoice->get_service_end_date()
			&& $invoice->get_service_start_date() > $invoice->get_service_end_date()
		) {
			$result->add_error( 'invoice.service_period.invalid', 'Das Ende des Leistungszeitraums liegt vor dessen Beginn.', 'service_end_date' );
		}

		foreach ( $invoice->get_references() as $index => $reference ) {
			if ( '' === $reference->get_type() || '' === $reference->get_id() ) {
				$result->add_error( 'invoice.reference.invalid', 'Dokumentreferenzen benötigen Typ und Kennung.', 'references.' . $index );
			}
		}

		$lines = $invoice->get_lines();
		if ( empty( $lines ) ) {
			$result->add_error( 'invoice.lines.required', 'Die Rechnung benötigt mindestens eine Position.', 'lines' );
		}

		$line_total = Amount::zero();
		$line_taxable_by_key = array();
		foreach ( $lines as $index => $line ) {
			$path = 'lines.' . $index;
			$line_data = $line->to_array();
			if ( '' === $line->get_id() ) {
				$result->add_error( 'line.id.required', 'Die Positionsnummer fehlt.', $path . '.id' );
			}
			if ( '' === $line->get_name() ) {
				$result->add_error( 'line.name.required', 'Die Positionsbeschreibung fehlt.', $path . '.name' );
			}
			if ( ! preg_match( '/^[A-Z0-9]{2,3}$/', $line->get_unit_code() ) ) {
				$result->add_error( 'line.unit.invalid', 'Der Einheitencode ist ungültig.', $path . '.unit_code' );
			}
			if ( '' === $line->get_tax_category() ) {
				$result->add_error( 'line.tax_category.required', 'Die Steuerkategorie fehlt.', $path . '.tax_category' );
			}
			$this->validate_tax_rule(
				$line_data['tax_category'],
				$line_data['tax_rate'],
				$line_data['tax_exemption_reason'],
				$path,
				$result
			);
			$line_total = $line_total->add( $line->get_net_amount() );
			$line_tax_key = $line_data['tax_category'] . '|' . $line_data['tax_rate'];
			$line_taxable_by_key[ $line_tax_key ] = isset( $line_taxable_by_key[ $line_tax_key ] )
				? $line_taxable_by_key[ $line_tax_key ]->add( $line->get_net_amount() )
				: $line->get_net_amount();
		}

		$totals = $invoice->get_totals();
		if ( ! $line_total->equals( $totals->get_line_net_amount() ) ) {
			$result->add_error( 'totals.lines.mismatch', 'Die Summe der Positionen stimmt nicht mit der Positionsnettosumme überein.', 'totals.line_net_amount' );
		}

		$expected_tax_exclusive = $totals->get_line_net_amount()
			->subtract( $totals->get_allowance_total() )
			->add( $totals->get_charge_total() );
		if ( ! $expected_tax_exclusive->equals( $totals->get_tax_exclusive_amount() ) ) {
			$result->add_error( 'totals.tax_exclusive.mismatch', 'Die Nettosumme nach Zu- und Abschlägen ist inkonsistent.', 'totals.tax_exclusive_amount' );
		}

		$allowance_total = Amount::zero();
		$charge_total = Amount::zero();
		foreach ( $invoice->get_allowances_charges() as $item ) {
			if ( $item->is_charge() ) {
				$charge_total = $charge_total->add( $item->get_amount() );
			} else {
				$allowance_total = $allowance_total->add( $item->get_amount() );
			}
		}
		if ( ! $allowance_total->equals( $totals->get_allowance_total() ) ) {
			$result->add_error( 'totals.allowances.mismatch', 'Die dokumentweiten Nachlässe stimmen nicht mit der Nachlasssumme überein.', 'totals.allowance_total' );
		}
		if ( ! $charge_total->equals( $totals->get_charge_total() ) ) {
			$result->add_error( 'totals.charges.mismatch', 'Die dokumentweiten Zuschläge stimmen nicht mit der Zuschlagssumme überein.', 'totals.charge_total' );
		}

		$tax_total = Amount::zero();
		$taxable_total = Amount::zero();
		$breakdown_taxable_by_key = array();
		foreach ( $invoice->get_tax_breakdowns() as $index => $tax ) {
			$tax_data = $tax->to_array();
			$path = 'tax_breakdowns.' . $index;
			$tax_total = $tax_total->add( $tax->get_tax_amount() );
			$taxable_total = $taxable_total->add( $tax->get_taxable_amount() );

			if ( '' === $tax->get_category() ) {
				$result->add_error( 'tax.category.required', 'Die Steuerkategorie der Steueraufschlüsselung fehlt.', $path . '.category' );
			}
			$this->validate_tax_rule(
				$tax_data['category'],
				$tax_data['rate'],
				$tax_data['exemption_reason'],
				$path,
				$result
			);

			$expected_tax = Calculator::tax_amount( $tax->get_taxable_amount(), $tax_data['rate'] );
			if ( ! $expected_tax->equals( $tax->get_tax_amount() ) ) {
				$result->add_error( 'tax.amount.mismatch', 'Der Steuerbetrag entspricht nicht Bemessungsgrundlage und Steuersatz.', $path . '.tax_amount' );
			}
			$tax_key = $tax_data['category'] . '|' . $tax_data['rate'];
			$breakdown_taxable_by_key[ $tax_key ] = isset( $breakdown_taxable_by_key[ $tax_key ] )
				? $breakdown_taxable_by_key[ $tax_key ]->add( $tax->get_taxable_amount() )
				: $tax->get_taxable_amount();
		}

		foreach ( array_unique( array_merge( array_keys( $line_taxable_by_key ), array_keys( $breakdown_taxable_by_key ) ) ) as $tax_key ) {
			if (
				! isset( $line_taxable_by_key[ $tax_key ], $breakdown_taxable_by_key[ $tax_key ] )
				|| ! $line_taxable_by_key[ $tax_key ]->equals( $breakdown_taxable_by_key[ $tax_key ] )
			) {
				$result->add_error( 'tax.breakdown.mismatch', 'Die Steueraufschlüsselung stimmt nicht mit den Positionssteuergruppen überein.', 'tax_breakdowns' );
			}
		}

		if ( ! $tax_total->equals( $totals->get_tax_amount() ) ) {
			$result->add_error( 'totals.tax.mismatch', 'Die Steueraufschlüsselung stimmt nicht mit dem Gesamtsteuerbetrag überein.', 'totals.tax_amount' );
		}

		if ( ! $taxable_total->equals( $totals->get_tax_exclusive_amount() ) ) {
			$result->add_error( 'totals.taxable.mismatch', 'Die Bemessungsgrundlagen stimmen nicht mit der Nettosumme überein.', 'tax_breakdowns' );
		}

		$expected_tax_inclusive = $totals->get_tax_exclusive_amount()->add( $totals->get_tax_amount() );
		if ( ! $expected_tax_inclusive->equals( $totals->get_tax_inclusive_amount() ) ) {
			$result->add_error( 'totals.gross.mismatch', 'Die Bruttosumme ist inkonsistent.', 'totals.tax_inclusive_amount' );
		}

		$expected_payable = $totals->get_tax_inclusive_amount()->subtract( $totals->get_prepaid_amount() );
		if ( ! $expected_payable->equals( $totals->get_payable_amount() ) ) {
			$result->add_error( 'totals.payable.mismatch', 'Der Zahlbetrag ist inkonsistent.', 'totals.payable_amount' );
		}

		return $result;
	}

	private function validate_tax_rule( $category, $rate, $exemption_reason, $path, ValidationResult $result ) {
		$is_breakdown = str_starts_with( $path, 'tax_breakdowns.' );
		$category_path = $path . ( $is_breakdown ? '.category' : '.tax_category' );
		$rate_path = $path . ( $is_breakdown ? '.rate' : '.tax_rate' );
		$reason_path = $path . ( $is_breakdown ? '.exemption_reason' : '.tax_exemption_reason' );
		$allowed_categories = array( 'S', 'Z', 'E', 'AE', 'K' );
		if ( ! in_array( $category, $allowed_categories, true ) ) {
			$result->add_error( 'tax.category.invalid', 'Die Steuerkategorie wird nicht unterstützt.', $category_path );
			return;
		}

		$zero_rate = (bool) preg_match( '/^0+(?:\.0+)?$/', (string) $rate );
		if ( 'S' === $category && $zero_rate ) {
			$result->add_error( 'tax.standard.rate.invalid', 'Normale Umsatzsteuer benötigt einen positiven Steuersatz.', $rate_path );
		}
		if ( 'S' !== $category && ! $zero_rate ) {
			$result->add_error( 'tax.exempt.rate.invalid', 'Diese Steuerkategorie muss mit 0 Prozent verwendet werden.', $rate_path );
		}
		if ( in_array( $category, array( 'E', 'AE', 'K' ), true ) && '' === $exemption_reason ) {
			$result->add_error( 'tax.exemption_reason.required', 'Für diese Steuerkategorie ist ein Befreiungsgrund erforderlich.', $reason_path );
		}
	}

	private function validate_party( Party $party, $path, ValidationResult $result ) {
		$required = array(
			'name' => $party->get_name(),
			'address_line' => $party->get_address_line(),
			'postal_code' => $party->get_postal_code(),
			'city' => $party->get_city(),
		);

		foreach ( $required as $field => $value ) {
			if ( '' === $value ) {
				$result->add_error( 'party.' . $field . '.required', 'Eine erforderliche Adressangabe fehlt.', $path . '.' . $field );
			}
		}

		if ( ! preg_match( '/^[A-Z]{2}$/', $party->get_country_code() ) ) {
			$result->add_error( 'party.country.invalid', 'Das Land muss als ISO-3166-1-Alpha-2-Code angegeben werden.', $path . '.country_code' );
		}

		if ( '' === $party->get_electronic_address() ) {
			$result->add_warning( 'party.electronic_address.missing', 'Für XRechnung muss eine elektronische Adresse ergänzt werden.', $path . '.electronic_address' );
		} elseif ( '' === $party->get_electronic_address_scheme() ) {
			$result->add_error( 'party.electronic_address_scheme.required', 'Zur elektronischen Adresse fehlt der Schema-Code.', $path . '.electronic_address_scheme' );
		}
	}
}
