<?php
/**
 * Part payment widget view
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
 * KiTT_ProductPrice
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_ProductPrice
{
    private $_config;
    private $_locale;
    private $_pclasses;
    private $_templateLoader;
    private $_translator;

    /**
     * Create product price widget
     *
     * @param KiTT_Config           $config         KiTT Config object
     * @param KiTT_Locale           $locale         KiTT Locale object
     * @param KiTT_PClassCollection $pclasses       Collection of pclasses
     * @param KiTT_TemplateLoader   $templateLoader KiTT TemplateLoader object
     * @param KiTT_Translator       $translator     KiTT Translator object
     */
    public function __construct ($config, $locale, $pclasses, $templateLoader,
        $translator
    ) {
        $this->_config = $config;
        $this->_locale = $locale;
        $this->_pclasses = $pclasses;
        $this->_templateLoader = $templateLoader;
        $this->_translator = $translator;
    }

    /**
     * Display the part payment box
     *
     * @return string rendered html
     */
    public function show()
    {
        if (count($this->_pclasses->pclasses) == 0) {
            return "";
        }

        if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE 7.0")
            || strstr($_SERVER['HTTP_USER_AGENT'], "MSIE 6.0")
        ) {
            return "";
        }

        $template = $this->_templateLoader->load('ppbox.mustache');
        $data = array(
            'config' => $this->_config,
            'country' => $this->_locale->getCountryCode(),
            'pclasses' => $this->_pclasses,
            'asterisk' =>
                ($this->_locale->getCountry() == KlarnaCountry::DE
                    ? '*'
                    : ''
                ),
            'lang' => $this->_translator
        );

        return $template->render($data);
    }
}

