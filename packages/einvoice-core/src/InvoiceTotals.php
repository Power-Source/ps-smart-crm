<?php

namespace PSource\EInvoice;

use InvalidArgumentException;

final class InvoiceTotals {

	private $line_net_amount;
	private $allowance_total;
	private $charge_total;
	private $tax_exclusive_amount;
	private $tax_amount;
	private $tax_inclusive_amount;
	private $prepaid_amount;
	private $payable_amount;

	public function __construct( array $data ) {
		$this->line_net_amount = self::amount( $data, 'line_net_amount' );
		$this->allowance_total = self::amount_or_zero( $data, 'allowance_total' );
		$this->charge_total = self::amount_or_zero( $data, 'charge_total' );
		$this->tax_exclusive_amount = self::amount( $data, 'tax_exclusive_amount' );
		$this->tax_amount = self::amount( $data, 'tax_amount' );
		$this->tax_inclusive_amount = self::amount( $data, 'tax_inclusive_amount' );
		$this->prepaid_amount = self::amount_or_zero( $data, 'prepaid_amount' );
		$this->payable_amount = self::amount( $data, 'payable_amount' );
	}

	private static function amount( array $data, $key ) {
		if ( ! isset( $data[ $key ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Invoice total "%s" is required.', $key ) );
		}

		return $data[ $key ] instanceof Amount
			? $data[ $key ]
			: Amount::from_string( $data[ $key ] );
	}

	private static function amount_or_zero( array $data, $key ) {
		return isset( $data[ $key ] ) ? self::amount( $data, $key ) : Amount::zero();
	}

	public function get_line_net_amount() {
		return $this->line_net_amount;
	}

	public function get_allowance_total() {
		return $this->allowance_total;
	}

	public function get_charge_total() {
		return $this->charge_total;
	}

	public function get_tax_exclusive_amount() {
		return $this->tax_exclusive_amount;
	}

	public function get_tax_amount() {
		return $this->tax_amount;
	}

	public function get_tax_inclusive_amount() {
		return $this->tax_inclusive_amount;
	}

	public function get_prepaid_amount() {
		return $this->prepaid_amount;
	}

	public function get_payable_amount() {
		return $this->payable_amount;
	}

	public function to_array() {
		return array(
			'line_net_amount' => $this->line_net_amount->to_string(),
			'allowance_total' => $this->allowance_total->to_string(),
			'charge_total' => $this->charge_total->to_string(),
			'tax_exclusive_amount' => $this->tax_exclusive_amount->to_string(),
			'tax_amount' => $this->tax_amount->to_string(),
			'tax_inclusive_amount' => $this->tax_inclusive_amount->to_string(),
			'prepaid_amount' => $this->prepaid_amount->to_string(),
			'payable_amount' => $this->payable_amount->to_string(),
		);
	}
}
