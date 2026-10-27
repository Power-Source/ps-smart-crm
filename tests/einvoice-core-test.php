<?php

use PSource\EInvoice\Amount;
use PSource\EInvoice\Calculator;
use PSource\EInvoice\InvoiceLine;
use PSource\EInvoice\InvoiceSnapshot;
use PSource\EInvoice\InvoiceValidator;
use PSource\EInvoice\XRechnungUblExporter;
use horstoeko\invoicesuite\validators\InvoiceSuiteXsdDocumentValidator;

define( 'ABSPATH', __DIR__ . '/' );
require_once dirname( __DIR__ ) . '/inc/einvoice/bootstrap.php';

function assert_true( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function create_valid_invoice_data() {
	$party = array(
		'name' => 'Beispiel GmbH',
		'address_line' => 'Musterstrasse 1',
		'postal_code' => '12345',
		'city' => 'Berlin',
		'country_code' => 'DE',
		'electronic_address' => 'rechnung@example.test',
		'electronic_address_scheme' => 'EM',
	);

	return array(
		'invoice_number' => 'RE-2026-0001',
		'type_code' => '380',
		'issue_date' => '2026-10-27',
		'due_date' => '2026-11-10',
		'currency' => 'EUR',
		'buyer_reference' => 'KUNDENREFERENZ-4711',
		'seller' => $party,
		'buyer' => array_merge( $party, array( 'name' => 'Kunde AG' ) ),
		'lines' => array(
			array(
				'id' => '1',
				'name' => 'Beratung',
				'quantity' => '1',
				'unit_code' => 'HUR',
				'unit_price' => '100.00',
				'net_amount' => '100.00',
				'tax_category' => 'S',
				'tax_rate' => '19',
			),
			array(
				'id' => '2',
				'name' => 'Dokumentation',
				'quantity' => '1',
				'unit_code' => 'HUR',
				'unit_price' => '50.00',
				'net_amount' => '50.00',
				'tax_category' => 'S',
				'tax_rate' => '19',
			),
		),
		'tax_breakdowns' => array(
			array(
				'category' => 'S',
				'rate' => '19',
				'taxable_amount' => '150.00',
				'tax_amount' => '28.50',
			),
		),
		'totals' => array(
			'line_net_amount' => '150.00',
			'tax_exclusive_amount' => '150.00',
			'tax_amount' => '28.50',
			'tax_inclusive_amount' => '178.50',
			'payable_amount' => '178.50',
		),
	);
}

function create_tax_case_invoice_data( $invoice_number, array $line_definitions ) {
	$data = create_valid_invoice_data();
	$data['invoice_number'] = $invoice_number;
	$data['lines'] = array();
	$taxable_by_key = array();
	$reason_by_key = array();
	$line_total = Amount::zero();

	foreach ( $line_definitions as $index => $definition ) {
		$net_amount = Amount::from_string( $definition['net_amount'] );
		$data['lines'][] = array(
			'id' => (string) ( $index + 1 ),
			'name' => $definition['name'],
			'quantity' => '1',
			'unit_code' => $definition['unit_code'] ?? 'C62',
			'unit_price' => $definition['net_amount'],
			'net_amount' => $definition['net_amount'],
			'tax_category' => $definition['category'],
			'tax_rate' => $definition['rate'],
			'tax_exemption_reason' => $definition['reason'] ?? '',
		);
		$key = $definition['category'] . '|' . $definition['rate'];
		$taxable_by_key[ $key ] = isset( $taxable_by_key[ $key ] )
			? $taxable_by_key[ $key ]->add( $net_amount )
			: $net_amount;
		$reason_by_key[ $key ] = $definition['reason'] ?? '';
		$line_total = $line_total->add( $net_amount );
	}

	$tax_total = Amount::zero();
	$data['tax_breakdowns'] = array();
	foreach ( $taxable_by_key as $key => $taxable_amount ) {
		list( $category, $rate ) = explode( '|', $key, 2 );
		$tax_amount = Calculator::tax_amount( $taxable_amount, $rate );
		$tax_total = $tax_total->add( $tax_amount );
		$data['tax_breakdowns'][] = array(
			'category' => $category,
			'rate' => $rate,
			'taxable_amount' => $taxable_amount->to_string(),
			'tax_amount' => $tax_amount->to_string(),
			'exemption_reason' => $reason_by_key[ $key ],
		);
	}

	$gross_total = $line_total->add( $tax_total );
	$data['totals'] = array(
		'line_net_amount' => $line_total->to_string(),
		'tax_exclusive_amount' => $line_total->to_string(),
		'tax_amount' => $tax_total->to_string(),
		'tax_inclusive_amount' => $gross_total->to_string(),
		'payable_amount' => $gross_total->to_string(),
	);

	return $data;
}

$sum = Amount::from_string( '0.10' )->add( Amount::from_string( '0.20' ) );
assert_true( '0.30' === $sum->to_string(), 'Decimal addition must be exact.' );

$invoice = new InvoiceSnapshot( create_valid_invoice_data() );
$result = ( new InvoiceValidator() )->validate( $invoice );
assert_true( $result->is_valid(), 'A consistent invoice must be valid.' );
assert_true( '178.50' === $invoice->to_array()['totals']['payable_amount'], 'Serialization must preserve exact amounts.' );

$extended_data = create_valid_invoice_data();
$extended_data['service_start_date'] = '2026-10-01';
$extended_data['service_end_date'] = '2026-10-31';
$extended_data['delivery_address'] = array(
	'name' => 'Kunde AG Warenannahme',
	'address_line' => 'Lagerstrasse 2',
	'postal_code' => '12345',
	'city' => 'Berlin',
	'country_code' => 'DE',
);
$extended_data['references'] = array(
	array(
		'type' => 'order',
		'id' => 'PO-4711',
		'issue_date' => '2026-09-20',
	),
);
$extended_data['attachments'] = array(
	array(
		'id' => 'timesheet',
		'filename' => 'timesheet.pdf',
		'mime_type' => 'application/pdf',
		'checksum' => str_repeat( 'a', 64 ),
	),
);
$extended_invoice = new InvoiceSnapshot( $extended_data );
assert_true( 'PO-4711' === $extended_invoice->to_array()['references'][0]['id'], 'Structured references must be serialized.' );
assert_true( 'DE' === $extended_invoice->to_array()['delivery_address']['country_code'], 'Delivery address must be serialized.' );

$discounted_line = Calculator::line_net_amount( '19.99', '3', '10', 'percentage' );
assert_true( '53.97' === $discounted_line->to_string(), 'Percentage discounts must be rounded half-up to cents.' );
$fixed_discount_line = Calculator::line_net_amount( '19.99', '2.5', '1.00', 'fixed' );
assert_true( '47.48' === $fixed_discount_line->to_string(), 'Fixed unit discounts must support decimal quantities.' );
$calculated_tax = Calculator::tax_amount( Amount::from_string( '53.97' ), '19' );
assert_true( '10.25' === $calculated_tax->to_string(), 'Tax calculation must be rounded half-up to cents.' );

$invalid_period = create_valid_invoice_data();
$invalid_period['service_start_date'] = '2026-10-31';
$invalid_period['service_end_date'] = '2026-10-01';
$invalid_period_result = ( new InvoiceValidator() )->validate(
	new InvoiceSnapshot( $invalid_period )
);
assert_true( ! $invalid_period_result->is_valid(), 'An inverted service period must be rejected.' );

$xml = ( new XRechnungUblExporter() )->export( $invoice );
$xml_document = new DOMDocument();
assert_true( $xml_document->loadXML( $xml, LIBXML_NONET ), 'XRechnung export must produce well-formed XML.' );
assert_true( 'Invoice' === $xml_document->documentElement->localName, 'The first export syntax must be UBL Invoice.' );
assert_true( str_contains( $xml, 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0' ), 'The XML must identify the XRechnung 3.0 profile.' );
$xsd_validation = InvoiceSuiteXsdDocumentValidator::createFromContent( $xml )->validate();
assert_true( ! $xsd_validation->hasErrorOrInternalErrorMessagesInMessageBag(), 'Generated XRechnung XML must pass UBL XSD validation.' );

$tax_cases = array(
	'seven-percent' => array(
		'expected_tax' => '7.00',
		'lines' => array(
			array( 'name' => 'Lebensmittel', 'net_amount' => '100.00', 'category' => 'S', 'rate' => '7' ),
		),
	),
	'multiple-rates' => array(
		'expected_tax' => '26.00',
		'lines' => array(
			array( 'name' => 'Beratung', 'net_amount' => '100.00', 'category' => 'S', 'rate' => '19', 'unit_code' => 'HUR' ),
			array( 'name' => 'Fachbuch', 'net_amount' => '100.00', 'category' => 'S', 'rate' => '7' ),
		),
	),
	'zero-rate' => array(
		'expected_tax' => '0.00',
		'lines' => array(
			array( 'name' => 'Nullbesteuerte Leistung', 'net_amount' => '100.00', 'category' => 'Z', 'rate' => '0' ),
		),
	),
	'small-business' => array(
		'expected_tax' => '0.00',
		'lines' => array(
			array( 'name' => 'Leistung nach Paragraf 19', 'net_amount' => '100.00', 'category' => 'E', 'rate' => '0', 'reason' => 'Steuerbefreiung nach § 19 UStG' ),
		),
	),
	'tax-exempt' => array(
		'expected_tax' => '0.00',
		'lines' => array(
			array( 'name' => 'Steuerfreie Leistung', 'net_amount' => '100.00', 'category' => 'E', 'rate' => '0', 'reason' => 'Steuerbefreiter Umsatz' ),
		),
	),
	'reverse-charge' => array(
		'expected_tax' => '0.00',
		'lines' => array(
			array( 'name' => 'Reverse-Charge-Leistung', 'net_amount' => '100.00', 'category' => 'AE', 'rate' => '0', 'reason' => 'Steuerschuldnerschaft des Leistungsempfängers nach § 13b UStG' ),
		),
	),
	'intra-community' => array(
		'expected_tax' => '0.00',
		'lines' => array(
			array( 'name' => 'Innergemeinschaftliche Lieferung', 'net_amount' => '100.00', 'category' => 'K', 'rate' => '0', 'reason' => 'Steuerfreie innergemeinschaftliche Lieferung' ),
		),
	),
);

foreach ( $tax_cases as $case_name => $case ) {
	$case_data = create_tax_case_invoice_data( 'RE-' . strtoupper( $case_name ), $case['lines'] );
	$case_invoice = new InvoiceSnapshot( $case_data );
	$case_validation = ( new InvoiceValidator() )->validate( $case_invoice );
	assert_true( $case_validation->is_valid(), 'Tax case "' . $case_name . '" must pass internal validation.' );
	assert_true( $case['expected_tax'] === $case_invoice->to_array()['totals']['tax_amount'], 'Tax case "' . $case_name . '" must calculate the expected tax.' );
	$case_xml = ( new XRechnungUblExporter() )->export( $case_invoice );
	$case_xsd_validation = InvoiceSuiteXsdDocumentValidator::createFromContent( $case_xml )->validate();
	assert_true( ! $case_xsd_validation->hasErrorOrInternalErrorMessagesInMessageBag(), 'Tax case "' . $case_name . '" must pass UBL XSD validation.' );
}

$invalid_tax_rule = create_tax_case_invoice_data(
	'RE-INVALID-TAX-RULE',
	array(
		array( 'name' => 'Unzulässiges Reverse Charge', 'net_amount' => '100.00', 'category' => 'AE', 'rate' => '19', 'reason' => 'Reverse Charge' ),
	)
);
$invalid_tax_rule_result = ( new InvoiceValidator() )->validate( new InvoiceSnapshot( $invalid_tax_rule ) );
assert_true( ! $invalid_tax_rule_result->is_valid(), 'An exempt category with a positive tax rate must be rejected.' );

$missing_exemption_reason = create_tax_case_invoice_data(
	'RE-MISSING-EXEMPTION',
	array(
		array( 'name' => 'Unbegründete Befreiung', 'net_amount' => '100.00', 'category' => 'E', 'rate' => '0' ),
	)
);
$missing_exemption_result = ( new InvoiceValidator() )->validate( new InvoiceSnapshot( $missing_exemption_reason ) );
assert_true( ! $missing_exemption_result->is_valid(), 'An exempt category without an exemption reason must be rejected.' );

$mismatched_breakdown = create_tax_case_invoice_data(
	'RE-MISMATCHED-BREAKDOWN',
	array(
		array( 'name' => 'Ermäßigter Umsatz', 'net_amount' => '100.00', 'category' => 'S', 'rate' => '7' ),
	)
);
$mismatched_breakdown['tax_breakdowns'][0]['rate'] = '19';
$mismatched_breakdown_result = ( new InvoiceValidator() )->validate( new InvoiceSnapshot( $mismatched_breakdown ) );
assert_true( ! $mismatched_breakdown_result->is_valid(), 'A tax breakdown assigned to the wrong rate group must be rejected.' );

$invalid_data = create_valid_invoice_data();
$invalid_data['totals']['payable_amount'] = '178.49';
$invalid_result = ( new InvoiceValidator() )->validate( new InvoiceSnapshot( $invalid_data ) );
assert_true( ! $invalid_result->is_valid(), 'An inconsistent payable amount must be rejected.' );

$invalid_amount_rejected = false;
try {
	Amount::from_string( '10.999' );
} catch ( InvalidArgumentException $exception ) {
	$invalid_amount_rejected = true;
}
assert_true( $invalid_amount_rejected, 'Amounts with more than two decimals must be rejected.' );

$zero_quantity_rejected = false;
try {
	$invalid_line = create_valid_invoice_data()['lines'][0];
	$invalid_line['quantity'] = '0.0000';
	new InvoiceLine( $invalid_line );
} catch ( InvalidArgumentException $exception ) {
	$zero_quantity_rejected = true;
}
assert_true( $zero_quantity_rejected, 'A zero quantity must be rejected without float conversion.' );

$overflow_rejected = false;
try {
	Amount::from_string( (string) PHP_INT_MAX );
} catch ( OverflowException $exception ) {
	$overflow_rejected = true;
}
assert_true( $overflow_rejected, 'Amounts outside the integer minor-unit range must be rejected.' );

echo "E-invoice core tests passed.\n";
