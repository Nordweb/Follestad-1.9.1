<?php

class Extendware_EWSpamProtection_Override_Mage_Review_ProductController extends Extendware_EWSpamProtection_Override_Mage_Review_ProductController_Bridge
{
    public function postAction()
    {
    	$captcha = Mage::getSingleton('ewspamprotection/captcha');
    	$captcha->setConfigScope('product_review');
    	
        if ($captcha->isEnabled() === true) { // check that recaptcha is actually enabled
			if ($captcha->getProvider()->passed() === true) { // if recaptcha response is correct, use core functionality
			    return parent::postAction();
			} else { // if recaptcha response is incorrect, reload the page
			    Mage::getSingleton('core/session')->addError($this->__('Your CAPTCHA entry is incorrect. Please try again.'));
	            Mage::getSingleton('review/session')->setFormData($this->getRequest()->getPost());
	            return $this->_redirectReferer();
			}
        } else {
            return parent::postAction();
        }
    }
}