<?php
/**
 * Invoice fee pdf
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
 * Class to handle the invoice fee on Invoice pdfs
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Invoice_Pdf_Total
extends Mage_Sales_Model_Order_Pdf_Total_Default
{

    /**
     * Get array of arrays with totals information
     *
     * @return array
     */
    public function getTotalsForDisplay()
    {
        $fee = $this->getAmount();
        $order = $this->getOrder();
        $incl = $order->formatPriceTxt($fee['incl']);
        $excl = $order->formatPriceTxt($fee['excl']);
        if ($this->getAmountPrefix()) {
            $incl = $this->getAmountPrefix() . $incl;
            $excl = $this->getAmountPrefix() . $excl;
        }

        $storeId = Mage::app()->getStore()->getId();
        $vatOption = Mage::getStoreConfig("tax/sales_display/price", $storeId);
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $label = Mage::helper('sales')->__($this->getTitle()) . ':';
        $totals = array();
        /**
         * 1 : Show exluding tax
         * 2 : Show including tax
         * 3 : Show both
         */
        if ($vatOption == '1' || $vatOption == '3') {
            $total = array(
                'amount'    => $excl,
                'font_size' => $fontSize
            );
            $exclLabel = $label;
            if ($vatOption == '3') {
                $exclLabel .= ' (Excl.Tax)';
            }
            $total['label'] = $exclLabel;
            $totals[] = $total;
        }
        if ($vatOption == '2' || $vatOption == '3') {
            $total = array(
                'amount'    => $incl,
                'font_size' => $fontSize
            );
            $inclLabel = $label;
            if ($vatOption == '3') {
                $inclLabel .= ' (Incl.Tax)';
            }
            $total['label'] = $inclLabel;
            $totals[] = $total;
        }

        return $totals;
    }

    /**
     * Check if we can display total information in PDF
     *
     * @return bool
     */
    public function canDisplay()
    {
        $amount = $this->getAmount();
        return ($amount["incl"] !== 0);
    }

    /**
     * Get Total amount from source
     *
     * @return array
     */
    public function getAmount()
    {
        $payment =  $this->getOrder()->getPayment();
        $incl = $payment->getAdditionalInformation("invoice_fee");
        $excl = $payment->getAdditionalInformation("invoice_fee_exluding_vat");
        return array(
            'incl' => ($incl ? $incl : 0),
            'excl' => ($excl ? $excl : 0)
        );
    }

}
