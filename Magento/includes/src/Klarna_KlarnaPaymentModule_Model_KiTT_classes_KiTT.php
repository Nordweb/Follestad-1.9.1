<?php
/**
 * Entry point to KiTT
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
define("KITT_PATH", dirname(__FILE__));

require_once KITT_PATH . '/Exception.php';
require_once KITT_PATH . '/LanguagePack.php';
require_once KITT_PATH . '/XMLLanguagePack.php';
require_once KITT_PATH . '/Translator.php';
require_once KITT_PATH . '/String.php';
require_once KITT_PATH . '/HTTPContext.php';
require_once KITT_PATH . '/Ajax.php';
require_once KITT_PATH . '/Title.php';
require_once KITT_PATH . '/Config.php';
require_once KITT_PATH . '/SimpleConfig.php';
require_once KITT_PATH . '/Locale.php';
require_once KITT_PATH . '/Session.php';
require_once KITT_PATH . '/Dispatcher.php';
require_once KITT_PATH . '/mustache/Mustache.php';
require_once KITT_PATH . '/Template.php';
require_once KITT_PATH . '/TemplateLoader.php';
require_once KITT_PATH . '/VFS.php';
require_once KITT_PATH . '/PClassCollection.php';
require_once KITT_PATH . '/TemplateData.php';
require_once KITT_PATH . '/ProductPrice.php';
require_once KITT_PATH . '/ErrorMessage.php';
require_once KITT_PATH . '/Formatter.php';
require_once KITT_PATH . '/DefaultFormatter.php';
require_once KITT_PATH . '/Addresses.php';

/**
 * KiTT
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT
{

    /**
     * @var string
     */
    const INVOICE = "invoice";

    /**
     * @var string
     */
    const PART = "part";

    /**
     * @var string
     */
    const SPEC = "spec";

    /**
     * @var KiTT_Config
     */
    private static $_config;

    /**
     * @var KiTT_Formatter
     */
    private static $_formatter;

    /**
     * @var KiTT_LanguagePack
     */
    private static $_languagePack;

    /**
     * Cache of loaded templates
     *
     * @var array
     */
    private static $_templateCache = array();

    /**
     * @var KiTT_ErrorMessage
     */
    private static $_errorMessage;

    /**
     * Get the shared configuration instance
     *
     * @return KiTT_Config
     */
    public static function configuration()
    {
        if (self::$_config === null) {
            self::$_config = new KiTT_SimpleConfig();
        }
        return self::$_config;
    }

    /**
     * Update the shared configuration
     *
     * @param array $options array with key values to update configuration with
     *
     * @return void
     */
    public static function configure($options)
    {
        $config = self::configuration();
        foreach ($options as $key => $value) {
            $config->set($key, $value);
        }
    }

    /**
     * Get a locale object representing the given options
     *
     * @param string|int $country  country of the locale
     * @param string|int $language language of the locale (optional)
     * @param string|int $currency currency of the locale (optional)
     *
     * @return KiTT_Locale
     */
    public static function locale($country, $language = null, $currency = null)
    {
        return new KiTT_Locale($country, $language, $currency);
    }

    /**
     * Set the Formatter to use for this instance.
     *
     * @param KiTT_Formatter $formatter The formatter to set
     *
     * @return void
     */
    public static function setFormatter($formatter)
    {
        self::$_formatter = $formatter;
    }

    /**
     * Get the shared KiTT_Formatter used for this instance
     *
     * @return KiTT_Formatter
     */
    public static function getFormatter()
    {
        if (self::$_formatter === null) {
            self::$_formatter = new KiTT_DefaultFormatter();
        }
        return self::$_formatter;
    }

    /**
     * Create a KiTT_VFS object
     *
     * @return KiTT_VFS
     */
    protected static function vfs()
    {
        return new KiTT_VFS();
    }

    /**
     * Get the shared KiTT_LanguagePack used for this instance
     *
     * @return KiTT_LanguagePack
     */
    public static function languagePack()
    {
        if (self::$_languagePack === null) {
            self::$_languagePack = new KiTT_XMLLanguagePack(
                self::configuration(),
                self::vfs()
            );
        }
        return self::$_languagePack;
    }

    /**
     * Create a KiTT_Translator object for the specified KiTT_Locale
     *
     * @param KiTT_Locale $locale The locale to fetch translations for
     *
     * @return KiTT_Translator
     */
    public static function translator($locale)
    {
        return new KiTT_Translator(self::languagePack(), $locale);
    }

    /**
     * Get a PClassCollection for the specified payment method
     *
     * @param string $payment Payment type constant
     * @param Klarna $kapi    The Klarna object to get pclasses from
     * @param float  $sum     The amount to calculate with
     * @param int    $page    KlarnaFlags PRODUCT_PAGE or CHECKOUT_PAGE
     *
     * @return KiTT_PClassCollection
     */
    public static function pclassCollection($payment, $kapi, $sum, $page)
    {
        $types = null;
        switch ($payment) {
        case KiTT::PART:
            $types = array(
                KlarnaPClass::ACCOUNT,
                KlarnaPClass::CAMPAIGN,
                KlarnaPClass::FIXED
            );
            break;
        case KiTT::SPEC:
            $types = array(KlarnaPClass::SPECIAL);
            break;
        default:
            $types = array();
            break;
        }
        return new KiTT_PClassCollection(
            $kapi, self::getFormatter(), $sum, $page, $types
        );
    }

    /**
     * Get a PClassCollection object for Klarna Part
     *
     * @param Klarna $kapi The Klarna object to get pclasses from
     * @param float  $sum  The amount to calculate with
     * @param int    $page KlarnaFlags PRODUCT_PAGE or CHECKOUT_PAGE
     *
     * @return KiTT_PClassCollection
     */
    public static function pclassCollectionForPart($kapi, $sum, $page)
    {
        return self::pclassCollection(KiTT::PART, $kapi, $sum, $page);
    }

    /**
     * Get a PClassCollection object for Klarna Spec
     *
     * @param Klarna $kapi The Klarna object to get pclasses from
     * @param float  $sum  The amount to calculate with
     * @param int    $page KlarnaFlags PRODUCT_PAGE or CHECKOUT_PAGE
     *
     * @return KiTT_PClassCollection
     */
    public static function pclassCollectionForSpec($kapi, $sum, $page)
    {
        return self::pclassCollection(KiTT::SPEC, $kapi, $sum, $page);
    }

    /**
     * Get a KiTT_Title object
     *
     * @param string                $type       KiTT constant for payment solution
     * @param KiTT_Locale           $locale     The locale to format the title with
     * @param KiTT_PClassCollection $collection The pclass collection to use
     * @param array                 $options    The extra options to use
     *
     * @return mixed KiTT_Title or null
     */
    public static function title($type, $locale, $collection, $options = array())
    {
        return new KiTT_Title(
            $type,
            $locale,
            self::getFormatter(),
            $collection,
            self::translator($locale),
            $options
        );
    }

    /**
     * Get a KiTT_Title object for Klarna Invoice
     *
     * @param KiTT_Locale $locale  The locale to format the title with
     * @param array       $options The extra options to use [optional]
     *
     * @return KiTT_Title
     */
    public static function titleForInvoice($locale, $options = array())
    {
        return self::title(KiTT::INVOICE, $locale, null, $options);
    }

    /**
     * Get a KiTT_Title object for Klarna Part
     *
     * @param KiTT_Locale           $locale     The locale to format the title with
     * @param KiTT_PClassCollection $collection The pclass collection to use
     *
     * @return KiTT_Title
     */
    public static function titleForPart($locale, $collection)
    {
        return self::title(KiTT::PART, $locale, $collection, array());
    }

    /**
     * Get a KiTT_Title object for Klarna Spec
     *
     * @param KiTT_Locale           $locale     The locale to format the title with
     * @param KiTT_PClassCollection $collection The pclass collection to use
     *
     * @return KiTT_Title
     */
    public static function titleForSpec($locale, $collection)
    {
        return self::title(KiTT::SPEC, $locale, $collection, array());
    }

    /**
     * Factory for templateLoader
     *
     * @param KiTT_Locale $locale locale
     *
     * @return KiTT_TemplateLoader
     */
    public static function templateLoader($locale)
    {
        return new KiTT_TemplateLoader(
            self::configuration(),
            $locale,
            new KiTT_VFS,
            self::$_templateCache
        );
    }

    /**
     * Factory for templateData
     *
     * @param string               $paymentCode 'invoice', 'part' or 'spec'
     * @param KiTT_Locale          $locale      locale
     * @param KiTT_PClassCollecion $pclasses    optional pclasscollection
     *
     * @return Kitt_TemplateData
     */
    public static function templateData($paymentCode, $locale, $pclasses = null)
    {
        $translator = self::translator($locale);
        return new KiTT_TemplateData(
            self::configuration(),
            $locale,
            $pclasses,
            $translator,
            $paymentCode,
            new KiTT_InputName($translator),
            new KiTT_InputData(),
            self::errorMessage()
        );
    }

    /**
     * Get a KiTT_Dispatcher object
     *
     * @param KiTT_Addresses    $addresses    The object used to fetch addresses
     * @param KiTT_Productprice $productprice The object used to update the ppbox
     *
     * @return KiTT_Dispatcher
     */
    public static function ajaxDispatcher($addresses, $productprice)
    {
        return new KiTT_Dispatcher(
            new KiTT_Session(),
            new KiTT_Ajax(self::configuration(), $addresses, $productprice)
        );
    }

    /**
     * Factory for the part payment box
     *
     * @param KiTT_Locale          $locale   locale
     * @param KiTT_PClassCollecion $pclasses pclasscollection
     *
     * @return KiTT_ProductPrice
     */
    public static function partPaymentBox($locale, $pclasses)
    {
        return new KiTT_ProductPrice(
            self::configuration(),
            $locale,
            $pclasses,
            self::templateLoader($locale),
            self::translator($locale)
        );
    }

    /**
     * Get an ErrorMessage singleton
     *
     * @return KiTT_ErrorMessage
     */
    public static function errorMessage()
    {
        if (self::$_errorMessage === null) {
            self::$_errorMessage = new KiTT_ErrorMessage;
        }
        return self::$_errorMessage;
    }
}
