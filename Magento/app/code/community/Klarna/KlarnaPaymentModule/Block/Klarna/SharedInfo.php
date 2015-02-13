<?php
/**
 * File used to create the shared info block for the Klarna solutions
 *
 * PHP Version 5.2
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class used to create a shared info block
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Klarna_SharedInfo
extends Mage_Payment_Block_Info
{

    /**
     * Render template html
     *
     * @return string
     */
    protected function _toHtml()
    {
        $payment = $this->getMethod();
        $info = $this->getInfo();

        $country = $this->_getCountryFromInfo($info);

        $mode = $this->_getIntegrationMode($info);

        $helper = Mage::helper("klarnaPaymentModule");
        $img_path = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
        $this->assign(
            "logo", $helper->getLogo($payment->getCode(), $country, $img_path)
        );

        $transactionNumber = $this->_getTransactionNumber($mode, $info);
        if (strlen($transactionNumber) > 0) {
            $lang = Mage::helper("klarnaPaymentModule/lang");
            $this->assign("showTransactionNumber", true);
            $this->assign(
                "label", $lang->fetch($this->_getTransactionLabel($mode), $country)
            );
            $this->assign("transactionNumber", $transactionNumber);
        }

        $invoiceUrl = $info->getAdditionalInformation("klarna_invoice_url");
        if ((Mage::getSingleton('admin/session')->isLoggedIn())
            && (strlen($invoiceUrl) > 0)
        ) {
            $this->assign("showInvoiceNumbers", true);
            $this->assign(
                "invoiceLabel", $lang->fetch("click_invoice_to_print", $country)
            );
            $this->assign(
                "invoiceNumbers",
                $this->_getInvoiceNumbers($transactionNumber, $invoiceUrl, $mode)
            );
        }

        $this->setTemplate('klarna/info.phtml');
        return parent::_toHtml();
    }

    /**
     * Get the invoice numbers to display for a activated Klarna Invoice
     *
     * @param string $invoiceUrl Klarna invoice url
     * @param string $mode       Klarna Integration mode
     *
     * @return array
     */
    private function _getInvoiceNumbers($transactionNumber, $invoiceUrl, $mode)
    {
        if ($mode == "advanced") {
            $invoiceUrl = unserialize($invoiceUrl);
            $invoiceNumbers = array();
            $liveMode = Mage::helper("klarnaPaymentModule/api")->getHost();
            $host = ($liveMode == Klarna::LIVE ? 'online' : 'beta-test');
            $uri = "https://{$host}.klarna.com/invoices/";
            foreach ($invoiceUrl as $invoiceNumber) {
                $link = KiTT_String::injectLink(
                    "_{$invoiceNumber[0]}_", "$uri{$invoiceNumber[0]}.pdf"
                );
                $invoiceNumbers[] = $link;
            }
            return $invoiceNumbers;
        }

        return array(KiTT_String::injectLink("_{$transactionNumber}_", $invoiceUrl));
    }

    /**
     * Get shipping country from the info instance
     *
     * @param object $info Magento info instance
     *
     * @return mixed
     */
    private function _getCountryFromInfo($info)
    {
        $order = $info->getOrder();
        if (isset($order)) {
            return $order->getShippingAddress()->getCountry();
        }

        $quote = $info->getQuote();
        if (isset($quote)) {
            return $quote->getShippingAddress()->getCountry();
        }

        Mage::throwException("Unable to find a country");
    }

    /**
     * Get the integration mode if its set on the info instance or take it from
     * the configuration
     *
     * @param object $info Magento info instance
     *
     * @return string
     */
    private function _getIntegrationMode($info)
    {
        $mode = $info->getAdditionalInformation("klarna_integrationmode");
        if (strlen($mode) > 0) {
            return $mode;
        }
        return Mage::getStoreConfig(
            "klarna/general/integration", Mage::app()->getStore()->getId()
        );
    }


    /**
     * Get the info message to display on PDFs
     *
     * @return string
     */
    public function toPdf()
    {
        return $this->getMethod()->getTitle();
    }

    /**
     * Get the transaction label to display
     *
     * @param string $mode Klarna integration mode
     *
     * @return string
     */
    private function _getTransactionLabel($mode)
    {
        switch($mode) {
        case "advanced":
            return "reservation_number_text";
        default:
            return "invoice_number_text";
        }
    }

    /**
     * Get the transaction number to display
     *
     * @param string $mode Klarna integration mode
     * @param object $info Magento info instance
     *
     * @return string
     */
    private function _getTransactionNumber($mode, $info)
    {
        switch ($mode) {
        case "advanced":
            return $info->getAdditionalInformation("klarna_reservation_id");
        case "standard":
            return $info->getAdditionalInformation('klarna_invoice_id');
        default:
            return "";
        }
    }

}
