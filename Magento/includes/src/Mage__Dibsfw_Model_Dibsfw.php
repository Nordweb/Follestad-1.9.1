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
 * Payment Model
 **/
require_once dirname(__FILE__) . '/dibsflex_api/dibsflex_helpers_cms.php';
require_once dirname(__FILE__) . '/dibsflex_api/dibsflex_helpers.php';
require_once dirname(__FILE__) . '/dibsflex_api/dibsflex_api.php';

class Mage_Dibsfw_Model_Dibsfw extends dibsflex_api {

    protected $_code  = 'Dibsfw';
    protected $_formBlockType = 'Mage_Dibsfw_block_form';

    /**
     * Get Dibs session namespace
     *
     * @return Dibsfw_Dibsfw_Model_Session
     */
    public function getSession() {
        return Mage::getSingleton('Dibsfw/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /* 
     * Validate the currency code is avaialable to use for dibs or not
     */
    public function validate() {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!array_key_exists($currency_code, $this->dibsflex_api_getCurrencyArray())) {
            Mage::throwException(Mage::helper('Dibsfw')->__('Selected currency code (' .
                                 $currency_code.') is not compatabile with Dibs'));
        }
        return $this;
    }
    
    public function getCheckoutFormFields() {
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
		
        $aFields = $this->dibsflex_api_requestModel($order);
        
        return $aFields;
    }
    
    
    /**
     * Using internal pages for input payment data
     *
     * @return bool
     */
    public function canUseInternal() {
        return false;
    }

    /**
     * Using for multiple shipping address
     *
     * @return bool
     */
    public function canUseForMultishipping() {
        return false;
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('Dibsfw/Dibsfw/redirect', array('_secure' => true));
    }

    /**
     * Calculates if any of the trusted logos are to be shown - in that case return true
     */
    public function showTrustedList() {
        $logoArray = explode(',', $this->getConfigData('dibsfwlogos'));
        foreach($logoArray as $item) {
            if ($item == 'DIBS' || $item == 'VISA_SECURE' || $item == 'MC_SECURE' ||
                $item == 'JCB_SECURE' || $item == 'PCI') {
                
                return true;
            } 
        }
        return false;
    }
    
    /**
     * Calculates if any of the card logos are to be shown - in that case return true
     */
    public function showCardsList() {
        $logoArray = explode(',', $this->getConfigData('dibsfwlogos'));
        foreach($logoArray as $item) {
            if ($item == 'AMEX' || $item == 'BAX' || $item == 'DIN' || $item == 'DK' || 
                $item == 'FFK' || $item == 'JCB' || $item == 'MC' || $item == 'MTRO' || 
                $item == 'MOCA' || $item == 'VISA' || $item == 'ELEC' || $item == 'AKTIA' || 
                $item == 'DNB' || $item == 'EDK' || $item == 'ELV' || $item == 'EW' || 
                $item == 'FSB' || $item == 'GIT' || $item == 'ING' || $item == 'SEB' || 
                $item == 'SHB' || $item == 'SOLO' || $item == 'VAL') {

                return true;	
            } 
        }
        return false;
    }   
}