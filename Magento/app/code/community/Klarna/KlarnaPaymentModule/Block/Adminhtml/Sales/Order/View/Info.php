<?php
/**
 * File used to add a notice on the order page
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
 * Class used to create a config form field for pclass actions
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Adminhtml_Sales_Order_View_Info
extends Mage_Adminhtml_Block_Sales_Order_View_Info
{

    /**
     * Render block html
     *
     * @return void
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        $order = $this->getOrder();
        $storeID = $order->getStoreId();
        $method = $order->getPayment()->getMethod();
        if (strstr($method, 'klarna')) {
            $noticeTemplate = new Mage_Core_Block_Template();
            if (Mage::getStoreConfig("klarna/general/host", $storeID) == "LIVE") {
                $target = "https://online.klarna.com";
            } else {
                $target = "https://beta-test.klarna.com";
            }
            $noticeTemplate->setTemplate("klarna/order-notice.phtml");
            $noticeTemplate->assign("target", $target);
            $noticeTemplate->assign(
                "config",
                Mage::getStoreConfig("klarna/general/integration", $storeID)
            );
            return $noticeTemplate->toHtml() . $html;
        }
        return $html;
    }

}
