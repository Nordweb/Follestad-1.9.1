<?php
/**
 * Address Controller
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
 * Controller used to dispatch Klarna ajax actions
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_AddressController
extends Mage_Core_Controller_Front_Action
{

    /**
     * Dispatch the Klarna action
     *
     * @return void
     */
    public function dispatchAction()
    {
        if ($this->getRequest()->getParam('type') == '0') {
            return;
        }

        $type = "";

        switch($this->getRequest()->getParam('type')) {
        case 'invoice':
        case 'klarna_invoice':
        case 'klarna_box_invoice':
            $type = 'klarna_invoice';
            break;
        case 'part':
        case 'klarna_partpayment':
        case 'klarna_box_part':
            $type = 'klarna_partpayment';
            break;
        case 'spec':
        case 'special':
        case 'klarna_specpayment':
        case 'klarna_box_spec':
            $type = 'klarna_specpayment';
            break;
        default:
            return;
        }
        $api = Mage::helper("klarnaPaymentModule/api");

        $country = $this->getRequest()->getParam('country');

        try {
            $api->loadConfig(
                $country, Mage::app()->getStore()->getId(), $type
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        $oKlarna = $api->getKlarnaObject();
        $oKlarna->setCountry($country);

        $dispatcher = KiTT::ajaxDispatcher(
            new KiTT_Addresses($oKlarna),
            null
        );

        $dispatcher->dispatch();
    }

}
