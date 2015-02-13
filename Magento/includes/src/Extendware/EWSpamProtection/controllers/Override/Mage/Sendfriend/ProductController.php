<?php
class Extendware_EWSpamProtection_Override_Mage_Sendfriend_ProductController extends Extendware_EWSpamProtection_Override_Mage_Sendfriend_ProductController_Bridge
{
    public function sendmailAction()
    {
    	$captcha = Mage::getSingleton('ewspamprotection/captcha');
    	$captcha->setConfigScope('email_friend');
    	
        if ($captcha->isEnabled() === true) { // check that recaptcha is actually enabled
			if ($captcha->getProvider()->passed() === true) { // if recaptcha response is correct, use core functionality
			    return parent::sendmailAction();
			} else { // if recaptcha response is incorrect, reload the page
			    Mage::getSingleton('catalog/session')->addError($this->__('Your CAPTCHA entry is incorrect. Please try again.'));
			    Mage::getSingleton('catalog/session')->setSendfriendFormData($this->getRequest()->getPost());
			    return $this->_redirectReferer();
			}
        } else {
			return parent::sendmailAction();
        }
    }
}