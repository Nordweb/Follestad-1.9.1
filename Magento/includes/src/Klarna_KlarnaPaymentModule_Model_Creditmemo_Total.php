<?php
/**
 * Invoice fee Credit memo
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
 * Class to handle the invoice fee on a Credit memo
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Creditmemo_Total
extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    /**
     * Collect the order total
     *
     * @param object $creditmemo The Creditmemo instance to collect from
     *
     * @return Klarna_KlarnaPaymentModule_Model_Creditmemo_Total
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $method = $creditmemo->getOrder()->getPayment()->getMethodInstance();

        if ($method->getCode() != 'klarna_invoice') {
            return $this;
        }

        $info = $method->getInfoInstance();

        if (!$info) {
            return $this;
        }

        $invoiceFee =  $info->getAdditionalInformation('invoice_fee');
        $baseInvoiceFee =  $info->getAdditionalInformation('base_invoice_fee');

        if (!$invoiceFee) {
            return $this;
        }

        $creditmemo->setBaseGrandTotal(
            ($creditmemo->getBaseGrandTotal() + $baseInvoiceFee)
        );
        $creditmemo->setGrandTotal(
            ($creditmemo->getGrandTotal() + $invoiceFee)
        );

        $creditmemo->setBaseInvoiceFee($baseInvoiceFee);
        $creditmemo->setInvoiceFee($invoiceFee);
        return $this;
    }
}
