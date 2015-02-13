<?php
/**
 * File used in order to format prices
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

require_once KLARNA_DIR . "Model/KiTT/classes/Formatter.php";

/**
 * Helper class to implement the KiTT_Formatter interface
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Helper_Format
extends Mage_Core_Helper_Abstract implements KiTT_Formatter
{

    /**
     * Magentos formatPrice has a default "includeContainer" parameter set to
     * true, so we default it to true aswell but allow it to be overridden
     */
    private $_includeContainer;

    /**
     * Set the includeContainer flag
     *
     * @param bool $includeContainer If the container should be visible or not
     *
     * @return void
     */
    public function includeContainer($includeContainer = true)
    {
        $this->_includeContainer = $includeContainer;
    }

    /**
     * Format the price with proper currency symbols etc
     *
     * @param mixed       $price  Raw price
     * @param KiTT_Locale $locale The locale to format the price for
     *
     * @return string formatted price
     */
    public function formatPrice($price, KiTT_Locale $locale)
    {
        return Mage::helper('core')->formatPrice(
            $price, $this->_includeContainer
        );
    }
}
