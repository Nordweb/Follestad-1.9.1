<?php
/**
 * Dibs A/S
 * Dibs Payment Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Payments & Gateways Extensions
 * @package    Dibsfw_Dibsfw
 * @author     Dibs A/S
 * @copyright  Copyright (c) 2010 Dibs A/S. (http://www.dibs.dk/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Payment Controller
 **/

class Mage_Dibsfw_DibsfwController extends Mage_Core_Controller_Front_Action {
    protected $_order;
    private $oDibsModel;
    
    function _construct() {
        $this->oDibsModel= Mage::getModel('dibsfw/Dibsfw');
    }

    protected function _expireAjax(){
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    public function addToStock() {
      	// Load the session object
      	$session = Mage::getSingleton('checkout/session');
      	$session->setDibsfwStandardQuoteId($session->getQuoteId());
      
      	// Load the order object
	$order = $this->getOrder();
      
// add items back on stock
// Put the order back on stock as it is not yet paid!
// http://www.magentocommerce.com/wiki/groups/132/protx_form_-_subtracting_stock_on_successful_payment

    	if (((int)$this->oDibsModel->getConfigData('handlestock')) == 1) {
            if(!isset($_SESSION['stock_removed']) || 
               $_SESSION['stock_removed'] != $session->getLastRealOrderId()) {
                /* Put the stock back on, we don't want it taken off yet */
                $items = $order->getAllItems(); // Get all items from the order
                if ($items) {
                    foreach($items as $item) {
                        $quantity = $item->getQtyOrdered(); // get Qty ordered
                        $product_id = $item->getProductId(); // get it's ID
                        // Load the stock for this product
                        $stock = Mage::getModel('cataloginventory/stock_item')
                                 ->loadByProduct($product_id);
                        $stock->setQty($stock->getQty()+$quantity); // Set to new Qty            
                        $stock->save(); // Save
                        continue;                        
                    }
                } 
           
                // Flag so that stock is only updated once!
                $_SESSION['stock_removed'] = $session->getLastRealOrderId();

            }
        }
    }

    public function redirectAction(){
        // Load the session object
      	$session = Mage::getSingleton('checkout/session');
      	$session->setDibsfwQuoteId($session->getQuoteId());

	// Create the POST to DIBS (Inside Magento Checkout)
      	$this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('dibsfw/redirect'));
    	$this->renderLayout();
		
	// Create the POST to DIBS (In Separate "Blank" Window)
	// $this->getResponse()->setBody($this->getLayout()->createBlock('Dibsfw/redirect')->toHtml());
      
      	// Save order comment
      	$order = Mage::getModel('sales/order');
      	$order->loadByIncrementId($session->getLastRealOrderId());
        
        foreach($order->getAllStatusHistory() as $oOrderStatusItem) {
            $sOrderComment = $oOrderStatusItem->getComment();
            break;
        }
        
      	if($sOrderComment != $this->__('DIBSFW_LABEL_3')) {
            $order->addStatusToHistory($order->getStatus(), $this->__('DIBSFW_LABEL_3'));
        }
        
      	$order->save();
      
      	// Add items back on stock (if used)
      	$this->addToStock();
    }
    
    public function getOrder (){
        if ($this->_order == null) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    public function successAction() {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getDibsfwStandardQuoteId(true));
        $order = Mage::getModel('sales/order');
	$fields = array();
        
        $mErr = $this->oDibsModel->dibsflex_api_checkMainFields($order, TRUE);
        if($mErr === FALSE) {
            $fields['successaction'] = '1';
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->update($this->oDibsModel->dibsflex_helper_getdbprefix() . 
                           'dibs_orderdata', $fields,'orderid='.$order->getRealOrderId());

            $this->oDibsModel->dibsflex_helper_redirect(
                    $this->oDibsModel->dibsflex_helper_cmsurl('checkout/onepage/success'));
        }
        else {
            echo $this->oDibsModel->dibsflex_api_errCodeToMessage($mErr);
            exit();
        }
    }
    
    public function callbackAction() {
        $order = Mage::getModel('sales/order');
        $this->oDibsModel->dibsflex_api_callback($order);
    }
    
    /**
     * When a customer cancel payment from dibs.
     */
    public function cancelAction() {
    	$session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getDibsfwStandardQuoteId(true));
	$fields = array();

        // Save order comment
      	$order = Mage::getModel('sales/order');
		
	if (isset($_REQUEST['orderid'])) {
            $order->loadByIncrementId((int)$_REQUEST['orderid']);
            $order->registerCancellation($this->__('DIBSFW_LABEL_20'));
            $order->save();

            // Add items back on stock (if used)
            $this->oDibsModel->removeFromStock();

            // Mark in DIBS specific DB table that order cancellation has been performed
            $fields['ordercancellation'] = '1';
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->update($this->oDibsModel->dibsflex_helper_getdbprefix() . 
                           'dibs_orderdata', $fields,'orderid='.$order->getRealOrderId());
	}
        // Give back cart to customer for new attempt to buy
        $this->oDibsModel->dibsflex_helper_redirect(
                $this->oDibsModel->dibsflex_helper_cmsurl('checkout/cart'));
     }
     
     function cgiapiAction() {
         $this->oDibsModel->dibsflex_api_cgiapi();
     }
}