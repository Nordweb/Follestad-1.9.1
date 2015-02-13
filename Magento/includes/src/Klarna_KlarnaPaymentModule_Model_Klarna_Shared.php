<?php
/**
 * Class with shared functions
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
 * Shared Klarna Functions
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Klarna_Shared
extends Mage_Payment_Model_Method_Abstract
{

    protected $_formBlockType = 'klarnaPaymentModule/klarna_shared';
    protected $_infoBlockType = 'klarnaPaymentModule/klarna_sharedInfo';

    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;

    const SHIPPING = "shipping";
    const BILLING = "billing";

    protected $info;

    protected $data;

    protected $shippingaddress;

    protected $billingaddress;

    protected $method;

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Assign data to info model instance
     *
     * @param mixed $data data to assign
     *
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $this->data = $data;
        if (!($this->data instanceof Varien_Object)) {
            $this->data = new Varien_Object($this->data);
        }
        $isOSCEnabled = Mage::getStoreConfig(
            'onestepcheckout/general/rewrite_checkout_links',
            Mage::app()->getStore()->getId()
        );
        $this->info = $this->getInfoInstance();
        $quote = $this->info->getQuote();
        $this->shippingaddress = $quote->getShippingAddress();
        $this->billingaddress = $quote->getBillingAddress();
        $this->method = $this->data->getMethod();
        $this->info->setAdditionalInformation(
            "klarna_reference", $this->data[$this->method . "_reference"]
        );

        // Set the phone number used in the checkout
        $phoneNumber = str_replace(
            array('/', '.', "_", ',', ':', ';', ' ', '-', '\\'),
            "",
            $this->data[$this->method . '_phonenumber']
        );
        $this->info->setAdditionalInformation("klarna_phonenumber", $phoneNumber);
        // Set the personal number used in the checkout.
        $this->info->setAdditionalInformation(
            'klarna_personalnumber', $this->data[$this->method . "_personalnumber"]
        );
        // Set the gender of the customer
        $this->info->setAdditionalInformation(
            'klarna_gender', $this->data[$this->method . "_gender"]
        );
        $this->info->setAdditionalInformation(
            'klarna_dob_day', $this->data[$this->method . "_dob_day"]
        );
        $this->info->setAdditionalInformation(
            'klarna_dob_month', $this->data[$this->method . "_dob_month"]
        );
        $this->info->setAdditionalInformation(
            'klarna_dob_year', $this->data[$this->method . "_dob_year"]
        );

        // Handle the information for OnepageCheckout
        if ($isOSCEnabled) {
            $this->_setOSCAddress();
        } else {
            // We would have recevied an address key with the getAddress call
            // from the checkout
            $country = $this->shippingaddress->getCountry();
            if ($country == "SE") {
                $this->_setSwedishAddress();
            } else {
                // For other countries than sweden, update the shipping address with
                // the information used in the checkout
                $this->_setShippingAddress();

                // Update the shipping address with specifics needed for German and
                // Dutch customers and copy shipping to billing because they must
                // be the same
                if (in_array($country, array("DE", "NL"))) {
                    $this->_setGermanOrDutchAddress();
                }
            }
        }

        // Set PClass information
        $this->_setPClassInformation();

        //Create a klarna address object used for the shipping address
        $this->_setAdditionalKlarnaInformation(self::SHIPPING);
        $this->_setAdditionalKlarnaInformation(self::BILLING);

        return $this;
    }

    /**
     * Get the customers used email in the checkout.
     *
     * @param Mage_Sales_Model_Quote_Address $address The address to use
     *
     * @return string
     */
    private function _getCustomerEmail($address)
    {
        //Get the email address from the address object if its set
        $addressEmail = $address->getEmail();
        if (strlen($addressEmail) > 0) {
            return $addressEmail;
        }

        //Otherwise we have to pick up the customers email from the session
        $sessionEmail = Mage::getSingleton('customer/session')
                ->getCustomer()
                ->getEmail();
        if (strlen($sessionEmail) > 0) {
            return $sessionEmail;
        }

        //For guests and new customers there wont be any email on the
        //customer object in the session or their shipping address, so we
        //have to fall back and get the email from their billing address.
        return $this->billingaddress->getEmail();
    }

    /**
     * Set additional Klarna information on the info instance
     *
     * @param string $type What key word the klarna information will use
     *
     * @return voi
     */
    private function _setAdditionalKlarnaInformation($type = self::SHIPPING)
    {
        $isOSCEnabled = Mage::getStoreConfig(
            'onestepcheckout/general/rewrite_checkout_links',
            Mage::app()->getStore()->getId()
        );
        if ($type == 'billing') {
            $address = $this->billingaddress;
        } else {
            $address = $this->shippingaddress;
        }

        $this->info->setAdditionalInformation(
            "klarna_{$type}_email", $this->_getCustomerEmail($address)
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_company", $address->getCompany()
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_firstname", $address->getFirstname()
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_lastname", $address->getLastname()
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_zipcode", $address->getPostcode()
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_city", $address->getCity()
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_phonenumber", $address->getTelephone()
        );
        $this->info->setAdditionalInformation(
            "klarna_{$type}_country", $address->getCountry()
        );

        $invoiceType = (strlen($address->getCompany()) > 0) ? 'company' : 'private';
        $this->info->setAdditionalInformation(
            "klarna_{$type}_invoiceType", $invoiceType
        );
        $street = $address->getStreet();
        $streetaddress = is_array($street) ? $street[0] : $street;
        $country = $address->getCountry();
        if (($country == "DE")
            || ($country == "NL")
        ) {
            if ($isOSCEnabled) {
                $streetparts = KiTT_Addresses::splitAddress($streetaddress);
                $street = $streetparts[0];
                if ($address->getCountry() == "NL") {
                    $houseNumber = $streetparts[1];
                    $houseExt = $streetparts[2];
                } else {
                    $houseNumber = $streetparts[1] . $streetparts[2];
                }
            } else {
                $street = $this->data[$this->method . '_street'];
                $houseNumber = $this->data[$this->method . '_house_number'];
                $houseExt = $this->data[$this->method . '_house_extension'];
            }

            $this->info->setAdditionalInformation(
                "klarna_{$type}_house_number", $houseNumber
            );
            $this->info->setAdditionalInformation("klarna_{$type}_street", $street);

            if ($address->getCountry() == "NL") {
                $this->info->setAdditionalInformation(
                    "klarna_{$type}_house_extension", $houseExt
                );
            }
        } else {
            $this->info->setAdditionalInformation(
                "klarna_{$type}_street", $streetaddress
            );
        }
    }

    /**
     * Get an address matching the key
     *
     * @param string $pno pno or orgno
     * @param string $key hash to match
     *
     * @return KlarnaAddr object
     */
    private function _getMatchingAddress($pno, $key)
    {
        $api = Mage::helper('klarnaPaymentModule/api');
        try {
            $api->loadConfig(
                KlarnaCountry::SE,
                Mage::app()->getStore()->getId(),
                $this->method
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        $api->setKlarnaInformation($api->getCountrySettings('SE'));
        $klarna = $api->getKlarnaObject();
        $kA = new KiTT_Addresses($klarna);
        return $kA->getMatchingAddress($pno, $key);
    }

    /**
     * With OneStepCheckout the only address provided in the checkout is a
     * billing address. Therefore we need to assume it's the only address
     * used and update the shipping address because we need it for our
     * calls to Klarna Online
     *
     * @return void
     */
    private function _setOSCAddress()
    {
        // Swedish address, set billing
        if ($this->billingaddress->getCountry() == "SE") {
            $key = $this->data->getData('klarna_address_key');
            $pno = $this->data->getData('klarna_ssn');
            $this->info->setAdditionalInformation('klarna_personalnumber', $pno);
            $kAddr = $this->_getMatchingAddress($pno, $key);
            $this->_setAddressWithKAddr($kAddr, $this->billingaddress);
        }
        $this->shippingaddress->setCompany($this->billingaddress->getCompany())
            ->setFirstname($this->billingaddress->getFirstname())
            ->setLastname($this->billingaddress->getLastname())
            ->setStreet($this->billingaddress->getStreet())
            ->setPostcode($this->billingaddress->getPostcode())
            ->setCity($this->billingaddress->getCity())
            ->setEmail($this->billingaddress->getEmail())
            ->save();

        // Update the klarna_phonenumber information with the information
        // from the billing address
        $this->info->setAdditionalInformation(
            "klarna_phonenumber", $this->billingaddress->getTelephone()
        );
        if (($this->billingaddress->getCountry() == 'DE')
            || ($this->billingaddress->getCountry() == 'NL')
        ) {
            $this->_createDateOfBirth();
        }
        //If we have a company, update the reference
        if (strlen($this->billingaddress->getCompany()) > 0) {
            $ref = $this->billingaddress->getFirstname() . " " .
                $this->billingaddress->getLastname();
            $this->info->setAdditionalInformation("klarna_reference", $ref);
        }
    }

    /**
     * Sets the swedish shipping address
     *
     * @return void
     */
    private function _setSwedishAddress()
    {
        $pno = $this->data[$this->method . "_personalnumber"];
        $key = $this->data[$this->method . "_shippingaddress"];
        try {
            $kAddr = $this->_getMatchingAddress($pno, $key);
            $this->_setAddressWithKAddr($kAddr, $this->shippingaddress);
            $phone = KiTT_String::decode($this->data[$this->method . '_phonenumber']);
            $this->shippingaddress->setTelephone($phone);
        } catch (Exception $e) {
            $lang = Mage::helper('klarnaPaymentModule/lang');
            $message = $lang->fetch('error_title_2', 'SE');
            $this->showErrorOnePage(
                $message,
                $this->method,
                $this->info->getAdditionalInformation()
            );
        }
    }

    /**
     * Update a specified address with a KlarnaAddress
     *
     * @param KlarnaAddr $kAddr   The KlarnaAddr to use
     * @param object     $address The Magento address to update
     *
     * @return void
     */
    private function _setAddressWithKAddr($kAddr, $address)
    {
        if ($kAddr instanceof KlarnaAddr) {
            $fname = KiTT_String::decode($kAddr->getFirstName());
            $lname = KiTT_String::decode($kAddr->getLastName());
            $company = KiTT_String::decode($kAddr->getCompanyName());
            $street = KiTT_String::decode($kAddr->getStreet());
            $zip = KiTT_String::decode($kAddr->getZipCode());
            $city = KiTT_String::decode($kAddr->getCity());
            if (strlen($fname) > 0) {
                $address->setFirstname($fname);
            }
            if (strlen($lname) > 0) {
                $address->setLastname($lname);
            }
            if (strlen($company) > 0) {
                $address->setCompany($company);
            }
            if (strlen($street) > 0) {
                $address->setStreet($street);
            }
            if (strlen($zip) > 0) {
                $address->setPostcode($zip);
            }
            if (strlen($city) > 0) {
                $address->setCity($city);
            }
        }
    }

    /**
     * Update the shipping addres with information provided in the checkout
     *
     * @return void
     */
    private function _setShippingAddress()
    {
        $this->shippingaddress
            ->setFirstname($this->data[$this->method . "_firstname"])
            ->setLastname($this->data[$this->method . "_lastname"])
            ->setPostcode($this->data[$this->method . "_zipcode"])
            ->setStreet($this->data[$this->method . "_street"])
            ->setCity($this->data[$this->method . "_city"])
            ->setTelephone(
                $this->info->getAdditionalInformation("klarna_phonenumber")
            )
            ->save();
        if ($this->data[$this->method . '_invoiceType'] == 'company') {
            $this->shippingaddress->setCompany(
                $this->data[$this->method . "_company"]
            )->save();
        }
    }

    /**
     * Handle the specific changes needed for German and Dutch addresses
     *
     * @return void
     */
    private function _setGermanOrDutchAddress()
    {
        // Generate a personal number from the date of birth
        $this->_createDateOfBirth();

        // Assemble the street address with house number and house extension
        // for Dutch customers
        $street = $this->data[$this->method . '_street']
            . ' '
            . $this->data[$this->method . '_house_number'];

        if ($this->shippingaddress->getCountry() == "NL") {
            $street .= ' ' . $this->data[$this->method . '_house_extension'];
        }
        // Overwrite the billing address with the shipping address because they
        // need to be the same
        $this->shippingaddress->setStreet(trim($street))->save();
        $this->billingaddress->setFirstname($this->shippingaddress->getFirstname())
            ->setLastname($this->shippingaddress->getLastname())
            ->setStreet($this->shippingaddress->getStreet())
            ->setPostcode($this->shippingaddress->getPostcode())
            ->setCity($this->shippingaddress->getCity())
            ->setCountryId($this->shippingaddress->getCountryId())
            ->setTelephone($this->shippingaddress->getTelephone())
            ->setCompany($this->shippingaddress->getCompany())
            ->save();
    }

    /**
     * Create a DOB and save it to the info instance
     *
     * @return void
     */
    private function _createDateOfBirth()
    {
        $personalnumber = $this->generatePersonalNumber(
            $this->data[$this->method . '_dob_year'],
            $this->data[$this->method . '_dob_month'],
            $this->data[$this->method . '_dob_day']
        );
        $this->info->setAdditionalInformation(
            'klarna_personalnumber', $personalnumber
        );
    }

    /**
     * Set the additional PClass information
     *
     * @return void
     */
    private function _setPClassInformation()
    {
        $pclassData = $this->data[$this->method . "_pclass"];
        $pclass = ($pclassData ? $pclassData : -1);
        $this->info->setAdditionalInformation("klarna_pclass", $pclass);
    }

    /**
     * Functions used to create a date of birth for Germand and Dutch customers
     *
     * @param string $year  Year
     * @param string $month Month
     * @param string $day   Day
     *
     * @return string
     */
    public function generatePersonalNumber($year, $month, $day)
    {
        $date = new DateTime();
        $date->setDate($year, $month, $day);
        return $date->format('dmY');
    }

    /**
     * Shared checks if module is enabled
     *
     * @param object $quote optional quote object
     *
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        if (is_null($quote)) {
            return false;
        }

        $api = Mage::helper('klarnaPaymentModule/api');
        $country = $quote->getShippingAddress()->getCountry();
        try {
            $api->loadConfig(
                $country, Mage::app()->getStore()->getId(), $this->getCode()
            );
        } catch (Exception $e) {
            return false;
        }

        $cs = $api->getCountrySettings($country);

        if (!is_array($cs)) {
            //Not a Klarna supported country
            return false;
        }

        $grandTotal = $quote->getGrandTotal();

        if (empty($grandTotal) || $grandTotal <= 0) {
            return false;
        }

        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $klarna = new Klarna();
        if (!$klarna->checkCountryCurrency($cs['country'], $currency)) {
            return false;
        }

        /*
          Until ILT questions can be answered, we need to block
          purchases over 250 Euro
         */
        if (($grandTotal > 250)
            && ($cs['country'] == KlarnaCountry::NL)
            && ($cs['currency'] == KlarnaCurrency::EUR)
        ) {
            return false;
        }

        return true;
    }


    /**
     * Get the integration mode from the info instance
     *
     * @return void
     */
    public function getIntegrationMode()
    {
        $addinfo = $this->getInfoInstance()->getAdditionalInformation();
        return $addinfo['klarna_integrationmode'];
    }

    /**
     * Authorize the purchase
     *
     * @param object $payment Magento payment model
     * @param double $amount  The amount to authorize with
     *
     * @return Klarna_KlarnaPaymentModule_Model_Klarna_Shared
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $info = $this->getInfoInstance();
        $personal_number = $info->getAdditionalInformation('klarna_personalnumber');
        $order = $payment->getOrder();
        $integration_mode = Mage::helper("klarnaPaymentModule")->getIntegrationMode();
        $api = Mage::helper('klarnaPaymentModule/api');

        try {
            $api->loadConfig(
                $order->getShippingAddress()->getCountry(),
                Mage::app()->getStore()->getId(),
                $payment->getMethod()
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        $api->setOrder($order);
        if (strstr($payment->getMethod(), 'invoice')) {
            $api->addInvoiceFee($order->getQuote());
        }

        $info->setAdditionalInformation('klarna_integrationmode', $integration_mode);

        if ($this->getIntegrationMode() == 'standard') {
            $result = $api->addInvoice();
        } else if ($this->getIntegrationMode() == 'advanced') {
            $result = $api->reserveAmount();
        }

        /* Set the orderstatus */
        if (isset($result['order_status'])) {
            $info->setAdditionalInformation(
                'klarna_orderstatus', $result['order_status']
            );
        }
        if ($result['status'] == 'error') {
            $isOSCEnabled = Mage::getStoreConfig(
                'onestepcheckout/general/rewrite_checkout_links',
                Mage::app()->getStore()->getId()
            );
            if ($isOSCEnabled) {
                $this->showErrorOneStep($result['error'], $payment);
            } else {
                $this->showErrorOnePage(
                    $result['error'],
                    $payment->getMethod(),
                    $payment->getAdditionalInformation()
                );
            }
        } else {
            if ($this->getIntegrationMode() == 'standard') {
                $info->setAdditionalInformation('klarna_invoice_id', $result['ocr']);
                // Remove the personalnumber
                $info->setAdditionalInformation('klarna_personalnumber', '');
                $payment->setAdditionalInformation('klarna_personalnumber', '')
                    ->save();
            } else {
                $info->setAdditionalInformation(
                    'klarna_reservation_id', $result['reservation_id']
                );
            }
        }
        return $this;
    }

    /**
     * Display an error message for One Step Checkout
     *
     * @param string $message The error message to display
     * @param object $payment The payment model in use
     *
     * @return void
     */
    public function showErrorOneStep($message, $payment)
    {
        $values = $payment->getAdditionalInformation();
        $checkout = $this->getCheckout()->init('klarna');
        $checkout->setData('klarna_pno', @$values['klarna_personalnumber']);
        $checkout->setData('klarna_gender', @$values['klarna_gender']);
        $checkout->setData('klarna_dob_day', @$values['klarna_dob_day']);
        $checkout->setData('klarna_dob_month', @$values['klarna_dob_month']);
        $checkout->setData('klarna_dob_year', @$values['klarna_dob_year']);
        $checkout->setData(
            $payment->getMethod() . "_pclass", @$values['klarna_pclass']
        );
        Mage::throwException(strip_tags(html_entity_decode($message)));
    }

    /**
     * Display an error message for One Page Checkout
     *
     * @param string $message The error message to display
     * @param string $method  The payment method in use
     * @param array  $info    The information used in the purchase
     *
     * @return void
     */
    public function showErrorOnePage($message, $method, $info = array())
    {
        $checkout = $this->getCheckout()->init('klarna');
        $checkout->unsetData('klarna_invoice_error');
        $checkout->unsetData('klarna_partpayment_error');
        $checkout->unsetData('klarna_specpayment_error');
        $errorData = array('message' => $message, 'values' => $info);

        $checkout->setData("{$method}_error", $errorData);
        $checkout->setGotoSection('payment');
        $checkout->setUpdateSection('payment-method');
        throw new Mage_Payment_Model_Info_Exception(wordwrap($message, 80));
    }

    /**
     * Capture a Magento order activation and activate the Klarna transaction
     *
     * @param object $payment Magento payment module
     * @param double $amount  The amount to capture
     *
     * @return void
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $info = $this->getInfoInstance();
        $mode = $info->getAdditionalInformation('klarna_integrationmode');

        if ($mode !== 'standard') {
            return $this;
        }

        $order = $payment->getOrder();

        $api = Mage::helper('klarnaPaymentModule/api');

        try {
            $api->loadConfig(
                $order->getShippingAddress()->getCountry(),
                $order->getStoreId(),
                $payment->getMethod()
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        $api->setOrder($order);
        $invoice_id = $info->getAdditionalInformation('klarna_invoice_id');
        $result = $api->activateInvoice($invoice_id);

        if ($result['status'] == 'error') {
            Mage::throwException(
                'Unable to activate invoice, error: ' . $result['error']
            );
        }
        $invoiceno = $result['invoice_id'];
        $info->setAdditionalInformation("klarna_invoice_url", $invoiceno);
        $message = "Klarna Invoice with number _{$invoice_id}_ created";
        Mage::getSingleton('adminhtml/session')
            ->addSuccess(KiTT_String::injectLink($message, $invoiceno));

        return $this;
    }

}
