<?php
/**
 * File used with shared functions and handling of the Klarna library
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

if (!defined('KLARNA_DIR')) {
    define(
        'KLARNA_DIR',
        Mage::getRoot() . '/code/community/Klarna/KlarnaPaymentModule/'
    );
}

require_once KLARNA_DIR . 'Helper/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once KLARNA_DIR . 'Helper/Klarna/Klarna.php';
require_once KLARNA_DIR . 'Helper/Klarna/pclasses/mysqlstorage.class.php';
require_once KLARNA_DIR . 'Model/KiTT/classes/KiTT.php';
require_once KLARNA_DIR . 'Helper/KlarnaMage.php';

//Klarna::$debug = true;

/**
 * API Helper extension
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Helper_Api extends Mage_Core_Helper_Abstract
{

    /**
     * Make sure javascript is only run once
     *
     * @var boolean
     */
    private static $_hasRun;

    const SHIPPING = "shipping";
    const BILLING = "billing";

    protected $klarna;
    protected $lang;
    protected $applyafter = false;
    protected $price_inc_tax = false;
    protected $orderLocale = false;
    protected $paymentmethod = '';
    protected $qoute;
    protected $shippingaddress;
    protected $order = null;
    protected $quote = null;
    protected $country_settings = array(
        'NO' => array(
            'pno_encoding' => KlarnaEncoding::PNO_NO,
            'language' => KlarnaLanguage::NB,
            'country' => KlarnaCountry::NO,
            'currency' => KlarnaCurrency::NOK,
            'currency_code' => 'NOK',
            'title' => 'Faktura - betal innen 14 dager',
            'title_pp' => 'Delbetaling via Klarna konto',
            'title_sp' => '',
            'admin_description' => 'Norway - NOK',
            'checkout_country' => 'no',
            'checkout_language' => 'nb'
        ),
        'SE' => array(
            'pno_encoding' => KlarnaEncoding::PNO_SE,
            'language' => KlarnaLanguage::SV,
            'country' => KlarnaCountry::SE,
            'currency' => KlarnaCurrency::SEK,
            'currency_code' => 'SEK',
            'title' => 'Faktura - Betala om 14 dagar',
            'title_pp' => 'Delbetalning Klarna konto',
            'title_sp' => '',
            'admin_description' => 'Sweden - SEK',
            'checkout_country' => 'se',
            'checkout_language' => 'sv'
        ),
        'DK' => array(
            'pno_encoding' => KlarnaEncoding::PNO_DK,
            'language' => KlarnaLanguage::DA,
            'country' => KlarnaCountry::DK,
            'currency' => KlarnaCurrency::DKK,
            'currency_code' => 'DKK',
            'title' => 'Faktura - Betaling indenfor 14 dage',
            'title_pp' => 'Delbetaling - Klarna Konto',
            'title_sp' => '',
            'admin_description' => 'Denmark - DKK',
            'checkout_country' => 'dk',
            'checkout_language' => 'da'
        ),
        'FI' => array(
            'pno_encoding' => KlarnaEncoding::PNO_FI,
            'language' => KlarnaLanguage::FI,
            'country' => KlarnaCountry::FI,
            'currency' => KlarnaCurrency::EUR,
            'currency_code' => 'EUR',
            'title' => 'Lasku - 14 vrk maksuaikaa',
            'title_pp' => 'Osamaksu - Klarna Tili',
            'title_sp' => '',
            'admin_description' => 'Finland - EURO',
            'checkout_country' => 'fi',
            'checkout_language' => 'fi'
        ),
        'NL' => array(
            'pno_encoding' => KlarnaEncoding::PNO_NL,
            'language' => KlarnaLanguage::NL,
            'country' => KlarnaCountry::NL,
            'currency' => KlarnaCurrency::EUR,
            'currency_code' => 'EUR',
            'title' => 'Factuur - Betaal binnen 14 dagen',
            'title_pp' => 'Gespreide betaling- Klarna Account',
            'title_sp' => '',
            'admin_description' => 'Netherlands - EURO',
            'checkout_country' => 'nl',
            'checkout_language' => 'nl'
        ),
        'DE' => array(
            'pno_encoding' => KlarnaEncoding::PNO_DE,
            'language' => KlarnaLanguage::DE,
            'country' => KlarnaCountry::DE,
            'currency' => KlarnaCurrency::EUR,
            'currency_code' => 'EUR',
            'title' => 'Rechnung - 14 tage',
            'title_pp' => 'Ratenkauf',
            'title_sp' => '',
            'admin_description' => 'Germany - EURO',
            'checkout_country' => 'de',
            'checkout_language' => 'de'
        ),
    );

    /**
     * Check for available module updates
     *
     * @return bool
     */
    public function checkForUpdates()
    {
        try {
            $kURL = 'http://static.klarna.com/external/msbo/magento.latest.txt';
            $modules = Mage::getConfig()->getNode('modules')->children();
            $modulesArray = (array)$modules;
            $current = $modulesArray['Klarna_KlarnaPaymentModule']->version;
            $latest = file_get_contents($kURL);
            if ($latest != "") {
                $imgPath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
                $updateTemplate = new Mage_Core_Block_Template();
                $updateTemplate->setTemplate('klarna/update.phtml');
                $updateTemplate->assign('imgPath', $imgPath);
                $updateTemplate->assign('current', $current);
                $updateTemplate->assign('latest', $latest);
                if (version_compare($latest, $current, '>')) {
                    $updateTemplate->assign('newAvailable', true);
                    return $updateTemplate->renderView();
                } else {
                    $updateTemplate->assign('newAvailable', false);
                    return $updateTemplate->renderView();
                }
            } else {
                return false;
            }
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Configure KiTT
     * Requires that loadConfig has been run first.
     *
     * @return void
     */
    public function configureKiTT()
    {
        KiTT::configure(
            array(
                'eid' => isset($this->merchantid) ? $this->merchantid : "",
                'agb_link' => Mage::getStoreConfig(
                    "klarna/de/agblink", Mage::app()->getStore()->getId()
                ),
                'paths' => array(
                    'kitt' => KLARNA_DIR . 'Model/KiTT/',
                    'lang' => KLARNA_DIR . 'Model/KiTT/data/language.xml',
                    'extra_templates' => Mage::getBaseDir() .
                        '/skin/frontend/base/default/klarna/templates/',
                ),
                'web' => array(
                    'ajax' => Mage::getBaseUrl(
                        Mage_Core_Model_Store::URL_TYPE_LINK
                    ) . 'klarna/address/dispatch',
                    'root' => KLARNA_DIR . 'Model/KiTT/',
                    'css' => Mage::getBaseUrl(
                        Mage_Core_Model_Store::URL_TYPE_SKIN
                    ) . '/frontend/base/default/klarna/',
                    'js' => Mage::getBaseUrl(
                        Mage_Core_Model_Store::URL_TYPE_JS
                    ),
                    'img' => Mage::getBaseUrl(
                        Mage_Core_Model_Store::URL_TYPE_SKIN
                    ) . '/frontend/base/default/klarna/',
                )
            )
        );
        KiTT::setFormatter(new Klarna_KlarnaPaymentModule_Helper_Format);
    }

    /**
     * Get country settings for the specified country iso
     *
     * @param string $country A country iso
     *
     * @return bool
     */
    public function getCountrySettings($country)
    {
        if (!isset($this->country_settings[$country])) {
            return false;
        }

        return $this->country_settings[$country];
    }

    /**
     * Set the order object to use for Klarna calls
     *
     * @param object $order The order to set
     *
     * @return void
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * This methods load the correct config based on the store id
     *
     * @param string $country The country iso to use
     * @param string $method  The payment method
     * @param string $storeId The store id to use
     *
     * @return void
     */
    public function loadConfig($country, $storeId, $method = null)
    {
        try {
            if (is_int($country)) {
                $country = KlarnaCountry::getCode($country);
            }
            $country = strtolower($country);
            /* Backwards compatability fix */
            if ($method !== null) {
                $method = str_replace("kreditor", "klarna", $method);
                $this->paymentmethod = $method;
            }

            /* Get the merchantid and e-store secret */
            $this->merchantid = (int) Mage::getStoreConfig(
                "klarna/{$country}/merchant_id", $storeId
            );
            $this->secret = Mage::getStoreConfig(
                "klarna/{$country}/shared_secret", $storeId
            );
            $this->quote = "";
            $this->klarna = $this->getKlarnaMage(
                $country, $this->merchantid, $this->secret
            );

        } catch (Exception $e) {
            Klarna::printDebug(__METHOD__, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a KlarnaMage object
     *
     * @param string $country      The country to use
     * @param string $merchantId   The Merchant Id to use
     * @param string $sharedSecret The Shared Secret to use
     * @param int    $mode         The mode to use
     *
     * @return KlarnaMage
     */
    public function getKlarnaMage($country, $merchantId, $sharedSecret, $mode = null)
    {
        if ($mode === null) {
            $mode = $this->getHost();
        }

        $config = new KlarnaConfig;
        $config['eid'] = $merchantId;
        $config['secret'] = $sharedSecret;
        $config['mode'] = $mode;
        $config['pcStorage'] = 'mysql';
        $config['pcURI'] = $this->getPCURI();
        $config['ssl']  = true;
        $config['candice'] = true;

        $klarna = new KlarnaMage;
        $klarna->setConfig($config);
        $klarna->setCountry($country);

        return $klarna;
    }

    /**
     * Build the PClass URI
     *
     * @return array
     */
    public function getPCURI()
    {
        $mageConfig = Mage::getResourceModel('sales/order')
            ->getReadConnection()->getConfig();
        return array(
            "user"      => $mageConfig['username'],
            "passwd"    => $mageConfig['password'],
            "dsn"       => $mageConfig['host'],
            "db"        => $mageConfig['dbname'],
            "table"     => "klarnapclasses"
        );
    }

    /**
     * Set the Klarna country, language and currency information
     *
     * @param array $countrySettings Associative array
     *
     * @return void
     */
    public function setKlarnaInformation($countrySettings)
    {
        $this->klarna->setCountry($countrySettings['country']);
        $this->klarna->setLanguage($countrySettings['language']);
        $this->klarna->setCurrency($countrySettings['currency']);
    }

    /**
     * Get the Klarna object used by this instance
     *
     * @return Klarna
     */
    public function getKlarnaObject()
    {
        return $this->klarna;
    }

    /**
     * Fetch all the goods and create a goodslist
     *
     * @param type $itemcollection       If we have a collection for adv modes
     * @param type $shippingAndDiscounts If discounts and fees should be added
     *
     * @return void
     */
    public function getGoodsList(
        $itemcollection = false, $shippingAndDiscounts = true
    ) {
        $this->orderLocale = Mage::getStoreConfig(
            Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,
            $this->order->getStoreId()
        );
        $this->applyafter = Mage::helper('tax')->applyTaxAfterDiscount(
            $this->order->getStoreId()
        );
        $items = $itemcollection
            ? $itemcollection
            : $this->order->getAllVisibleItems();
        foreach ($items as $item) {
            $id = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($id);
            $quantity = (int) ($item->getQtyOrdered()
                    ? $item->getQtyOrdered()
                    : $item->getQty());

            // Get the price inc. VAT
            $item_price = $item->getPriceInclTax();

            // Get the item tax
            $tax_percent = $item->getTaxPercent();

            if ($item->getProductType() != 'bundle') {
                $tax_percent = is_numeric($tax_percent)
                    ? $item->getTaxPercent()
                    : $this->_getTaxRate($product);
            } else {
                $tax_percent = $this->_getTaxRate($product);
            }

            // If we for some reason dont get the price above we calculate it instead
            if (!$item_price) {
                $item_price = $item->getPrice() / 100 * (100 + $tax_percent);
            }

            $children = $product->getChildrenItems();

            // Go through the children items of a bundled product
            $citems = $item->getChildrenItems();
            if (!$product->isConfigurable() && count($citems) > 0) {

                // There are differences between Magento 1.4.1.1 and older versions
                // so we need to get the price type this way.
                $pricetype = $item->getProduct()
                    ? $item->getProduct()->getPriceType()
                    : $product->getPriceType();

                if ($pricetype == 1) {

                    $this->klarna->addArticle(
                        $quantity,
                        KiTT_String::encode($item->getSku()),
                        KiTT_String::encode($item->getName()),
                        $item_price,
                        $item->getTaxPercent(),
                        0,
                        KlarnaFlags::INC_VAT
                    );
                }

                foreach ($citems as $citem) {
                    $price = ($citem->getPriceInclTax()
                        ? $citem->getPriceInclTax()
                        : 0);
                    $qty = ($citem->getQtyOrdered()
                        ? $citem->getQtyOrdered()
                        : $citem->getQty());
                    $this->klarna->addArticle(
                        $qty,
                        KiTT_String::encode($artNo = $citem->getSku()),
                        KiTT_String::encode($title = $citem->getName()),
                        $price,
                        $citem->getTaxPercent(),
                        0,
                        KlarnaFlags::INC_VAT
                    );
                }
            }

            if (count($children) == 1 && $product->isConfigurable()) {
                $name = $children[0]->getName();
                $sku = $children[0]->getProduct()->getSKU();
            } else {
                $name = $product->getName();
                $sku = $product->getSKU();
            }

            if ($item->getProductType() != 'bundle') {
                $this->klarna->addArticle(
                    $quantity,
                    KiTT_String::encode($sku),
                    KiTT_String::encode($name),
                    $item_price,
                    $tax_percent,
                    0,
                    KlarnaFlags::INC_VAT
                );
            }
        }

        // Only add extra fees/discounts if we are making our first invoice
        // This is only used with advanced integration and partial activations
        if ($shippingAndDiscounts) {
            $shipping_amount = $this->order->getShippingInclTax();
            if ($shipping_amount > 0) {
                $this->klarna->addArticle(
                    1,
                    'shippingfee',
                    KiTT_String::encode(
                        $this->translate('Shipping fee', $this->orderLocale)
                    ),
                    $shipping_amount,
                    $this->_getShippingTaxRate(),
                    0,
                    (KlarnaFlags::INC_VAT + KlarnaFlags::IS_SHIPMENT)
                );
            }

            // Add gift card if present
            $giftCardsAmount = $this->order->getGiftCardsAmount();
            if ($giftCardsAmount > 0) {
                $this->klarna->addArticle(
                    1,
                    'giftcard',
                    KiTT_String::encode(
                        $this->translate('Gift card', $this->orderLocale)
                    ),
                    ($giftCardsAmount * -1),
                    0,
                    0,
                    KlarnaFlags::INC_VAT
                );
            }

            // Add store credit if present
            $customerBalance = $this->order->getCustomerBalanceAmount();
            if ($customerBalance > 0) {
                $this->klarna->addArticle(
                    1,
                    'storecredit',
                    KiTT_String::encode(
                        $this->translate('Store credit', $this->orderLocale)
                    ),
                    ($customerBalance * -1),
                    0,
                    0,
                    KlarnaFlags::INC_VAT
                );
            }

            // Add reward points if present
            $rewardCurrency = $this->order->getRewardCurrencyAmount();
            if ($rewardCurrency > 0) {
                $this->klarna->addArticle(
                    1,
                    'rewardpoints',
                    KiTT_String::encode(
                        $this->translate('Reward points', $this->orderLocale)
                    ),
                    ($rewardCurrency * -1),
                    0,
                    0,
                    KlarnaFlags::INC_VAT
                );
            }

            // Add the totals discount
            $discount = $this->order->getDiscountAmount();
            if ($discount) {
                $this->addGoodsListDiscount($discount);
            }
        }
    }

    /**
     * Add a invoice fee article to the goods list
     *
     * @param mixed $object Either a Quote or an Array
     *
     * @return void
     */
    public function addInvoiceFee($object)
    {
        //During the checkout we will have a quote object which will
        //contain the values we need
        if ($object instanceof Klarna_KlarnaPaymentModule_Model_Quote) {
            $fee = $object->getInvoiceFee();
            $rate = $object->getInvoiceFeeRate();
        } elseif (is_array($object)) {
            //Otherwise if it's during the activation of an invoice or
            //reservation we will instead have the additional information
            //array that holds the values we need
            $fee = $object['invoice_fee'];
            $rate = $object['invoice_fee_rate'];
        }
        if ($fee) {
            $this->lang = Mage::helper('klarnaPaymentModule/lang');
            $country = $this->order->getShippingAddress()->getCountry();
            $title = $this->lang->fetch('INVOICE_FEE_TITLE', $country);
            $this->klarna->addArticle(
                1,
                'invoicefee',
                KiTT_String::encode($title),
                $fee, $rate, 0,
                KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING
            );
        }
    }

    /**
     * Add the order totals discount to the goods list
     *
     * @param float $amount The total discount
     *
     * @return void
     */
    public function addGoodsListDiscount($amount)
    {
        if ($this->applyafter) {
            //With this setting active the discount will not have the correct
            //value. We need to take each respective products rate and calculate
            //a new value.
            $amount = 0;
            foreach ($this->order->getAllVisibleItems() as $product) {
                $rate = $product->getTaxPercent();
                $newAmount = $product->getDiscountAmount() * (($rate / 100 ) + 1);
                $amount -= $newAmount;
            }
            //If the discount also extends to shipping
            $shippingDiscount = $this->order->getShippingDiscountAmount();
            if ($shippingDiscount) {
                $rate = $this->_getShippingTaxRate();
                $newAmount = $shippingDiscount * (($rate / 100 ) + 1);
                $amount -= $newAmount;
            }
        }
        $code = $this->order->getDiscountDescription();
        $title = Mage::helper('sales')->__('Discount (%s)', $code);
        $this->klarna->addArticle(
            1,
            'discount',
            utf8_decode($title),
            $amount,
            0,
            0,
            KlarnaFlags::NO_FLAG
        );
    }

    /**
     * Get the personal number from the order object
     *
     * @return string
     */
    public function getPersonalNumberFromOrder()
    {
        $info = $this->order->getPayment()->getMethodInstance()->getInfoInstance();
        return trim($info->getAdditionalInformation('klarna_personalnumber'));
    }

    /**
     * Get the gender form the order object
     *
     * @return int
     */
    public function getGenderFromOrder()
    {
        $info = $this->order->getPayment()->getMethodInstance()->getInfoInstance();
        $gender = $info->getAdditionalInformation('klarna_gender');
        if (strlen($gender) ==  0) {
            return null;
        }
        return (int)$gender;
    }

    /**
     * Add alternate credit time if we have a company
     *
     * @return int
     */
    function getFlags()
    {
        $altCredTime = Mage::getStoreConfig(
            "advanced/klarna/altcredtime", Mage::app()->getStore()->getId()
        );
        if ($altCredTime == "1") {
            return 65536;
        }
        return 0;
    }

    /**
     * Get the pclass from the order
     *
     * @return int
     */
    public function getPClassFromOrder()
    {
        $info = $this->order->getPayment()->getMethodInstance()->getInfoInstance();
        return (int) $info->getAdditionalInformation('klarna_pclass');
    }

    /**
     * Perform a activateInvoice call to Klarna
     *
     * @param string $invoiceID The invoice id to use
     *
     * @return array
     */
    public function activateInvoice($invoiceID)
    {
        try {
            $result = $this->klarna->activateInvoice(
                $invoiceID, $this->getPClassFromOrder()
            );
            Mage::dispatchEvent(
                'klarna_post_activation',
                array('klarna' => $this->klarna, 'id' => $invoiceID)
            );
            return array(
                'status' => 'success',
                'invoice_id' => utf8_encode($result)
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'error' => wordwrap(utf8_encode($e->getMessage()), 80)
            );
        }
    }

    /**
     * Get the flags associated with advanced integration mode
     *
     * @return int
     */
    public function getAdvancedReserveFlags()
    {
        $flag = KlarnaFlags::RSRV_PRESERVE_RESERVATION;

        // Add alternate credit time if we have a company
        $altCredTime = Mage::getStoreConfig(
            "klarna/advanced/altcredtime", $this->order->getStoreId()
        );
        if ($this->order->getShippingAddress()->getCompany() != ''
            && $altCredTime == "1"
        ) {
            return $flag |= 128;
        }

        return $flag;
    }

    /**
     * Perform a activateReservation call to Klarna
     *
     * @param string $reservation_id The reservation to activate
     * @param mixed  $items          The specific items to add [optional]
     * @param bool   $addinvoice     If invoice fee and discounts should be added
     *
     * @return array
     */
    public function activateReservation(
        $reservation_id, $items = null, $addinvoice = null
    ) {
        $locale = KiTT::locale($this->order->getShippingAddress()->getCountry());
        $this->klarna->setCountry($locale->getCountry());
        $this->klarna->setLanguage($locale->getLanguage());
        $this->klarna->setCurrency($locale->getCurrency());

        $this->getGoodsList($items, $addinvoice);

        $this->_setKlarnaAddress(self::BILLING);
        $this->_setKlarnaAddress(self::SHIPPING);

        $this->klarna->setEstoreInfo($this->order->getIncrementId());

        $this->klarna->enableActivateReservation();
        try {
            $result = $this->klarna->activateReservation(
                $this->getPersonalNumberFromOrder(),
                $reservation_id,
                $this->getGenderFromOrder(),
                '',
                $this->getAdvancedReserveFlags(),
                $this->getPClassFromOrder()
            );

            $data = array('klarna' => $this->klarna, 'id' => $result[1]);
            Mage::dispatchEvent('klarna_post_activation', $data);
            return array(
                'status' => 'success',
                'id' => utf8_encode($result[1])
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'error' => wordwrap(utf8_encode($e->getMessage()), 80)
            );
        }

    }

    /**
     * Perform a reserveAmount call to klarna
     *
     * @return array
     */
    public function reserveAmount()
    {
        $locale = KiTT::locale($this->order->getShippingAddress()->getCountry());
        $this->klarna->setCountry($locale->getCountry());
        $this->klarna->setLanguage($locale->getLanguage());
        $this->klarna->setCurrency($locale->getCurrency());

        $this->getGoodsList();

        $this->_setKlarnaAddress(self::BILLING);
        $this->_setKlarnaAddress(self::SHIPPING);

        $this->klarna->setEstoreInfo($this->order->getIncrementId());

        try {
            $result = $this->klarna->reserveAmount(
                $this->getPersonalNumberFromOrder(),
                $this->getGenderFromOrder(),
                -1,
                $this->getFlags(),
                $this->getPClassFromOrder()
            );
            if ($result[1] == KlarnaFlags::PENDING
                || $result[1] == KlarnaFlags::ACCEPTED
            ) {
                return array(
                    'status' => 'success',
                    'reservation_id' => utf8_encode($result[0]),
                    'order_status' => $result[1]
                );
            }
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'error' => utf8_encode($e->getMessage())
            );
        }
    }

    /**
     * Perform a addTransaction call to Klarna
     *
     * @return array
     */
    public function addInvoice()
    {
        $locale = KiTT::locale($this->order->getShippingAddress()->getCountry());
        $this->klarna->setCountry($locale->getCountry());
        $this->klarna->setLanguage($locale->getLanguage());
        $this->klarna->setCurrency($locale->getCurrency());

        $this->getGoodsList();

        $this->_setKlarnaAddress(self::BILLING);
        $this->_setKlarnaAddress(self::SHIPPING);

        $this->klarna->setEstoreInfo($this->order->getIncrementId());

        $flags = $this->getFlags() + KlarnaFlags::RETURN_OCR;
        try {
            $result = $this->klarna->addTransaction(
                $this->getPersonalNumberFromOrder(),
                $this->getGenderFromOrder(),
                $flags,
                $this->getPClassFromOrder()
            );

            if (($result[2] == KlarnaFlags::PENDING)
                || ($result[2] == KlarnaFlags::ACCEPTED)
            ) {
                return array(
                    'status' => 'success',
                    'invoice_id' => utf8_encode($result[0]),
                    'ocr' => utf8_encode($result[1]),
                    'order_status' => $result[2]
                );
            }
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'error' => utf8_encode($e->getMessage())

            );
        }
    }

    /**
     * Perform a cancelReservation call to Klarna
     *
     * @param string $rno Reservation number
     *
     * @return bool
     */
    public function cancelReservation($rno)
    {
        return $this->klarna->cancelReservation($rno);
    }

    /**
     * Perform a deleteInvoice call to Klarna
     *
     * @param string $invNo Invoice number
     *
     * @return bool
     */
    public function deleteInvoice($invNo)
    {
        return $this->klarna->deleteInvoice($invNo);
    }

    /**
     * Clear the pclasses table
     *
     * @return void
     */
    public function clearPClasses()
    {
        $pcstorage = new MySQLStorage();
        $pcstorage->clear($this->getPCURI());
    }

    /**
     * Perform a fetchPClasses call to Klarna
     *
     * @param string $country The country iso to use
     *
     * @return type
     */
    public function fetchPClasses($country)
    {
        $locale = Kitt::locale($country);
        try {
            $this->klarna->fetchPClasses(
                $locale->getCountry(),
                $locale->getLanguage(),
                $locale->getCurrency()
            );
            return true;
        } catch (Exception $e) {
            return array(
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Get Pclasses
     *
     * @param int    $types   KlarnaPClass constant
     * @param string $country The country iso to use
     *
     * @return type
     */
    public function getPClasses($types = null, $country = null)
    {
        $this->klarna->setCountry($country);
        $pclasses = array();
        if (is_array($types)) {
            foreach ($types as $type) {
                $pc = $this->klarna->getPClasses($type);
                $pclasses = array_merge($pclasses, $pc);
            }
        } else {
            $pclasses = $this->klarna->getPClasses($types);
        }
        return $pclasses;
    }

    /**
     * Translate the payment code into a KiTT code
     *
     * @param $string $paymentMethod The payment method to translate
     *
     * @return string
     */
    public function getPaymentCode($paymentMethod)
    {
        switch ($paymentMethod) {
        case 'klarna_partpayment':
            return KiTT::PART;
        case 'klarna_specpayment':
            return KiTT::SPEC;
        case 'klarna_invoice':
            return KiTT::INVOICE;
        default:
            throw new Exception("Invalid Klarna Solution");
        }
    }

    /**
     * Get a PClass collection
     *
     * @param double $sum  The cost to calculate with
     * @param string $code The payment code
     * @param int    $page KlarnaFlags constant
     *
     * @return KiTT_PClassCollection
     */
    public function getPClassCollection(
        $sum, $code = null, $page = KlarnaFlags::CHECKOUT_PAGE
    ) {
        if ($code == null) {
            $code = $this->getPaymentCode($this->paymentmethod);
        }
        return KiTT::pclassCollection(
            $code,
            $this->klarna,
            $sum,
            $page
        );
    }

    /**
     * Check if there are pclasses available
     *
     * @param double $sum The cost to calculate with
     *
     * @return bool
     */
    public function isPClassesAvailable($sum)
    {
        $pclassCollection = $this->getPClassCollection(
            $sum
        );
        return (count($pclassCollection->pclasses) > 0);
    }

    /**
     * Get the pclasses array from a PClassCollection
     *
     * @param float $sum The sum to calculate with
     *
     * @return array An array containing an array of pclasses
     */
    public function getPClassesFromCollection($sum)
    {
        $pclassCollection = $this->getPClassCollection(
            $sum
        );
        return $pclassCollection->pclasses;
    }

    /**
     * Calculate monthly cost
     *
     * @param double $sum    The sum to calculate with
     * @param int    $pclass KlarnaPClass id
     * @param int    $flags  KlarnaFlags constant
     *
     * @return double
     */
    public function calcMonthlyCost($sum, $pclass, $flags)
    {
        return KlarnaCalc::calc_monthly_cost($sum, $pclass, $flags);
    }

    /**
     * Populate template for loading OneStep Javascripts
     *
     * @param string $country country selected by the customer
     *
     * @return string template rendered with the appropriate javascripts
     */
    public function loadOneStepJavascripts($country)
    {
        if (self::$_hasRun) {
            return "";
        }
        self::$_hasRun = true;
        $this->configureKiTT();
        $templateLoader = KiTT::templateLoader(KiTT::locale($country));
        $jsTemplate = $templateLoader->load("javascript.mustache");
        $scripts = array(
            "scripts" => array(
                "//static.klarna.com/external/core/v1.0/js/klarna.js",
                "//static.klarna.com/external/toc/v1.1/js/klarna.terms.min.js",
                Mage::getBaseUrl(
                    Mage_Core_Model_Store::URL_TYPE_JS
                ) . "klarna.lib.js",
                Mage::getBaseUrl(
                    Mage_Core_Model_Store::URL_TYPE_JS
                ) . "klarnaosc.js"
            )
        );
        if ($country == 'de') {
            $scripts['scripts'][]
                = '//static.klarna.com/external/js/klarnaConsentNew.js';
        }
        return $jsTemplate->render($scripts);
    }

    /**
     * Function for returning the html used in the checkout
     *
     * @param object $paymentMethod either invoice, part or spec
     *
     * @return string
     */
    public function getCheckoutHTML($paymentMethod)
    {
        $quote = $paymentMethod->getInfoInstance()->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $country = $shippingAddress->getCountry();

        $this->locale = KiTT::locale($country);

        $paymentCode = $paymentMethod->getCode();
        $this->loadConfig(
            $this->locale->getCountry(),
            Mage::app()->getStore()->getId(),
            $paymentCode
        );
        $this->klarna->setCountry($this->locale->getCountry());
        $this->klarna->setLanguage($this->locale->getLanguage());
        $this->klarna->setCurrency($this->locale->getCurrency());
        $this->configureKiTT();

        $templateLoader = KiTT::templateLoader($this->locale);

        $paymentType = $this->getPaymentCode($paymentCode);
        $pclassCollection = $this->getPClassCollection(
            $quote->getGrandTotal(),
            $paymentType
        );

        $inputValues = array();
        $session = Mage::getSingleton('checkout/session')->init('klarna');
        $kError = $session->getData("{$paymentCode}_error");
        $session->unsetData("{$paymentCode}_error");

        $errorMessage = "";
        if (is_array($kError)) {
            $inputValues = $this->addKlarnaErrorData($kError['values']);
            $errorMessage = $kError['message'];
            if ($paymentCode === "klarna_partpayment") {
                $defaultPClass = $inputValues['pclass'];
                if (isset($defaultPClass)) {
                    $pclassCollection->setDefault($defaultPClass);
                }
            }
        }

        $paymentTemplate = $templateLoader->load("{$paymentType}.html");
        $templateData = KiTT::templateData(
            $paymentType,
            $this->locale,
            $pclassCollection
        );

        if (strlen($errorMessage) > 0) {
            $templateData->errors = $errorMessage;
        }

        $fee = Mage::getStoreConfig(
            'klarna/' . strtolower($country) . '/invoice_fee',
            Mage::app()->getStore()->getId()
        );

        $helper = Mage::helper('klarnaPaymentModule');
        $feeArray = $helper->getInvoiceFeeArray(
            $fee,
            $shippingAddress,
            $quote->getCustomerTaxClassId()
        );
        $displayValue = $helper->getInvoiceFeeDisplayValue($feeArray);

        $templateData->invoice_fee = $displayValue ? $displayValue : 0;

        $templateData->value->setAddress(
            $this->createKlarnaAddress($shippingAddress)
        );

        if (count($inputValues) > 0) {
            $templateData->value->merge($inputValues);
        }

        $templateData->input->merge($this->getCheckoutParams());
        $templateData->paymentCode = $paymentCode;
        return $paymentTemplate->render($templateData);
    }

    /**
     * Convert the keys on an array to match the values needed by mustache
     *
     * @param array $values Associative array holding klarna information
     *
     * @return array
     */
    public function addKlarnaErrorData($values)
    {
        return array(
            'socialNumber' => @$values['klarna_personalnumber'],
            'firstName' => @$values['klarna_shipping_firstname'],
            'lastName' => @$values['klarna_shipping_lastname'],
            'gender' => @$values['klarna_gender'],
            'phoneNumber' => @$values['klarna_shipping_phonenumber'],
            'street' => @$values['klarna_shipping_street'],
            'homenumber' => @$values['klarna_shipping_house_number'],
            'house_extension' => @$values['klarna_shipping_house_extension'],
            'zipcode' => @$values['klarna_shipping_zipcode'],
            'city' => @$values['klarna_shipping_city'],
            'birth_day' => @$values['klarna_dob_day'],
            'birth_month' => @$values['klarna_dob_month'],
            'birth_year' => @$values['klarna_dob_year'],
            'pclass' => @$values['klarna_pclass']
        );
    }

    /**
     * Get an array with the new names of the input fields
     *
     * @return array
     */
    public function getCheckoutParams()
    {
        return array(
            'shipmentAddressInput'
                => "payment[{$this->paymentmethod}_shippingaddress]",
            'firstName' => "payment[{$this->paymentmethod}_firstname]",
            'lastName' => "payment[{$this->paymentmethod}_lastname]",
            'phoneNumber' => "payment[{$this->paymentmethod}_phonenumber]",
            'socialNumber' => "payment[{$this->paymentmethod}_personalnumber]",
            'street' => "payment[{$this->paymentmethod}_street]",
            'homenumber' => "payment[{$this->paymentmethod}_house_number]",
            'house_extension' => "payment[{$this->paymentmethod}_house_extension]",
            'gender' => "payment[{$this->paymentmethod}_gender]",
            'birth_day' => "payment[{$this->paymentmethod}_dob_day]",
            'birth_month' => "payment[{$this->paymentmethod}_dob_month]",
            'birth_year' => "payment[{$this->paymentmethod}_dob_year]",
            'companyName' => "payment[{$this->paymentmethod}_company]",
            'city' => "payment[{$this->paymentmethod}_city]",
            'zipcode' => "payment[{$this->paymentmethod}_zipcode]",
            'reference' => "payment[{$this->paymentmethod}_reference]",
            'consent' => "payment[{$this->paymentmethod}_consent]",
            'invoiceType' => "payment[{$this->paymentmethod}_invoiceType]",
            'paymentPlan' => "payment[{$this->paymentmethod}_pclass]"
        );
    }

    /**
     * Creates a Klarna Address Object from a Magento address
     *
     * @param object $address The Magento address to convert
     *
     * @return KlarnaAddr
     */
    public function createKlarnaAddress($address)
    {
        $kAddr = new KlarnaAddr();
        try {
            $kAddr->setFirstName(KiTT_String::encode($address->getFirstname()));
            $kAddr->setLastName(KiTT_String::encode($address->getLastname()));
            $kAddr->setTelno(KiTT_String::encode($address->getTelephone()));
            $kAddr->setCity(KiTT_String::encode($address->getCity()));
            $kAddr->setZipCode(KiTT_String::encode($address->getPostcode()));
            $kAddr->setCountry(KiTT_String::encode($address->getCountry()));
            $street = $address->getStreet();
            $street = (is_array($street)
                ? KiTT_String::encode($street[0])
                : KiTT_String::encode($street));
            $kAddr->setStreet($street);
            if (($kAddr->getCountry() == KlarnaCountry::DE)
                || ($kAddr->getCountry() == KlarnaCountry::NL)
            ) {
                $streetparts = KiTT_Addresses::splitAddress($street);
                $kAddr->setStreet($streetparts[0]);
                if ($kAddr->getCountry() == KlarnaCountry::NL) {
                    $kAddr->setHouseNumber($streetparts[1]);
                    $kAddr->setHouseExt($streetparts[2]);
                } else {
                    $kAddr->setHouseNumber($streetparts[1] . $streetparts[2]);
                }
            }
            $kAddr->setCompanyName(KiTT_String::encode($address->getCompany()));
            return $kAddr;
        } catch(Exception $e) {
            //It's most likely an incomplete address, return emtpy klarnaAddr
            return $kAddr;
        }
    }

    /**
     * Sets the Klarna Address on the api from order information
     *
     * @param string $type Klarna_KlarnaPaymentModule_Helper_Api constant
     *
     * @return void
     */
    private function _setKlarnaAddress($type = self::SHIPPING)
    {
        $info = $this->order->getPayment()->getMethodInstance()->getInfoInstance();
        $addr = new KlarnaAddr();
        $invoiceType = $info->getAdditionalInformation("klarna_{$type}_invoiceType");
        if ($invoiceType == 'company') {
            $addr->setCompanyName(
                KiTT_String::encode(
                    $info->getAdditionalInformation("klarna_{$type}_company")
                )
            );
            $ref = $info->getAdditionalInformation('klarna_reference');
            if (strlen($ref) == 0) {
                $fname = $info->getAdditionalInformation("klarna_{$type}_firstname");
                $lname = $info->getAdditionalInformation("klarna_{$type}_lastname");
                $ref = trim($fname . ' ' . $lname);
            }
            $ref = KiTT_String::encode($ref);
            $this->klarna->setReference($ref, "");
            $this->klarna->setComment("ref: {$ref}");
        } else {
            $addr->setFirstName(
                KiTT_String::encode(
                    $info->getAdditionalInformation("klarna_{$type}_firstname")
                )
            );
            $addr->setLastName(
                KiTT_String::encode(
                    $info->getAdditionalInformation("klarna_{$type}_lastname")
                )
            );
        }
        $addr->setEmail(
            KiTT_String::encode(
                $info->getAdditionalInformation("klarna_{$type}_email")
            )
        );
        $addr->setTelno(
            KiTT_String::encode(
                $info->getAdditionalInformation("klarna_{$type}_phonenumber")
            )
        );
        $addr->setZipCode(
            KiTT_String::encode(
                $info->getAdditionalInformation("klarna_{$type}_zipcode")
            )
        );
        $addr->setCity(
            KiTT_String::encode(
                $info->getAdditionalInformation("klarna_{$type}_city")
            )
        );
        $addr->setStreet(
            KiTT_String::encode(
                $info->getAdditionalInformation("klarna_{$type}_street")
            )
        );
        $country = KiTT_String::encode(
            $info->getAdditionalInformation("klarna_{$type}_country")
        );
        $addr->setCountry($country);
        if (($country == 'DE')
            || ($country ==  'NL')
        ) {
            $addr->setHouseNumber(
                KiTT_String::encode(
                    $info->getAdditionalInformation("klarna_{$type}_house_number")
                )
            );
            if ($country == 'NL') {
                $addr->setHouseExt(
                    KiTT_String::encode(
                        $info->getAdditionalInformation(
                            "klarna_{$type}_house_extension"
                        )
                    )
                );
            }
        }

        if ($type == self::BILLING) {
            $this->klarna->setAddress(KlarnaFlags::IS_BILLING, $addr);
        } else {
            $this->klarna->setAddress(KlarnaFlags::IS_SHIPPING, $addr);
        }
    }

    /**
     * Translate with the Magento Core Translate class
     *
     * @param string $string The string to translate
     * @param object $locale The Locale to translate for
     *
     * @return string
     */
    public function translate($string, $locale)
    {
        $translate = Mage::getSingleton('core/translate');
        $translate->setLocale($locale);
        $translate->init("frontend", true);
        return $translate->translate(
            array(new Mage_Core_Model_Translate_Expr($string, 'KlarnaPaymentModule'))
        );
    }

    /**
     * Get the tax rate from a product
     *
     * @param object $product The product to get a tax rate from
     *
     * @return double
     */
    private function _getTaxRate($product)
    {
        // Load the customer so we can retrevice the correct tax class id
        $customer = Mage::getModel('customer/customer')
            ->load($this->order->getCustomerId());
        $request = Mage::getSingleton('tax/calculation')
            ->getRateRequest(
                $this->order->getShippingAddress(),
                $this->order->getBillingAddress(),
                $customer->getTaxClassId(),
                Mage::app()->getStore($this->order->getStoreId())
            );
        return Mage::getSingleton('tax/calculation')
            ->getRate($request->setProductClassId($product->getTaxClassId()));
    }

    /**
     * Returns the tax rate for the shipping
     *
     * @return double The shipping tax rate
     */
    private function _getShippingTaxRate()
    {
        // Load the customer so we can retrevice the correct tax class id
        $customer = Mage::getModel('customer/customer')
            ->load($this->order->getCustomerId());
        $taxClass = Mage::getStoreConfig(
            'tax/classes/shipping_tax_class',
            $this->order->getStoreId()
        );
        $calculation = Mage::getSingleton('tax/calculation');
        $request = $calculation->getRateRequest(
            $this->order->getShippingAddress(),
            $this->order->getBillingAddress(),
            $customer->getTaxClassId(),
            Mage::app()->getStore($this->order->getStoreId())
        );
        return $calculation->getRate($request->setProductClassId($taxClass));
    }

    /**
     * Returns the correct host
     *
     * @return int
     */
    public function getHost()
    {
        $host = Mage::getStoreConfig(
            'klarna/general/host', Mage::app()->getStore()->getId()
        );
        if ($host == 'LIVE') {
            return Klarna::LIVE;
        }
        return Klarna::BETA;
    }

    /**
     * Check order status
     *
     * @param string $id      order id
     * @param string $storeID store id
     *
     * @return string
     */
    public function checkOrderStatus($id, $storeID)
    {
        try{
            $result = $this->klarna->checkOrderStatus($id);
            switch($result){
            case KlarnaFlags::ACCEPTED:
                return "klarna_accepted";
            case KlarnaFlags::PENDING:
                return "klarna_pending";
            case KlarnaFlags::DENIED:
                return "klarna_denied";
            }
        } catch(Exception $e) {
            Mage::getSingleton('core/session')->addError(
                'There was an error for store: ' .
                Mage::app()->getStore($storeID)->getName() . '<br/>' .
                $e->getMessage()
            );
        }
    }

}
