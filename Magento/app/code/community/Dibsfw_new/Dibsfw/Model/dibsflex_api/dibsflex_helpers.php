<?php
interface dibsflex_helpers_iface {
    function dibsflex_helper_dbquery_write($sQuery);
    function dibsflex_helper_dbquery_read($sQuery);
    function dibsflex_helper_dbquery_read_single($mResult, $sName);
    function dibsflex_helper_cmsurl($sLink);
    function dibsflex_helper_getconfig($sVar);
    function dibsflex_helper_getdbprefix();
    function dibsflex_helper_getReturnURLs($sURL);
    function dibsflex_helper_getOrderObj($mOrderInfo, $bResponse = FALSE);
    function dibsflex_helper_getAddressObj($mOrderInfo);
    function dibsflex_helper_getShippingObj($mOrderInfo);
    function dibsflex_helper_getItemsObj($mOrderInfo);
    function dibsflex_helper_redirect($sURL);
    function dibsflex_helper_afterCallback($oOrder);
    function dibsflex_helper_getlang($sKey);
    function dibsflex_helper_cgiButtonsClass();
    function dibsflex_helper_modVersion();
}

class dibsflex_helpers extends dibsflex_helpers_cms implements dibsflex_helpers_iface {

    /** START OF DIBS HELPERS AREA **/

    function dibsflex_helper_dbquery_write($sQuery) {
        $oWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
        return $oWrite->query($sQuery);
    }
    
    function dibsflex_helper_dbquery_read($sQuery) {
	$oRead = Mage::getSingleton('core/resource')->getConnection('core_read');
        return $oRead->fetchRow($sQuery);
    }
    
    function dibsflex_helper_dbquery_read_single($mResult, $sName) {
        if(isset($mResult[$sName])) return $mResult[$sName];
        else return null;
    }
    
    function dibsflex_helper_cmsurl($sLink) {
        return Mage::getUrl($sLink, array('_secure' => true));
    }
    
    function dibsflex_helper_getconfig($sVar, $sPrefix = 'DIBSFW_') {
        return $this->getConfigData($sPrefix . $sVar);
    }
    
    function dibsflex_helper_getdbprefix() {
        return Mage::getConfig()->getTablePrefix();
    }
    
    function dibsflex_helper_getReturnURLs($sURL) {

        switch ($sURL) {
            case 'success':
                return $this->dibsflex_helper_cmsurl("Dibsfw/Dibsfw/success");
            break;
            case 'callback':
                return $this->dibsflex_helper_cmsurl("Dibsfw/Dibsfw/callback");
            break;
            case 'callbackfix':
                return $this->dibsflex_helper_cmsurl("Dibsfw/Dibsfw/callback");
            break;
            case 'cgi':
                return $this->dibsflex_helper_cmsurl('Dibsfw/Dibsfw/cgiapi');
            break;
            case 'cancel':
                return $this->dibsflex_helper_cmsurl("Dibsfw/Dibsfw/cancel");
            break;
            default:
                return $this->dibsflex_helper_cmsurl('customer/account/index');
            break;
        }
    }
    
    function dibsflex_helper_getOrderObj($mOrderInfo, $bResponse = FALSE) {
        if($bResponse === TRUE) {
            $mOrderInfo->loadByIncrementId((int)$_POST['orderid']);
        }
        
        return (object)array(
                    'order_id'  => $mOrderInfo->getRealOrderId(),
                    'total'     => $this->dibsflex_api_float2intSmartRounding($mOrderInfo->getTotalDue()),
                    'currency'  => $this->dibsflex_api_getCurrencyValue(
                                       $mOrderInfo->getOrderCurrency()->getCode()
                                   )
               );
    }
    
    function dibsflex_helper_getAddressObj($mOrderInfo) {
        $aShipping = $mOrderInfo->getShippingAddress();
        $aBilling  = $mOrderInfo->getBillingAddress();
        
        return (object)array(
                'billing'   => (object)array(
                    'firstname' => $aBilling['firstname'],
                    'lastname'  => $aBilling['lastname'],
                    'street'    => $aBilling['street'],
                    'postcode'  => $aBilling['postcode'],
                    'city'      => $aBilling['city'],
                    'region'    => $aBilling['region'],
                    'country'   => $aBilling['country_id'],
                    'phone'     => $aBilling['telephone'],
                    'email'     => $mOrderInfo['customer_email']
                ),
                'delivery'  => (object)array(
                    'firstname' => $aShipping['firstname'],
                    'lastname'  => $aShipping['lastname'],
                    'street'    => $aShipping['street'],
                    'postcode'  => $aShipping['postcode'],
                    'city'      => $aShipping['city'],
                    'region'    => $aShipping['region'],
                    'country'   => $aShipping['country_id'],
                    'phone'     => $aShipping['telephone'],
                    'email'     => $mOrderInfo['customer_email']
                )
            );
    }

    function dibsflex_helper_getShippingObj($mOrderInfo) {
        return (object)array(
                'method' => $mOrderInfo['shipping_description'],
                'rate'   => $this->dibsflex_api_float2intSmartRounding($mOrderInfo['shipping_amount']),
                'tax'    => $this->dibsflex_api_float2intSmartRounding($this->cms_get_shippingTaxPercent($mOrderInfo))
            );
    }

    function dibsflex_helper_getItemsObj($mOrderInfo) {
        foreach($mOrderInfo->getAllItems() as $oItem) {
            $oItems[] = (object)array(
                'item_id'   => $oItem->getProductId(),
                'name'      => $oItem->getName(),
                'sku'       => $oItem->getSku(),
                'price'     => $this->dibsflex_api_float2intSmartRounding($oItem->getPrice()),
                'qty'       => $this->dibsflex_api_float2intSmartRounding($oItem->getQtyOrdered(), 3),
                'tax_name'  => '',
                'tax_rate'  => $this->dibsflex_api_float2intSmartRounding($oItem->getTaxPercent())
            );
        }
        return $oItems;
    }

    function dibsflex_helper_redirect($sURL) {
        Mage::app()->getFrontController()->getResponse()->setRedirect($sURL);
    }

    function dibsflex_helper_afterCallback($oOrder) {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getDibsfwStandardQuoteId(true));            
        if (((int)$this->dibsflex_helper_getconfig('sendmailorderconfirmation', '')) == 1) {
            $oOrder->sendNewOrderEmail();
        }
	$this->removeFromStock();
        $this->setOrderStatusAfterPayment();
        $session->setQuoteId($session->getDibsfwStandardQuoteId(true));
    }
    
    function dibsflex_helper_getlang($sKey) {
        $aLang = $this->mage_getTextArray();
        return $aLang[$sKey];
    }
    
    function dibsflex_helper_cgiButtonsClass() {
        return 'form-button';
    }
    
    function dibsflex_helper_modVersion() {
        return 'mgn1_3.0.2';
    }


    /** END OF DIBS HELPERS AREA **/
}
?>