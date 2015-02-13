<?php
/**
 * Localisation
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * Represent a locale with country, currency and language
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_Locale
{
    /**
     * @var Klarna
     */
    private static $_api;

    /**
     * Country constant
     *
     * @var int
     */
    private $_country;

    /**
     * Language constant
     *
     * @var int
     */
    private $_language;

    /**
     * Currency constant
     *
     * @var int
     */
    private $_currency;

    /**
     * Creates a locale
     *
     * @param string|int $country  country of the locale
     * @param string|int $language language of the locale (optional)
     * @param string|int $currency currency of the locale (optional)
     */
    public function __construct($country, $language = null, $currency = null)
    {
        // For legacy reasons getLanguageForCountry etc is not static
        if (self::$_api === null) {
            self::$_api = new Klarna;
        }

        // Set country
        if (is_numeric($country)) {
            $this->_country = intval($country);
        } else {
            $this->_country = KlarnaCountry::fromCode($country);
        }

        // Set language from user input or from country default
        if ($language === null) {
            $this->_language = self::$_api->getLanguageForCountry($this->_country);
        } else if (is_numeric($language)) {
            $this->_language = intval($language);
        } else {
            $this->_language = KlarnaLanguage::fromCode($language);
        }

        // Set currency from user input or from country default
        if ($currency === null) {
            $this->_currency = self::$_api->getCurrencyForCountry($this->_country);
        } else if (is_numeric($currency)) {
            $this->_currency = intval($currency);
        } else {
            $this->_currency = KlarnaCurrency::fromCode($currency);
        }
    }

    /**
     * get the country of the locale
     *
     * @return int country constant
     */
    public function getCountry()
    {
        return $this->_country;
    }

    public function getCountryCode()
    {
        return KlarnaCountry::getCode($this->_country);
    }

    /**
     * get the language of the locale
     *
     * @return int language constant
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * get the language ISO code
     */
    public function getLanguageCode()
    {
        return KlarnaLanguage::getCode($this->_language);
    }

    /**
     * get the currency of the locale
     *
     * @return int currency constant
     */
    public function getCurrency()
    {
        return $this->_currency;
    }
}


class KiTT_InvalidLocaleException extends KiTT_Exception
{
    /**
     * KiTT_InvalidLocaleException constructor
     *
     * @param mixed $locale The object in question
     */
    public function __construct($locale)
    {
        parent::__construct("({$locale}) is not a valid locale");
    }
}
