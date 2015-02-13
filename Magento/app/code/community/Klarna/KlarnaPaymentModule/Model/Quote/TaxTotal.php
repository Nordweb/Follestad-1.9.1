<?php
/**
 * Invoice fee tax address quote
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class to handle the invoice fee tax on a address quote
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Quote_TaxTotal
extends Mage_Sales_Model_Quote_Address_Total_Tax
{

    /**
     * Collect the order total
     *
     * @param object $address The address instance to collect from
     *
     * @return Klarna_KlarnaPaymentModule_Model_Quote_TaxTotal
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();
        if (($quote->getId() == null)
            || ($address->getAddressType() != "shipping")
        ) {
            return $this;
        }

        $payment = $quote->getPayment();
        if (($payment->getMethod() != 'klarna_invoice')
            && (!count($quote->getPaymentsCollection())
            || (!$payment->hasMethodInstance()))
        ) {
            return $this;
        }

        $methodInstance = $payment->getMethodInstance();

        if ($methodInstance->getCode() != 'klarna_invoice') {
            return $this;
        }
        $fee = $methodInstance->getAddressInvoiceFee($address);

        if (!is_array($fee)) {
            return $this;
        }
        $address->setTaxAmount($address->getTaxAmount() + $fee['taxamount']);
        $address->setBaseTaxAmount(
            $address->getBaseTaxAmount() + $fee['base_taxamount']
        );

        $address->setInvoiceTaxAmount($fee['taxamount']);
        $address->setBaseInvoiceTaxAmount($fee['base_taxamount']);
        return $this;
    }

}
