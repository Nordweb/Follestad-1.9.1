<?php
/**
 * File used to include the invoice fee to the order totals on the admin page
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
 * Order totals handling class.
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Adminhtml_Sales_Order_Totals
extends Mage_Adminhtml_Block_Sales_Order_Totals
{

    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();

        $order = $this->getOrder();
        $payment = $order->getPayment();
        if ($payment->getMethod() != "klarna_invoice") {
            return $this;
        }
        $info = $order->getPayment()->getMethodInstance()->getInfoInstance();
        if (!$info->getAdditionalInformation("invoice_fee")) {
            return $this;
        }
        return Mage::helper('klarnaPaymentModule/total')->addToBlock($this);
    }

}
