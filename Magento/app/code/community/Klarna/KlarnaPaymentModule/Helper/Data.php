<?php
/**
 * Klarna_KlarnaPaymentModule_Helper_Data
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
 * Data helper class
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Helper_Data
    extends Mage_Core_Helper_Abstract
{

    const NONKLARNA = -1;

    /**
     * Get logo class
     *
     * @param string $methodCode method code, Can be klarna_invoice,
     *                           klarna_partpayment or klarna_specpayment.
     * @param string $country    iso-alpha-2 code
     * @param string $img_path   path to magentos image dir
     *
     * @return string Entire uri to the logo for the given methodCode
     */
    public function getLogo($methodCode, $country, $img_path = "")
    {
        $country = strtolower($country);
        switch($methodCode) {
        case 'klarna_invoice':
            return "klarna_logo_invoice_{$country}";
        case 'klarna_partpayment':
            return "klarna_logo_account_{$country}";
        case 'klarna_specpayment':
            return "klarna_logo_special_{$country}";
        }
        return "";
    }

    /**
     * Guess the Klarna Country code constant to use
     *
     * @param object $address Magento Address object [optional]
     *
     * @return int
     */
    public function guessCountryCode($address = null)
    {
        if ($address instanceof Mage_Customer_Model_Address) {
            $countryCode = KlarnaCountry::fromCode($address->getCountry());
            if ($countryCode === null) {
                return self::NONKLARNA;
            }
            return $countryCode;
        }

        $countryCode = $this->_getCountryForCurrency(
            KlarnaCurrency::fromCode(
                Mage::app()->getStore()->getCurrentCurrencyCode()
            )
        );
        if ($countryCode === null) {
            $defaultCountry = KlarnaCountry::fromCode(
                Mage::getStoreConfig(
                    'general/country/default', Mage::app()->getStore()->getId()
                )
            );
            if ($defaultCountry === null) {
                return self::NONKLARNA;
            }
            return $defaultCountry;
        }
        return $countryCode;
    }

    /**
     * Guess the Customer address
     *
     * @param object $quote Magento Quote Object
     *
     * @return object Magento Address Object
     */
    public function guessCustomerAddress($quote = null)
    {
        if (isset($quote)) {
            return $quote->getCustomer()->getPrimaryShippingAddress();
        }

        return Mage::getSingleton('customer/session')
            ->getCustomer()
            ->getPrimaryShippingAddress();
    }

    /**
     * Get a country code constant for a currency constant
     *
     * @param int $currency KlarnaCurrency constant
     *
     * @return int
     */
    private function _getCountryForCurrency($currency)
    {
        switch ($currency) {
        case KlarnaCurrency::SEK:
            return KlarnaCountry::SE;
        case KlarnaCurrency::DKK:
            return KlarnaCountry::DK;
        case KlarnaCurrency::NOK:
            return KlarnaCountry::NO;
        default:
            return null;
        }
    }


    /**
     * Retrieve the merchant id for the given country.
     *
     * @param string|int $country iso-alpha-2 country code or KlarnaCountry
     *                            constant.
     *
     * @return int merchant ID for the specifieod country
     */
    public function getMerchantId($country)
    {
        if (is_int($country)) {
            $country = KlarnaCountry::getCode($country);
        }
        $country = strtolower($country);
        return $this->merchantid = (int) Mage::getStoreConfig(
            "klarna/{$country}/merchant_id",
            Mage::app()->getStore()->getId()
        );
    }

    /**
     * Get the country used on the quote.
     *
     * @return string iso-alpha-2 country code.
     */
    function getCountry()
    {
        return $this->getQuote()->getShippingAddress()->getCountry();
    }

    /**
     * Get an array of input fields required for the specific countries. Needed
     * for OneStepCheckout.
     *
     * @param string $country iso-alpha-2 code
     *
     * @return array
     */
    function getCountrySpecificFields($country)
    {
        $country = strtolower($country);
        $array = array();
        switch($country) {
        case "de":
            $array[] = 'consent';
        case "nl":
            $array[] = 'gender';
            $array[] = 'dob';
            return $array;
        case "se":
            return array("ssn");
        default:
            return array("personalnumber");
        }
    }

    /**
     * is the country a Klarna country?
     *
     * @param string $country country iso-alpha-2 code
     *
     * @return boolean
     */
    public function isCountryKlarna($country)
    {
        $countries = array('NO', 'SE', 'DK', 'NL', 'DE', 'FI');
        return in_array(strtoupper($country), $countries);
    }

    /**
     * Get the tax class used.
     *
     * @return int
     */
    public function getTaxClass()
    {
        return (int) Mage::getStoreConfig(
            'klarna/general/tax_class', Mage::app()->getStore()->getId()
        );
    }

    /**
     * Get the invoice fee configuration value by country
     *
     * @param string $country The country iso to fetch for
     *
     * @return float
     */
    public function getInvoiceFeeByCountry($country)
    {
        $country = strtolower($country);
        return Mage::getStoreConfig(
            "klarna/{$country}/invoice_fee", Mage::app()->getStore()->getId()
        );
    }

    /**
     * Get the Integration mode used
     *
     * @return string
     */
    public function getIntegrationMode()
    {
        return Mage::getStoreConfig(
            'klarna/general/integration', Mage::app()->getStore()->getId()
        );
    }

    /**
     * Will return the invoice fee formatted according to the tax settings
     * in magento
     *
     * @param float   $base       The initial invoice fee to use taken from the
     *                            settings
     * @param Address $address    A Magento address object
     * @param int     $taxClassId integer ID of the tax class used for the
     *                            invoice.
     *
     * @return array
     */
    public function getInvoiceFeeArray($base, $address, $taxClassId)
    {
        //Get the correct rate to use
        $store = Mage::app()->getStore();
        $calc = Mage::getSingleton('tax/calculation');
        $rateRequest = $calc->getRateRequest(
            $address, $address, $taxClassId, $store
        );
        $taxClass = $this->getTaxClass();
        $rateRequest->setProductClassId($taxClass);
        $rate = $calc->getRate($rateRequest);

        //Get the vat display options for products from Magento tax settings
        $VatOptions = Mage::getStoreConfig(
            "tax/calculation/price_includes_tax", $store->getId()
        );

        if ($VatOptions == 1) {
            //Catalog prices are set to include taxes
            $value = $calc->calcTaxAmount($base, $rate, true, false);
            $excl = ($base - $value);
            return array(
                'excl' => $excl,
                'base_excl' => $this->calcBaseValue($excl),
                'incl' => $base,
                'base_incl' => $this->calcBaseValue($base),
                'taxamount' => $value,
                'base_taxamount' => $this->calcBaseValue($value),
                'rate' => $rate
            );
        }
        //Catalog prices are set to exclude taxes
        $value = $calc->calcTaxAmount($base, $rate, false, false);
        $incl = ($base + $value);

        return array(
            'excl' => $base,
            'base_excl' => $this->calcBaseValue($base),
            'incl' => $incl,
            'base_incl' => $this->calcBaseValue($incl),
            'taxamount' => $value,
            'base_taxamount' => $this->calcBaseValue($value),
            'rate' => $rate
        );
    }

    /**
     * Try to calculate the value of the invoice fee with the base currency
     * of the store if the purchase was done with a different currency.
     *
     * @param float $value value to calculate on
     *
     * @return float
     */
    protected function calcBaseValue($value)
    {
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($currentCurrencyCode !== $baseCurrencyCode) {
            $currencyModel = Mage::getModel('directory/currency');
            $currencyRates = $currencyModel->getCurrencyRates(
                $baseCurrencyCode, array($currentCurrencyCode)
            );
            return ($value / $currencyRates[$currentCurrencyCode]);
        }
        return $value;
    }

    /**
     * Returns the value to be displayed in the frontend according to either
     * settings in OneStepCheckout or Magento
     *
     * @param array $feeArray formatted array recieved from getAddressInvoiceFee
     *
     * @return float
     */
    public function getInvoiceFeeDisplayValue($feeArray)
    {
        $storeId = Mage::app()->getStore()->getId();
        $isOSCEnabled = Mage::getStoreConfig(
            'onestepcheckout/general/rewrite_checkout_links', $storeId
        );
        if ($isOSCEnabled) {
            $OSCDisplayAmountsInclTax = Mage::getStoreConfig(
                'onestepcheckout/general/display_tax_included', $storeId
            );
            if (Mage::getStoreConfig(
                'onestepcheckout/general/display_tax_included', $storeId
            )) {
                //OneStepCheckout displays their products including taxes
                return $feeArray['incl'];
            }
            //OneStepCheckout displays their products excluding taxes
            return $feeArray['excl'];
        }

        /**
         * 1: Display excluding VAT
         * 2: Display including VAT
         * 3: Display both
         */
        if (Mage::getStoreConfig("tax/sales_display/price", $storeId) == 1) {
            //Display options are set to display only excluding prices
            return $feeArray['excl'];
        }
        //Display settings are set to either show including or both
        //including and excluding.
        return $feeArray['incl'];
    }
}
