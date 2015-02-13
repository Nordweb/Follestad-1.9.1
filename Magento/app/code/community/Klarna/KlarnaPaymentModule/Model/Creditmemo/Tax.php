<?php
/**
 * Invoice fee tax Credit memo
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
 * Class to handle the invoice fee tax on a Credit memo
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Creditmemo_Tax
extends Mage_Sales_Model_Order_Creditmemo_Total_Tax
{

    /**
     * Collect the order total
     *
     * @param object $creditmemo The Creditmemo instance to collect from
     *
     * @return Klarna_KlarnaPaymentModule_Model_Creditmemo_Tax
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

        $tax =  $info->getAdditionalInformation('invoice_tax_amount');
        $baseTax = $info->getAdditionalInformation('base_invoice_tax_amount');

        if (!$tax) {
            return $this;
        }

        $creditmemo->setBaseTaxAmount(
            $creditmemo->getBaseTaxAmount() + $baseTax
        );
        $creditmemo->setTaxAmount(
            $creditmemo->getTaxAmount() + $tax
        );

        return $this;
    }
}
