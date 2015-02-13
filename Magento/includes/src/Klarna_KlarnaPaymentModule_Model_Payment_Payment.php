<?php
/**
 * Helper backwards compatability fix
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
 * Klarna_KlarnaPaymentModule_Model_Payment_Payment
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Payment_Payment
extends Mage_Payment_Helper_Data
{
    /**
     * Backwards compatability fix for Kreditor
     *
     * @param string $code The payment method to fetch
     *
     * @return mixed
     */
    public function getMethodInstance($code)
    {
        if (strpos($code, "kreditor") === false) {
            return parent::getMethodInstance($code);
        }

        return parent::getMethodInstance(str_replace("kreditor", "klarna", $code));
    }

}
