<?php
/**
 * Klarna_KlarnaPaymentModule_Model_Klarna_Invoice
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   Klarna_Module_Magento
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * Invoice specific overrides of the shared payment model
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Klarna_Invoice
extends Klarna_KlarnaPaymentModule_Model_Klarna_Shared
{

    protected $_code = 'klarna_invoice';

    /**
     * Check if module is enabled, then call parent for more checks.
     *
     * @param object $quote optional quote object
     *
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        $storeId = Mage::app()->getStore()->getId();
        if (!Mage::getStoreConfig('klarna/payments/invoice_enabled', $storeId)) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     */
    public function canCapturePartial()
    {
        return ($this->getIntegrationMode() == 'advanced');
    }

    /**
     * Get Invoice fee
     *
     * @param Mage_Customer_Model_Address_Abstract $address address object
     * @param int                                  $classId class id
     *
     * @return array
     */
    public function getAddressInvoiceFee(
        Mage_Customer_Model_Address_Abstract $address, $classId = null
    ) {
        $helper = Mage::helper('klarnaPaymentModule');
        $base = $helper->getInvoiceFeeByCountry($address->getCountry());
        if ($base > 0) {
            return $helper->getInvoiceFeeArray($base, $address, $classId);
        }
        return null;
    }

    /**
     * Returns the predefined title for this payment option
     *
     * @return string The title for the payment option
     */
    public function getTitle()
    {
        $info = $this->getInfoInstance();
        $quote = $info->getQuote();
        if (isset($quote)) {
            $address = $quote->getShippingAddress();
            $country = $address->getCountry();
            $classId = $quote->getCustomerTaxClassId();
        } else {
            $order = $info->getOrder();
            $address = $order->getShippingAddress();
            $country = $address->getCountry();
        }

        $feeArray = $this->getAddressInvoiceFee($address, $classId);

        $fee = 0;
        if (is_array($feeArray)) {
            $helper = Mage::helper("klarnaPaymentModule");
            $fee = $helper->getInvoiceFeeDisplayValue($feeArray);
        }

        Mage::helper("klarnaPaymentModule/api")->configureKiTT();
        KiTT::setFormatter(Mage::helper("klarnaPaymentModule/format"));

        $kittTitle = KiTT::titleForInvoice(
            KiTT::locale($country),
            array("fee" => $fee)
        );
        $result = $kittTitle->getTitle();

        return $result["title"];
    }

}
