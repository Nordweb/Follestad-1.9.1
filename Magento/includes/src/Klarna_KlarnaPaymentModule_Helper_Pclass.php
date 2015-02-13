<?php
/**
 * Klarna_KlarnaPaymentModule_Helper_Pclass
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

require_once dirname(__FILE__) . '/MagentoPClassStorage.php';

/**
 * PClass helper class
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Helper_Pclass extends Mage_Core_Helper_Abstract
{

    /**
     * Update pclasses for supplied stores
     *
     * @param array                                 $stores Array containing
     *                                                      Mage_Core_Model_Store
     *                                                      objects
     * @param Klarna_KlarnaPaymentModule_Helper_Api $api    Klarna API Helper
     *
     * @return void
     */
    public function updatePclasses($stores, $api)
    {
        $errors = array();

        $api->clearPClasses();

        $info = $this->getStoreInformation($stores);
        foreach ($info as $storeCode => $countries) {
            foreach ($countries as $country => $settings) {
                $this->fetchPClasses($country, $settings, $api, $errors);
            }
        }

        if (count($errors) > 0) {
            sort($errors);

            $flagdir = Mage::getBaseUrl(
                Mage_Core_Model_Store::URL_TYPE_SKIN
            ) . '/adminhtml/base/default/klarna/flags/';
            foreach ($errors as $error) {
                $flag = "//cdn.klarna.com/public/kitt/res/v1.1/img/flags/".
                    "{$error['country']}.png";
                Mage::getSingleton('core/session')->addError(
                    "<img src='{$flag}' /> [<b> ".
                    "{$error['code']}</b> : <i>{$error['message']} </i>]"
                );
            }
        }
    }

    /**
     * Fetch new PClases
     *
     * @param string                                $country  Klarna country
     * @param array                                 $settings Array containing
     *                                                        Merchant Id and
     *                                                        Shared Secret
     * @param Klarna_KlarnaPaymentModule_Helper_Api $api      Klarna API Helper
     * @param array                                 &$errors  Array holding errors
     *
     * @return void
     */
    public function fetchPClasses($country, $settings, $api, &$errors)
    {
        foreach ($settings as $eid => $info) {
            try {
                $api->getKlarnaMage($country, $eid, $info["secret"], $info["mode"])
                    ->fetchPClasses($country);
            } catch (KlarnaException $e) {
                $errors[] = array(
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'country' => $country
                );
            }
        }
    }

    /**
     * Get the store information to use for fetching new PClasses
     *
     * @param array $stores An array of Mage_Core_Model_Store objects
     *
     * @return array containing all unique Merchant Ids and Shared Secrets sorted
     *         by country which is then sorted by store code
     */
    public function getStoreInformation($stores)
    {
        $result = array();
        foreach ($stores as $store) {
            if (!$store->getConfig('klarna/payments/partpay_enabled')
                && !$store->getConfig('klarna/payments/speccamp_enabled')
            ) {
                continue;
            }

            $code = $store->getCode();
            $result[$code] = $this->parseInformation($store);
        }

        return $result;
    }

    /**
     * Parse information from a store into the collector
     *
     * @param Mage_Core_Model_Store $store A Magento store model
     *
     * @return array
     */
    public function parseInformation($store)
    {
        $result = array();
        $countries = $store->getConfig("klarna/general/activated_countries");
        $mode = $store->getConfig("klarna/general/host");

        foreach (explode(",", $countries) as $country) {
            $country = strtolower($country);
            $eid = $store->getConfig("klarna/{$country}/merchant_id");
            $secret = $store->getConfig("klarna/{$country}/shared_secret");

            $result[$country] = array(
                $eid => array(
                    "secret" => $secret,
                    "mode" => ($mode === "LIVE") ? Klarna::LIVE : Klarna::BETA
                )
            );
        }

        return $result;
    }

    /**
     * Display the PClasses from the database
     *
     * @param Klarna_KlarnaPaymentModule_Helper_Api $api Klarna api helper
     *
     * @return void
     */
    public function displayPClasses($api)
    {
        $storage = new MagentoPClassStorage;
        try {
            $storage->load($api->getPCUri());
        } catch (KlarnaException $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return;
        }

        $pclasses = $this->buildPClassArray($storage);

        $template = new Mage_Core_Block_Template();
        $template->setTemplate('klarna/pclasses.phtml');
        $template->assign('hasPClasses', (count($pclasses) > 0));
        $template->assign('pclasses', $pclasses);

        Mage::getSingleton("core/session")->addNotice(
            $template->renderView()
        );
    }

    /**
     * Build the PClass array used to display the success message
     *
     * @param PCStorage $storage PCStorage implementation with getAllPClasses
     *
     * @return array
     */
    public function buildPClassArray($storage)
    {
        $pclasses = array();
        foreach ($storage->getAllPClasses() as $pclass) {
            $expiryDate = '-';
            if ($pclass->getExpire()) {
                $expiryDate = date('Y-m-d', $pclass->getExpire());
            }

            $country = KlarnaCountry::getCode($pclass->getCountry());

            $code = "";
            // Check if we should set the currency symbol
            switch ($country) {
            case "de":
            case "nl":
            case "fi":
                $code = "€";
                break;
            case "se":
            case "no":
            case "dk":
                $code = "kr";
                break;
            }

            $pclasses[] = array(
                'eid' => $pclass->getEid(),
                'country' => $country,
                'id' => $pclass->getId(),
                'description' => $pclass->getDescription(),
                'expiryDate' => $expiryDate,
                'months' => $pclass->getMonths(),
                'startFee' => "{$pclass->getStartFee()} {$code}",
                'invoiceFee' => "{$pclass->getInvoiceFee()} {$code}",
                'interestRate' => $pclass->getInterestRate() . '%',
                'minAmount' => "{$pclass->getMinAmount()} {$code}"
            );
        }
        return $pclasses;
    }

}
