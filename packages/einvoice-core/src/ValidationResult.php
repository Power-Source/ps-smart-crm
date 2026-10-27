<?php

namespace PSource\EInvoice;

final class ValidationResult {

	private $errors = array();
	private $warnings = array();

	public function add_error( $code, $message, $path = '' ) {
		$this->errors[] = array(
			'code' => (string) $code,
			'message' => (string) $message,
			'path' => (string) $path,
		);
	}

	public function add_warning( $code, $message, $path = '' ) {
		$this->warnings[] = array(
			'code' => (string) $code,
			'message' => (string) $message,
			'path' => (string) $path,
		);
	}

	public function is_valid() {
		return empty( $this->errors );
	}

	public function get_errors() {
		return $this->errors;
	}

	public function get_warnings() {
		return $this->warnings;
	}

	public function to_array() {
		return array(
			'valid' => $this->is_valid(),
			'errors' => $this->errors,
			'warnings' => $this->warnings,
		);
	}
}
