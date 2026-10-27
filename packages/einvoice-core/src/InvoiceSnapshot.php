<?php

namespace PSource\EInvoice;

use DateTimeImmutable;
use InvalidArgumentException;

final class InvoiceSnapshot {

	private $invoice_number;
	private $type_code;
	private $issue_date;
	private $due_date;
	private $currency;
	private $seller;
	private $buyer;
	private $lines;
	private $tax_breakdowns;
	private $totals;
	private $buyer_reference;
	private $payment_means_code;
	private $payment_reference;
	private $iban;
	private $bic;
	private $source;
	private $delivery_address;
	private $service_start_date;
	private $service_end_date;
	private $payment_terms;
	private $references;
	private $allowances_charges;
	private $attachments;
	private $note;

	public function __construct( array $data ) {
		$this->invoice_number = self::text( $data, 'invoice_number' );
		$this->type_code = self::text( $data, 'type_code', '380' );
		$this->issue_date = self::date( $data, 'issue_date' );
		$this->due_date = self::optional_date( $data, 'due_date' );
		$this->currency = strtoupper( self::text( $data, 'currency', 'EUR' ) );
		$this->seller = self::party( $data, 'seller' );
		$this->buyer = self::party( $data, 'buyer' );
		$this->lines = self::objects( $data, 'lines', InvoiceLine::class );
		$this->tax_breakdowns = self::objects( $data, 'tax_breakdowns', TaxBreakdown::class );
		$this->totals = self::totals( $data );
		$this->buyer_reference = self::text( $data, 'buyer_reference' );
		$this->payment_means_code = self::text( $data, 'payment_means_code' );
		$this->payment_reference = self::text( $data, 'payment_reference' );
		$this->iban = self::text( $data, 'iban' );
		$this->bic = self::text( $data, 'bic' );
		$this->source = isset( $data['source'] ) && is_array( $data['source'] ) ? $data['source'] : array();
		$this->delivery_address = self::optional_object( $data, 'delivery_address', Address::class );
		$this->service_start_date = self::optional_date( $data, 'service_start_date' );
		$this->service_end_date = self::optional_date( $data, 'service_end_date' );
		$this->payment_terms = self::text( $data, 'payment_terms' );
		$this->references = self::objects( $data, 'references', DocumentReference::class );
		$this->allowances_charges = self::objects( $data, 'allowances_charges', AllowanceCharge::class );
		$this->attachments = self::objects( $data, 'attachments', Attachment::class );
		$this->note = self::text( $data, 'note' );
	}

	private static function text( array $data, $key, $default = '' ) {
		return isset( $data[ $key ] ) ? trim( (string) $data[ $key ] ) : $default;
	}

	private static function date( array $data, $key ) {
		$value = self::text( $data, $key );
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		$errors = DateTimeImmutable::getLastErrors();

		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
			throw new InvalidArgumentException( sprintf( 'Invoice field "%s" must use YYYY-MM-DD.', $key ) );
		}

		return $value;
	}

	private static function optional_date( array $data, $key ) {
		return '' === self::text( $data, $key ) ? '' : self::date( $data, $key );
	}

	private static function party( array $data, $key ) {
		if ( ! isset( $data[ $key ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Invoice party "%s" is required.', $key ) );
		}

		return $data[ $key ] instanceof Party
			? $data[ $key ]
			: new Party( $data[ $key ] );
	}

	private static function objects( array $data, $key, $class_name ) {
		$values = isset( $data[ $key ] ) && is_array( $data[ $key ] ) ? $data[ $key ] : array();
		$result = array();

		foreach ( $values as $value ) {
			$result[] = $value instanceof $class_name ? $value : new $class_name( $value );
		}

		return $result;
	}

	private static function optional_object( array $data, $key, $class_name ) {
		if ( ! isset( $data[ $key ] ) || array() === $data[ $key ] || null === $data[ $key ] ) {
			return null;
		}

		return $data[ $key ] instanceof $class_name ? $data[ $key ] : new $class_name( $data[ $key ] );
	}

	private static function totals( array $data ) {
		if ( ! isset( $data['totals'] ) ) {
			throw new InvalidArgumentException( 'Invoice totals are required.' );
		}

		return $data['totals'] instanceof InvoiceTotals
			? $data['totals']
			: new InvoiceTotals( $data['totals'] );
	}

	public function get_invoice_number() {
		return $this->invoice_number;
	}

	public function get_currency() {
		return $this->currency;
	}

	public function get_seller() {
		return $this->seller;
	}

	public function get_buyer() {
		return $this->buyer;
	}

	public function get_lines() {
		return $this->lines;
	}

	public function get_tax_breakdowns() {
		return $this->tax_breakdowns;
	}

	public function get_totals() {
		return $this->totals;
	}

	public function get_service_start_date() {
		return $this->service_start_date;
	}

	public function get_service_end_date() {
		return $this->service_end_date;
	}

	public function get_references() {
		return $this->references;
	}

	public function get_allowances_charges() {
		return $this->allowances_charges;
	}

	public function to_array() {
		return array(
			'invoice_number' => $this->invoice_number,
			'type_code' => $this->type_code,
			'issue_date' => $this->issue_date,
			'due_date' => $this->due_date,
			'currency' => $this->currency,
			'seller' => $this->seller->to_array(),
			'buyer' => $this->buyer->to_array(),
			'lines' => array_map(
				function ( InvoiceLine $line ) {
					return $line->to_array();
				},
				$this->lines
			),
			'tax_breakdowns' => array_map(
				function ( TaxBreakdown $tax ) {
					return $tax->to_array();
				},
				$this->tax_breakdowns
			),
			'totals' => $this->totals->to_array(),
			'buyer_reference' => $this->buyer_reference,
			'payment_means_code' => $this->payment_means_code,
			'payment_reference' => $this->payment_reference,
			'iban' => $this->iban,
			'bic' => $this->bic,
			'source' => $this->source,
			'delivery_address' => $this->delivery_address ? $this->delivery_address->to_array() : null,
			'service_start_date' => $this->service_start_date,
			'service_end_date' => $this->service_end_date,
			'payment_terms' => $this->payment_terms,
			'note' => $this->note,
			'references' => array_map(
				static function ( DocumentReference $reference ) {
					return $reference->to_array();
				},
				$this->references
			),
			'allowances_charges' => array_map(
				static function ( AllowanceCharge $item ) {
					return $item->to_array();
				},
				$this->allowances_charges
			),
			'attachments' => array_map(
				static function ( Attachment $attachment ) {
					return $attachment->to_array();
				},
				$this->attachments
			),
		);
	}
}
