<?php
class dibsflex_helpers_cms extends Mage_Payment_Model_Method_Abstract {   
    protected $_code  = 'Dibsfw';
    
    private function cms_dibs_getPaymentPart($sPaymentstr) {
        $aPaymentPartStr = explode(',', $sPaymentstr, 2);
        return $aPaymentPartStr[0];
    }
    
    public function cms_dibs_printLogo($paytype) {
        $sImgHtml = "";
        $sLogo = $this->cms_dibs_getPaymentPart($paytype);
        if(preg_match("/[a-z\(\)_]+/is", $sLogo)) $sImgHtml = $this->cms_get_imgHtml($sLogo);
        return !empty($sImgHtml) ? $sImgHtml : $this->cms_get_imgHtml("dibs");
    }
    
    public function cms_get_imgHtml($sLogo) {
        $sImgUrl = Mage::getDesign()->getSkinUrl('images/Dibsfw/Dibsfw/' . 
                                                 preg_replace("/(\(|\)|_)/s", "",
                                                 strtolower($sLogo)) . '.gif');
        return (file_exists("." . strstr($sImgUrl, "/skin/"))) ?
               '<img src="' . $sImgUrl . '" alt="' . htmlentities($sLogo) . '" />' : "";
    }
    
    function cms_get_shippingTaxPercent($oOrder) {
        if($oOrder['shipping_tax_amount'] == 0 || $oOrder['shipping_amount'] == 0) return 0;
        else {
            return ($oOrder['shipping_tax_amount'] * 100) / $oOrder['shipping_amount'];
        }
    }
    
    function mage_getTextArray() {
        $aText = array(
            'text_err_fatal'     => 'A fatal error has occured!', 
            'text_return_toshop' => 'Return to shop', 
            'text_err_11'        => 'Unknown orderid was returned from DIBS payment gateway!', 
            'text_err_12'        => 'No orderid was returned from DIBS payment gateway!', 
            'text_err_21'        => 'The amount received from DIBS payment gateway 
                                     differs from original order amount!', 
            'text_err_22'        => 'No amount was returned from DIBS payment gateway!', 
            'text_err_31'        => 'The currency type received from DIBS payment gateway 
                                     differs from original order currency type!', 
            'text_err_32'        => 'No currency type was returned from DIBS payment 
                                     gateway!', 
            'text_err_41'        => 'The fingerprint key does not match!', 
            'text_err_def'       => 'Unknown error appeared. Please contact to shop 
                                     administration to check transaction.'          
        );
        
        return $aText;
    }
    
    public function setOrderStatusAfterPayment(){
	$order = Mage::getModel('sales/order');
	$order->loadByIncrementId($_REQUEST['orderid']);
        $order->setState($this->getConfigData('order_status_after_payment'),
                         true,
                         Mage::helper('dibsfw')->__('DIBSFW_LABEL_22'));

	$order->save();
    }
    
    // Remove from stock (if used)
    public function removeFromStock() {
    	// Load the session object
      	$session = Mage::getSingleton('checkout/session');
     	$session->setDibsfwStandardQuoteId($session->getQuoteId());
      
      	// Load the order object
	$order = Mage::getModel('sales/order');
	$order->loadByIncrementId($_REQUEST['orderid']);
      
// remove items from stock
// http://www.magentocommerce.com/wiki/groups/132/protx_form_-_subtracting_stock_on_successful_payment
        if (((int)$this->getConfigData('handlestock')) == 1) {
            $items = $order->getAllItems(); // Get all items from the order
            if ($items) {
                foreach($items as $item) {
                    $quantity = $item->getQtyOrdered(); // get Qty ordered
                    $product_id = $item->getProductId(); // get it's ID
                    // Load the stock for this product
                    $stock = Mage::getModel('cataloginventory/stock_item')
                             ->loadByProduct($product_id);
                    $stock->setQty($stock->getQty()-$quantity); // Set to new Qty            
                    $stock->save(); // Save
                    continue;                        
                }
            }
        }
    }
}
?>
