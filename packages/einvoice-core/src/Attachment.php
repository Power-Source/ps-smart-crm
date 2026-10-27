<?php

namespace PSource\EInvoice;

use InvalidArgumentException;

final class Attachment {

	private $id;
	private $filename;
	private $mime_type;
	private $description;
	private $checksum;
	private $external_reference;

	public function __construct( array $data ) {
		$this->id = self::text( $data, 'id' );
		$this->filename = self::text( $data, 'filename' );
		$this->mime_type = strtolower( self::text( $data, 'mime_type' ) );
		$this->description = self::text( $data, 'description' );
		$this->checksum = strtolower( self::text( $data, 'checksum' ) );
		$this->external_reference = self::text( $data, 'external_reference' );

		if ( '' !== $this->checksum && ! preg_match( '/^[a-f0-9]{64}$/', $this->checksum ) ) {
			throw new InvalidArgumentException( 'Attachment checksum must be a SHA-256 hash.' );
		}
	}

	private static function text( array $data, $key ) {
		return isset( $data[ $key ] ) ? trim( (string) $data[ $key ] ) : '';
	}

	public function to_array() {
		return array(
			'id' => $this->id,
			'filename' => $this->filename,
			'mime_type' => $this->mime_type,
			'description' => $this->description,
			'checksum' => $this->checksum,
			'external_reference' => $this->external_reference,
		);
	}
}
