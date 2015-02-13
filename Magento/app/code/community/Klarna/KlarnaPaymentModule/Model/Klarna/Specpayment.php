<?php
/**
 * Klarna_KlarnaPaymentModule_Model_Klarna_Specpayment
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
 * Spec payment specific overrides of the shared payment model
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Klarna_Specpayment
extends Klarna_KlarnaPaymentModule_Model_Klarna_Shared
{

    protected $_code = 'klarna_specpayment';

    protected $specialPClass = false;

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
     * Returns the predefined title for this payment option
     *
     * @return string The title for the payment option
     */
    public function getTitle()
    {
        $quote = $this->getInfoInstance()->getQuote();
        $country = $quote->getShippingAddress()->getCountry();

        $grandTotal = $quote->getGrandTotal();

        $helper = Mage::helper("klarnaPaymentModule/api");
        $helper->configureKiTT();
        try {
            $helper->loadConfig(
                $country,
                Mage::app()->getStore()->getId(),
                'klarna_specpayment'
            );
        } catch (Exception $e) {
            return $this->getConfigData('title');
        }


        if (strlen($country) == 0 || strlen($grandTotal) == 0) {
            $order = $this->getInfoInstance()->getOrder();
            $country = $order->getShippingAddress()->getCountry();
            $grandTotal = $order->getGrandTotal();
        }

        $cSettings = $helper->getCountrySettings($country);
        if (!is_array($cSettings)) {
            return $this->getConfigData('title');
        }

        $helper->setKlarnaInformation($cSettings);
        KiTT::setFormatter(Mage::helper("klarnaPaymentModule/format"));

        $kittTitle = KiTT::titleForSpec(
            KiTT::locale($cSettings["country"]),
            $helper->getPClassCollection($grandTotal, KiTT::SPEC)
        );
        $result = $kittTitle->getTitle();

        $routeName = Mage::app()
            ->getFrontController()
            ->getRequest()
            ->getRouteName();
        if ($routeName == "adminhtml") {
            return utf8_encode(html_entity_decode($result["title"]));
        }
        return $result["title"];
    }

    /**
     * Checks to see if there is any part payment option. If there is, we display the
     * option to the customer
     *
     * @param Mage_Sales_Model_Quote $quote Magento quote object
     *
     * @return bool True if the payment option should be visible, otherwise false
     */
    public function isAvailable($quote = null)
    {
        $storeId = Mage::app()->getStore()->getId();
        if (!Mage::getStoreConfig('klarna/payments/speccamp_enabled', $storeId)) {
            return false;
        }

        if (!parent::isAvailable($quote)) {
            return false;
        }

        $country = $quote->getShippingAddress()->getCountry();
        if (!$country) {
            return false;
        }

        $api = Mage::helper('klarnaPaymentModule/api');
        $cs = $api->getCountrySettings($country);
        if (!is_array($cs)) {
            return false;
        }

        try {
            $api->loadConfig(
                $country, $storeId, 'klarna_specpayment'
            );
        } catch (Exception $e) {
            return false;
        }

        $api->setKlarnaInformation($cs);

        if (!$api->isPClassesAvailable($quote->getGrandTotal())) {
            return false;
        }

        $this->specialPClass = $api->getPClassesFromCollection(
            $quote->getGrandTotal()
        );
        return true;
    }

}
