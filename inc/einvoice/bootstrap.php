<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload = dirname( __DIR__, 2 ) . '/vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	throw new RuntimeException( 'PS Smart Business E-invoice dependencies are not installed.' );
}

require_once $autoload;
require_once __DIR__ . '/class-document-metadata-repository.php';
require_once __DIR__ . '/class-customer-data-repository.php';
require_once __DIR__ . '/class-line-classification.php';
require_once __DIR__ . '/class-crm-adapter.php';
require_once __DIR__ . '/class-crm-exporter.php';
