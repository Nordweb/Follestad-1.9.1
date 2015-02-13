<?php
/**
 * File used to handle translations
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

require_once KLARNA_DIR . '/Helper/Api.php';


/**
 * Helper class to handle translations
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Helper_Lang extends Mage_Core_Helper_Abstract
{

    /**
     * @var KlarnaLanguagePack
     */
    protected $languagePack;

    /**
     * @var Klarna
     */
    protected $klarna;

    /**
     * KlarnaLanguage
     */
    protected $code;

    /**
     * Load the xml language pack
     *
     * @return void
     */
    protected function load()
    {
        if (!$this->klarna instanceof Klarna) {
            $this->klarna = new Klarna();
        }
        $helper = Mage::helper('klarnaPaymentModule/api');
        $helper->configureKiTT();
        $this->languagePack = KiTT::translator(
            KiTT::locale(
                KlarnaCountry::fromCode($this->code),
                $this->code
            )
        );
    }

    /**
     * Set the iso code to use for the xml language pack
     *
     * @param string $iso The country iso
     *
     * @return void
     */
    protected function setCode($iso)
    {
        if (!$this->klarna instanceof Klarna) {
            $this->klarna = new Klarna();
        }
        try {
            $country = KlarnaCountry::fromCode($iso);
            $language = $this->klarna->getLanguageForCountry($country);
            $this->code = ($language ? $language : KlarnaLanguage::EN);
        } catch (Exception $e) {
            $this->code = KlarnaLanguage::EN;
        }
    }

    /**
     * Fetch a translation from the xml language pack
     *
     * @param string $text The label to search for
     * @param string $iso  The country iso
     *
     * @return string The translated string
     */
    public function fetch($text, $iso)
    {
        $this->setCode($iso);
        if (!$this->languagePack instanceof KiTT_Translator) {
            $this->load();
        }
        return $this->languagePack->translate($text);
    }

}
