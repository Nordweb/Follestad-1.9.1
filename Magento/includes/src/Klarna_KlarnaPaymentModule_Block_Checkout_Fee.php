<?php
/**
 * File used to display a invoice fee on the order total
 *
 * PHP Version 5.2
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class used to create a invoice fee block
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Checkout_Fee
extends Mage_Checkout_Block_Total_Default
{
    protected $_template = 'klarna/checkout/fee.phtml';

    /**
     * Get Invoice fee include tax
     *
     * @return float
     */
    public function getInvoiceFeeIncludeTax()
    {
        return $this->getTotal()->getAddress()->getInvoiceFee();
    }

    /**
     * Get Invoice fee exclude tax
     *
     * @return float
     */
    public function getInvoiceFeeExcludeTax()
    {
        return $this->getTotal()->getAddress()->getInvoiceFeeExcludedVat();
    }

    /**
     * Checks if both incl and excl tax prices should be shown
     *
     * @return bool
     */
    public function displayBoth()
    {
        return Mage::helper("tax")->displayCartBothPrices();
    }

    /**
     * Checks if only incl tax price should be shown
     *
     * @return bool
     */
    public function displayIncludeTax()
    {
        return Mage::helper("tax")->displayCartPriceInclTax();
    }

    /**
     * Get the label to display for excl tax
     *
     * @return string
     */
    public function getExcludeTaxLabel()
    {
        return Mage::helper("tax")->getIncExcTaxLabel(false);
    }

    /**
     * Get the label to display for incl tax
     *
     * @return string
     */
    public function getIncludeTaxLabel()
    {
        return Mage::helper("tax")->getIncExcTaxLabel(true);
    }

}
