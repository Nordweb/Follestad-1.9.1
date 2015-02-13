<?php
/**
 * Event observer
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class to observe and handle Magento events
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Observer extends Mage_Core_Model_Abstract
{

    /**
     * Collects invoice fee from qoute/addresses to quote
     *
     * @param Varien_Event_Observer $observer Observer instance
     *
     * @return void
     */
    public function salesQuoteCollectTotalsAfter(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $quote->setInvoiceFee(0);
        $quote->setBaseInvoiceFee(0);
        $quote->setInvoiceFeeExcludedVat(0);
        $quote->setBaseInvoiceFeeExcludedVat(0);
        $quote->setInvoiceTaxAmount(0);
        $quote->setBaseInvoiceTaxAmount(0);
        $quote->setInvoiceFeeRate(0);

        foreach ($quote->getAllAddresses() as $address) {
            $quote->setInvoiceFee(
                (float) $quote->getInvoiceFee() + $address->getInvoiceFee()
            );
            $quote->setBaseInvoiceFee(
                (float) $quote->getBaseInvoiceFee() + $address->getBaseInvoiceFee()
            );

            $quoteFeeExclVat = $quote->getInvoiceFeeExcludedVat();
            $addressFeeExclCat = $address->getInvoiceFeeExcludedVat();
            $quote->setInvoiceFeeExcludedVat(
                (float) $quoteFeeExclVat + $addressFeeExclCat
            );

            $quoteBaseFeeExclVat = $quote->getBaseInvoiceFeeExcludedVat();
            $addressBaseFeeExclVat = $address->getBaseInvoiceFeeExcludedVat();
            $quote->setBaseInvoiceFeeExcludedVat(
                (float) $quoteBaseFeeExclVat + $addressBaseFeeExclVat
            );

            $quoteFeeTaxAmount = $quote->getInvoiceTaxAmount();
            $addressFeeTaxAmount = $address->getInvoiceTaxAmount();
            $quote->setInvoiceTaxAmount(
                (float) $quoteFeeTaxAmount + $addressFeeTaxAmount
            );

            $quoteBaseFeeTaxAmount = $quote->getBaseInvoiceTaxAmount();
            $addressBaseFeeTaxAmount = $address->getBaseInvoiceTaxAmount();
            $quote->setBaseInvoiceTaxAmount(
                (float) $quoteBaseFeeTaxAmount + $addressBaseFeeTaxAmount
            );
            $quote->setInvoiceFeeRate($address->getInvoiceFeeRate());
        }
    }

    /**
     * Adds invoice fee to a completed order
     *
     * @param Varien_Event_Observer $observer Magento observer object
     *
     * @return void
     */
    public function salesOrderPaymentPlaceEnd(Varien_Event_Observer $observer)
    {
        $payment = $observer->getPayment();
        if ($payment->getMethodInstance()->getCode() != 'klarna_invoice') {
            return;
        }

        $info = $payment->getMethodInstance()->getInfoInstance();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (! $quote->getId()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }

        //Set the invoice fee included tax value
        $info->setAdditionalInformation('invoice_fee', $quote->getInvoiceFee());
        $info->setAdditionalInformation(
            'base_invoice_fee', $quote->getBaseInvoiceFee()
        );
        $info->setAdditionalInformation(
            'invoice_fee_exluding_vat', $quote->getInvoiceFeeExcludedVat()
        );
        $info->setAdditionalInformation(
            'base_invoice_fee_exluding_vat', $quote->getBaseInvoiceFeeExcludedVat()
        );
        //Set the invoice fee tax amount
        $info->setAdditionalInformation(
            'invoice_tax_amount', $quote->getInvoiceTaxAmount()
        );
        $info->setAdditionalInformation(
            'base_invoice_tax_amount', $quote->getBaseInvoiceTaxAmount()
        );
        //Set the invoice fee rate used
        $info->setAdditionalInformation(
            'invoice_fee_rate', $quote->getInvoiceFeeRate()
        );
        $info->save();
    }

    /**
     * Handle the invoice capture for Klarna orders made with advannced integration
     * mode.
     *
     * @param object $observer Magento observer object
     *
     * @return void
     */
    public function salesOrderInvoicePay($observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $payment = $order->getPayment();

        $method = $payment->getMethod();
        if (($method != "kreditor_invoice")
            && ($method != "kreditor_partpayment")
            && ($method != "klarna_invoice")
            && ($method != "klarna_partpayment")
            && ($method != "klarna_specpayment")
        ) {
            return $this;
        }

        $addinfo = $payment->getAdditionalInformation();
        if ($addinfo['klarna_integrationmode'] != 'advanced') {
            return $this;
        }

        // The array that will hold the items that we are going to use
        $items = array();

        // Get the item ids that we are going to use in our call to Klarna
        $itemkeys = array();
        if (isset($_REQUEST['invoice']['items'])) {
            $itemkeys = array_keys($_REQUEST['invoice']['items']);
        }


        // Loop through the item collection and check if its the object that we
        // should send to Klarna
        foreach ($invoice->getAllItems() as $item) {
            if (in_array($item->getOrderItemId(), $itemkeys)) {
                $items[] = $item;
            }
        }

        $api = Mage::helper("klarnaPaymentModule/api");
        try {
            $api->loadConfig(
                $order->getShippingAddress()->getCountry(),
                $order->getStoreId(),
                $method
            );
        } catch (Exception $e) {
            return;
        }

        $api->setOrder($order);

        $info = $payment->getMethodInstance()->getInfoInstance();
        $invoices = $order->hasInvoices();
        //Add the invoice fee if it's the first invoice beeing created for this
        //order in the backend.
        if ((strstr($method, 'invoice'))
            && ($invoices === 1)
        ) {
            $api->addInvoiceFee($addinfo);
        }

        // Only add the shipping and discounts if this is the first invoice
        // beeing created.
        $shippingAndDiscounts = ($invoices === 1);
        $result = $api->activateReservation(
            $addinfo['klarna_reservation_id'], $items, $shippingAndDiscounts
        );

        if ($result['status'] == "error") {
            Mage::throwException($result["error"]);
        }

        $invoiceno =  $result['id'];
        $magentotime = Mage::getModel('core/date')->timestamp(time());
        $invoice_collection = $payment->getAdditionalInformation(
            "klarna_invoice_url"
        );
        if ($invoice_collection != "") {
            $invoice_collection = (Array)unserialize($invoice_collection);
            $invoice_collection[] = Array(
                $invoiceno, date("Y-m-d H:i", $magentotime)
            );
            $payment->setAdditionalInformation(
                "klarna_invoice_url", serialize($invoice_collection)
            );
        } else {
            $payment->setAdditionalInformation(
                "klarna_invoice_url",
                serialize(
                    array(
                        array(
                            $invoiceno, date("Y-m-d H:i", $magentotime)
                        )
                    )
                )
            );
        }

        $host = (($api->getHost() == Klarna::BETA) ? 'beta-test' : 'online');
        $message = "Klarna Invoice with number _{$invoiceno}_ created";
        $uri = "https://{$host}.klarna.com/invoices/{$invoiceno}.pdf";
        $invoice->addComment(str_replace("_", "", $message));
        Mage::getSingleton('adminhtml/session')->addSuccess(
            KiTT_String::injectLink($message, $uri)
        );

        return $this;
    }

    /**
     * Method to handle the download and showing of the pclasses,
     * aswell as the checking for updates.
     *
     * @param object $observer magento observer object
     *
     * @return void
     */
    public function adminSystemConfigChangedSectionKlarna($observer)
    {
        $helper = Mage::helper("klarnaPaymentModule/pclass");
        $pclassaction = Mage::app()->getRequest()->getParam(
            'klarna_pclasses_buttons'
        );
        $api = Mage::helper("klarnaPaymentModule/api");
        switch ($pclassaction) {
        case "update":
            $helper->updatePclasses(Mage::app()->getStores(), $api);
            //Fall-through to view after updating.
        case "view":
            $helper->displayPClasses($api);
            break;
        }

        $updateaction = Mage::app()->getRequest()->getParam('klarna_updates');

        if ($updateaction == 'check_update') {
            $helper = Mage::helper('klarnaPaymentModule/api');
            $notice = $helper->checkForUpdates();
            if ($notice) {
                Mage::getSingleton('core/session')->addNotice($notice);
            }
        }
    }

    /**
     * Method used to check for updates when logging in to the backend
     *
     * @return void
     */
    public function adminSessionUserLoginSuccess()
    {
        $storeId = Mage::app()->getStore()->getId();
        if (Mage::getStoreConfig('klarna/general/check_on_login', $storeId)) {
            $helper = Mage::helper('klarnaPaymentModule/api');
            $notice = $helper->checkForUpdates();
            if ($notice) {
                Mage::getSingleton('core/session')->addNotice($notice);
            }
        }
    }

    /**
     * Method for updating the order status after completing a purchase
     *
     * @param object $observer Magento observer object
     *
     * @return void
     */
    public function salesOrderPlaceAfter($observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();

        // Update the order status with the status recieved from Klarna
        $status = $payment->getAdditionalInformation('klarna_orderstatus');
        switch($status) {
        case 1:
            $order->addStatusToHistory("klarna_accepted")->save();
            $payment->setAdditionalInformation('klarna_orderstatus', 1);
            break;
        case 2:
            $order->addStatusToHistory("klarna_pending")->save();
            $payment->setAdditionalInformation('klarna_orderstatus', 2);
            break;
        case 3;
            $order->addStatusToHistory("klarna_denied")->save();
            $payment->setAdditionalInformation('klarna_orderstatus', 3);
            break;
        }
    }

    /**
     * Method for updating the order status when viewing the order in the backend
     *
     * Only check for a status update if the order is viewed in the backend
     * This is done so that the checkOrder status isn't run multiple times
     * directly after the purchase has been made.
     *
     * @param object $observer Magento observer object
     *
     * @return void
     */
    public function salesOrderLoadAfter($observer)
    {
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        $order = $observer->getOrder();
        $payment = $order->getPayment();

        $oldStatus = $order->getStatus();
        if ($oldStatus !== "klarna_pending") {
            return;
        }

        $api = Mage::helper('klarnaPaymentModule/api');
        try {
            $api->loadConfig(
                $order->getShippingAddress()->getCountry(),
                $order->getStoreId(),
                $payment->getMethod()
            );
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return;
        }

        $mode = $payment->getAdditionalInformation('klarna_integrationmode');
        if ($mode == 'advanced') {
            $id = $payment->getAdditionalInformation('klarna_reservation_id');
        } else {
            $id = $payment->getAdditionalInformation('klarna_invoice_id');
        }

        $newStatus = $api->checkOrderStatus($id, $order->getStoreId());
        if ($newStatus === $oldStatus) {
            return;
        }

        $order->addStatusToHistory($newStatus)->save();
        $payment->setAdditionalInformation('klarna_orderstatus', $newStatus);

    }

    /**
     * Method used for canceling a Klarna invoice when a Magento order is canceled
     *
     * @param object $observer Magento observer object
     *
     * @return void
     */
    public function salesOrderPaymentCancel($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $method = $payment->getMethod();
        if (($method !== 'klarna_invoice')
            && ($method !== 'klarna_partpayment')
            && ($method !== 'klarna_specpayment')
        ) {
            return;
        }

        $order = $payment->getOrder();
        $api = Mage::helper('klarnaPaymentModule/api');
        try {
            $api->loadConfig(
                $order->getShippingAddress()->getCountry(),
                $order->getStoreId(),
                $payment->getMethod()
            );
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
            return;
        }

        $mode = $payment->getAdditionalInformation('klarna_integrationmode');
        if ($mode == 'standard') {
            try {
                $invNo = $payment->getAdditionalInformation('klarna_invoice_id');
                $result = $api->deleteInvoice($invNo);
                $message = "Klarna invoice: {$invNo} has been deleted";
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
                $order->addStatusHistoryComment($message);
            } catch (Exception $e) {
                Mage::throwException("Error : {$e->getCode()}# {$e->getMessage()}");
            }
        } else if ($mode == 'advanced') {
            try {
                $rno = $payment->getAdditionalInformation('klarna_reservation_id');
                $result = $api->cancelReservation($rno);
                $message = "Klarna reservation: {$rno} has been canceled";
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
                $order->addStatusHistoryComment($message);
            } catch (Exception $e) {
                Mage::throwException("Error : {$e->getCode()}# {$e->getMessage()}");
            }
        }
    }

    /**
     * Method used for sending a customer noticifcation by email on post Klarna
     * invoice activation
     *
     * @param object $observer Magento observer object
     *
     * @return void
     */
    public function klarnaPostActivation($observer)
    {
        $storeId = Mage::app()->getStore()->getId();
        if (!Mage::getStoreConfig('klarna/advanced/send_by_email', $storeId)) {
            return;
        }
        try {
            $klarna = $observer->getKlarna();
            $id = $observer->getId();
            $klarna->emailInvoice($id);
            $message = "Klarna: {$id} - has been sent by email";
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::throwException("Error : {$e->getCode()}# {$e->getMessage()}");
        }
    }

}
