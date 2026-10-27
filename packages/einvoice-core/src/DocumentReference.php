<?php

namespace PSource\EInvoice;

use DateTimeImmutable;
use InvalidArgumentException;

final class DocumentReference {

	private $type;
	private $id;
	private $issue_date;

	public function __construct( array $data ) {
		$this->type = strtolower( trim( (string) ( $data['type'] ?? '' ) ) );
		$this->id = trim( (string) ( $data['id'] ?? '' ) );
		$this->issue_date = trim( (string) ( $data['issue_date'] ?? '' ) );

		if ( '' !== $this->issue_date ) {
			$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $this->issue_date );
			$errors = DateTimeImmutable::getLastErrors();
			if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
				throw new InvalidArgumentException( 'Document reference date must use YYYY-MM-DD.' );
			}
		}
	}

	public function get_type() {
		return $this->type;
	}

	public function get_id() {
		return $this->id;
	}

	public function to_array() {
		return array(
			'type' => $this->type,
			'id' => $this->id,
			'issue_date' => $this->issue_date,
		);
	}
}
