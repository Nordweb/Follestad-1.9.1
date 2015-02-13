<?php
class Extendware_EWSpamProtection_Override_Mage_Contacts_IndexController extends Extendware_EWSpamProtection_Override_Mage_Contacts_IndexController_Bridge
{
    public function postAction()
    {
    	$captcha = Mage::getSingleton('ewspamprotection/captcha');
    	$captcha->setConfigScope('contact_us');
    	
        if ($captcha->isEnabled() === true) { // check that recaptcha is actually enabled
			if ($captcha->getProvider()->passed() === true) { // if recaptcha response is correct, use core functionality
				Mage::getSingleton('customer/session')->unsContactFormData();
			    return parent::postAction();
			} else { // if recaptcha response is incorrect, reload the page
				Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Your CAPTCHA entry is incorrect. Please try again.'));
				Mage::getSingleton('customer/session')->setContactFormData($this->getRequest()->getPost());
			    return $this->_redirect('contacts/');
			}
        } else {
        	Mage::getSingleton('customer/session')->unsContactFormData();
            return parent::postAction();
        }
    }    
}