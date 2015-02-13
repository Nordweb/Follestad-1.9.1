<?php
class Extendware_EWSpamProtection_Override_Mage_Customer_AccountController extends Extendware_EWSpamProtection_Override_Mage_Customer_AccountController_Bridge
{
    public function createPostAction()
    {
    	$captcha = Mage::getSingleton('ewspamprotection/captcha');
    	$captcha->setConfigScope('account_registration');
    	
        if ($captcha->isEnabled() === true) { // check that recaptcha is actually enabled
			if ($captcha->getProvider()->passed() === true) { // if recaptcha response is correct, use core functionality
			   return parent::createPostAction();
			} else { // if recaptcha response is incorrect, reload the page
				$this->_getSession()->addError($this->__('Your CAPTCHA entry is incorrect. Please try again.'));
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
	            return $this->_redirectReferer();
			}
        } else {
            return parent::createPostAction();
        }
    }
}