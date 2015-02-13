<?php
/**
 * File used to create the shared block for the Klarna solutions
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
 * Class used to create a shared block
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Klarna_Shared
    extends Mage_Payment_Block_Form
{

    /**
     * Render template html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->isOSCEnabled()) {
            $this->setTemplate('klarna/form-onestepcheckout.phtml');
            $variables = $this->assembleOSCTemplateVariables(
                $this->getMethod()
            );
            foreach ($variables as $key => $value) {
                $this->assign($key, $value);
            }
        } else {
            $this->setTemplate('klarna/form.phtml');
        }
        return parent::_toHtml();
    }

    /**
     * Check if OneStepCheckout is enabled
     *
     * @return bool
     */
    protected function isOSCEnabled()
    {
        return (bool)Mage::getStoreConfig(
            'onestepcheckout/general/rewrite_checkout_links',
            Mage::app()->getStore()->getId()
        );
    }

    /**
     * assemble all needed variables for the OneStepCheckout Template.
     *
     * @param object $paymentMethod instance of the paymentMethod being used
     *
     * @return array An associative array containing the values needed.
     */
    public function assembleOSCTemplateVariables($paymentMethod)
    {
        // --- From Payment Methods
        $quote = $paymentMethod->getInfoInstance()->getQuote();
        $code = $paymentMethod->getCode();

        // --- Mage Functions
        $session = Mage::getSingleton("checkout/session")->init('klarna');
        $khelper = Mage::helper('klarnaPaymentModule');
        $lang = Mage::helper('klarnaPaymentModule/lang');
        $api = Mage::helper("klarnaPaymentModule/api");
        $img_path = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);

        // --- From quote
        $taxClassId = $quote->getCustomerTaxClassId();

        // --- From api helper
        $klarna = $api->getKlarnaObject();

        // --- From khelper
        $address = $khelper->guessCustomerAddress($quote);
        $country = KlarnaCountry::getCode($klarna->getCountry());

        $fields = $khelper->getCountrySpecificFields($country);
        $fee = $khelper->getInvoiceFeeByCountry($country);
        $feeArray = $khelper->getInvoiceFeeArray($fee, $address, $taxClassId);
        $feeDisplayValue = $khelper->getInvoiceFeeDisplayValue($feeArray);
        $merchant_id = $khelper->getMerchantId($country);

        // --- From api
        $method = $api->getPaymentCode($code);

        // --- Basic setup always used
        $vars = array(
            'country' => $country,
            'merchant_id' => $merchant_id,
            'fee' => round($feeDisplayValue, 2),
            'img_path' => $img_path,
            'code' => $code,
            'method' => $method,
            'logo' => $khelper->getLogo($code, $country, $img_path)
        );

        // --- Personal number
        if (in_array('personalnumber', $fields)) {
            $vars['showPno'] = true;
            $vars['pno'] = $session->getData('klarna_pno');
        }
        // --- Gender variables
        if (in_array('gender', $fields)) {
            $vars['showGender'] = true;
            $vars['gender'] = $session->getData('klarna_gender');
        }

        // --- Date of Birth variables
        if (in_array('dob', $fields)) {
            $vars['dob'] = true;
            $vars['dob_year'] = $session->getData('klarna_dob_year');
            $vars['year_disabled'] = (strlen($vars['dob_year']) == 0);
            $vars['dob_day'] = $session->getData('klarna_dob_day');
            $vars['day_disabled'] = (strlen($vars['dob_year']) == 0);
            $vars['dob_month'] = $session->getData('klarna_dob_month');
            $vars['month_disabled'] = (strlen($vars['dob_year']) == 0);
        }

        // --- Labels
        $vars['dobLabel'] = $lang->fetch('birthday', $country);
        $vars['dayLabel'] = $lang->fetch('date_day', $country);
        $vars['monthLabel'] = $lang->fetch('date_month', $country);
        $vars['yearLabel'] = $lang->fetch('date_year', $country);
        $vars['genderLabel'] = $lang->fetch('sex', $country);
        $vars['maleLabel'] = $lang->fetch('sex_male', $country);
        $vars['femaleLabel'] = $lang->fetch('sex_female', $country);
        if ($code === "klarna_invoice") {
            $vars['pnoLabel'] = $lang->fetch(
                'klarna_personalOrOrganisatio_number', $country
            );
        } else {
            $vars['pnoLabel'] = $lang->fetch(
                'person_number', $country
            );
        }

        // --- PClasses
        $pcollection = KiTT::pclassCollection(
            $method,
            $klarna,
            $quote->getGrandTotal(),
            KlarnaFlags::CHECKOUT_PAGE
        );

        if (count($pcollection->pclasses) > 0) {
            $defaultPClass = $session->getData("{$code}_pclass");
            if (isset($defaultPClass)) {
                $pcollection->setDefault($defaultPClass);
            }
            $vars['pclasses'] = $pcollection->table();
        }

        // --- Consent input
        if (in_array('consent', $fields)) {
            $vars['consent'] = true;
            $vars['agblink'] = Mage::getStoreConfig(
                "klarna/de/agblink",
                Mage::app()->getStore()->getId()
            );
        }

        if ($country === "nl" && $code !== "klarna_invoice") {
            $vars['showLetOp'] = true;
        }

        // --- Terms
        $vars['terms'] = $this->_getTerms(
            $code, $country, $merchant_id, $feeDisplayValue
        );

        return $vars;
    }

    /**
     * Get the Terms javaScripts for OneStepCheckout
     *
     * @param string $methodCode  method code, Can be klarna_invoice,
     *                            klarna_partpayment or klarna_specpayment.
     * @param string $country     iso-alpha-2 code
     * @param int    $merchant_id store eid
     * @param float  $fee         invoice fee
     *
     * @return string Entire constructor for the Terms javascripts.
     */
    private function _getTerms($methodCode, $country, $merchant_id, $fee = 0)
    {
        $termFunc = '';
        $params = array(
            "el" => "{$methodCode}_terms",
            "country" => $country,
            "eid" => $merchant_id
        );
        switch ($methodCode) {
        case 'klarna_invoice':
            $termFunc = 'Invoice';
            $params["charge"] = $fee;
            break;
        case 'klarna_partpayment':
            $termFunc = 'Account';
            break;
        case 'klarna_specpayment':
            $termFunc = 'Special';
            break;
        }
        $params = json_encode($params);
        return "new Klarna.Terms.{$termFunc} ({$params});";
    }

    /**
     * Get the checkout HTML
     *
     * @param object $method Magento payment model
     *
     * @return string
     */
    public function getCheckoutHTML($method)
    {
        return Mage::helper('klarnaPaymentModule/api')->getCheckoutHTML($method);
    }

}

