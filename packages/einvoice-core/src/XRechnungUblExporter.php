<?php

namespace PSource\EInvoice;

use DateTime;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use horstoeko\invoicesuite\InvoiceSuiteBuiltInProviders;
use horstoeko\invoicesuite\InvoiceSuiteDocumentBuilder;
use horstoeko\invoicesuite\validators\InvoiceSuiteXsdDocumentValidator;

final class XRechnungUblExporter {

	public const FORMAT = 'ubl';
	public const PROFILE = 'xrechnung';
	public const VERSION = '3.0';

	public function validate( InvoiceSnapshot $invoice ) {
		$validation = ( new InvoiceValidator() )->validate( $invoice );
		$data = $invoice->to_array();
		if ( '380' !== $data['type_code'] ) {
			$validation->add_error(
				'xrechnung.type.unsupported',
				'Der Export unterstützt ausschließlich Rechnungen.',
				'type_code'
			);
		}
		foreach ( array( 'seller', 'buyer' ) as $party_key ) {
			if ( '' === $data[ $party_key ]['electronic_address'] || '' === $data[ $party_key ]['electronic_address_scheme'] ) {
				$validation->add_error(
					'xrechnung.party.electronic_address.required',
					'Für XRechnung werden elektronische Adresse und Schema benötigt.',
					$party_key . '.electronic_address'
				);
			}
		}
		if ( '' === $data['buyer_reference'] ) {
			$validation->add_error(
				'xrechnung.buyer_reference.required',
				'Für XRechnung wird eine Buyer Reference oder Leitweg-ID benötigt.',
				'buyer_reference'
			);
		}

		return $validation;
	}

	public function export( InvoiceSnapshot $invoice ) {
		$validation = $this->validate( $invoice );
		if ( ! $validation->is_valid() ) {
			throw new RuntimeException( 'E-invoice validation failed: ' . json_encode( $validation->get_errors(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) );
		}

		$data = $invoice->to_array();
		$builder = InvoiceSuiteDocumentBuilder::createByProviderUniqueId(
			InvoiceSuiteBuiltInProviders::XRECHNUNG_UBL_INVOICE
		);

		$builder
			->setDocumentNo( $data['invoice_number'] )
			->setDocumentType( $data['type_code'] )
			->setDocumentDate( self::date( $data['issue_date'] ) )
			->setDocumentCurrency( $data['currency'] )
			->setDocumentBuyerReference( $data['buyer_reference'] );
		if ( '' !== $data['note'] ) {
			$builder->setDocumentNote( $data['note'] );
		}

		if ( '' !== $data['service_start_date'] || '' !== $data['service_end_date'] ) {
			$builder->setDocumentBillingPeriod(
				self::optional_date( $data['service_start_date'] ),
				self::optional_date( $data['service_end_date'] )
			);
		}

		foreach ( $data['references'] as $reference ) {
			$reference_date = self::optional_date( $reference['issue_date'] );
			switch ( $reference['type'] ) {
				case 'order':
					$builder->setDocumentBuyerOrderReference( $reference['id'], $reference_date );
					break;
				case 'contract':
					$builder->setDocumentContractReference( $reference['id'], $reference_date );
					break;
				case 'project':
					$builder->setDocumentProjectReference( $reference['id'] );
					break;
				default:
					$builder->addDocumentAdditionalReference( $reference['id'], $reference_date );
			}
		}

		$this->map_party( $builder, $data['seller'], true );
		$this->map_party( $builder, $data['buyer'], false );

		if ( '' !== $data['iban'] ) {
			$builder->setDocumentPaymentMeanAsCreditTransferSepa(
				$data['iban'],
				$data['seller']['name'],
				null,
				'' !== $data['bic'] ? $data['bic'] : null,
				$data['payment_reference']
			);
		} else {
			$builder->addDocumentPaymentMean(
				$data['payment_means_code'],
				$data['payment_terms'],
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				$data['payment_reference']
			);
		}
		if ( '' !== $data['payment_terms'] || '' !== $data['due_date'] ) {
			$builder->setDocumentPaymentTerm(
				$data['payment_terms'],
				self::optional_date( $data['due_date'] )
			);
		}

		foreach ( $data['tax_breakdowns'] as $tax ) {
			$builder->addDocumentTax(
				$tax['category'],
				'VAT',
				self::number( $tax['taxable_amount'] ),
				self::number( $tax['tax_amount'] ),
				self::number( $tax['rate'] ),
				'' !== $tax['exemption_reason'] ? $tax['exemption_reason'] : null
			);
		}

		foreach ( $data['allowances_charges'] as $item ) {
			$builder->addDocumentAllowanceCharge(
				$item['is_charge'],
				self::number( $item['amount'] ),
				'' !== $item['base_amount'] ? self::number( $item['base_amount'] ) : null,
				$item['tax_category'],
				'VAT',
				'' !== $item['tax_rate'] ? self::number( $item['tax_rate'] ) : null,
				$item['reason'],
				'' !== $item['reason_code'] ? $item['reason_code'] : null
			);
		}

		foreach ( $data['lines'] as $line ) {
			$line_tax = Calculator::tax_amount( Amount::from_string( $line['net_amount'] ), $line['tax_rate'] );
			$line_allowance = Amount::zero();
			$line_charge = Amount::zero();

			$builder
				->addDocumentPosition( $line['id'] )
				->setDocumentPositionProductDetails( null, $line['name'], $line['name'] )
				->setDocumentPositionQuantities( self::number( $line['quantity'] ), $line['unit_code'] )
				->setDocumentPositionNetPrice(
					self::number( $line['unit_price'] ),
					self::number( $line['price_base_quantity'] ),
					$line['unit_code']
				)
				->setDocumentPositionTax(
					$line['tax_category'],
					'VAT',
					$line_tax->to_minor_units() > 0 ? self::number( $line_tax->to_string() ) : null,
					self::number( $line['tax_rate'] ),
					'' !== $line['tax_exemption_reason'] ? $line['tax_exemption_reason'] : null
				);

			foreach ( $line['allowances_charges'] as $item ) {
				$amount = Amount::from_string( $item['amount'] );
				if ( $item['is_charge'] ) {
					$line_charge = $line_charge->add( $amount );
				} else {
					$line_allowance = $line_allowance->add( $amount );
				}
				$builder->addDocumentPositionAllowanceCharge(
					$item['is_charge'],
					self::number( $item['amount'] ),
					'' !== $item['base_amount'] ? self::number( $item['base_amount'] ) : null,
					$item['reason'],
					'' !== $item['reason_code'] ? $item['reason_code'] : null
				);
			}

			$line_gross = Amount::from_string( $line['net_amount'] )->add( $line_tax );
			$builder->setDocumentPositionSummation(
				self::number( $line['net_amount'] ),
				self::number( $line_charge->to_string() ),
				self::number( $line_allowance->to_string() ),
				self::number( $line_tax->to_string() ),
				self::number( $line_gross->to_string() )
			);
		}

		$totals = $data['totals'];
		$builder->setDocumentSummation(
			self::number( $totals['line_net_amount'] ),
			self::number( $totals['charge_total'] ),
			self::number( $totals['allowance_total'] ),
			self::number( $totals['tax_exclusive_amount'] ),
			self::number( $totals['tax_amount'] ),
			null,
			self::number( $totals['tax_inclusive_amount'] ),
			self::number( $totals['payable_amount'] ),
			self::number( $totals['prepaid_amount'] ),
			0.0
		);

		$xml = $builder->getContent();
		$this->assert_xml_totals( $xml, $totals );
		$xsd_validation = InvoiceSuiteXsdDocumentValidator::createFromContent( $xml )->validate();
		if ( $xsd_validation->hasErrorOrInternalErrorMessagesInMessageBag() ) {
			throw new RuntimeException( 'Generated XRechnung XML failed UBL XSD validation.' );
		}

		return $xml;
	}

	private function map_party( InvoiceSuiteDocumentBuilder $builder, array $party, $seller ) {
		$prefix = $seller ? 'Seller' : 'Buyer';
		$builder->{ 'setDocument' . $prefix . 'Name' }( $party['name'] );
		$builder->{ 'setDocument' . $prefix . 'Address' }(
			$party['address_line'],
			null,
			null,
			$party['postal_code'],
			$party['city'],
			$party['country_code'],
			'' !== $party['region'] ? $party['region'] : null
		);
		$builder->{ 'setDocument' . $prefix . 'Communication' }(
			$party['electronic_address_scheme'],
			$party['electronic_address']
		);

		if ( '' !== $party['vat_id'] ) {
			$builder->{ 'setDocument' . $prefix . 'TaxRegistration' }( 'VA', $party['vat_id'] );
		}
		if ( '' !== $party['tax_id'] ) {
			$builder->{ 'addDocument' . $prefix . 'TaxRegistration' }( 'FC', $party['tax_id'] );
		}
		if ( '' !== $party['contact_name'] || '' !== $party['contact_phone'] || '' !== $party['contact_email'] ) {
			$method = $seller ? 'setDocumentSellerContact' : 'addDocumentBuyerContact';
			$builder->{$method}(
				$party['contact_name'],
				null,
				$party['contact_phone'],
				null,
				$party['contact_email']
			);
		}
	}

	private static function date( $value ) {
		return new DateTime( $value . ' 00:00:00' );
	}

	private static function optional_date( $value ) {
		return '' === $value ? null : self::date( $value );
	}

	private static function number( $value ) {
		if ( ! preg_match( '/^-?\d+(?:\.\d+)?$/', (string) $value ) ) {
			throw new RuntimeException( 'InvoiceSuite received an invalid decimal value.' );
		}

		return (float) $value;
	}

	private function assert_xml_totals( $xml, array $totals ) {
		$document = new DOMDocument();
		if ( ! $document->loadXML( $xml, LIBXML_NONET ) ) {
			throw new RuntimeException( 'InvoiceSuite generated invalid XML.' );
		}
		$xpath = new DOMXPath( $document );

		$expected = array(
			'LineExtensionAmount' => $totals['line_net_amount'],
			'TaxExclusiveAmount' => $totals['tax_exclusive_amount'],
			'TaxInclusiveAmount' => $totals['tax_inclusive_amount'],
			'PayableAmount' => $totals['payable_amount'],
		);
		foreach ( $expected as $local_name => $amount ) {
			$nodes = $xpath->query(
				"//*[local-name()='LegalMonetaryTotal']/*[local-name()='" . $local_name . "']"
			);
			if ( 0 === $nodes->length ) {
				throw new RuntimeException( 'Generated XML is missing ' . $local_name . '.' );
			}
			$actual = Amount::from_string( trim( $nodes->item( 0 )->textContent ) );
			if ( ! $actual->equals( Amount::from_string( $amount ) ) ) {
				throw new RuntimeException( 'Generated XML amount differs from the immutable snapshot: ' . $local_name );
			}
		}
	}
}
