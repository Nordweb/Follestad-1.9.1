<?php
class Extendware_EWSpamProtection_Block_Captcha_Script extends Extendware_EWCore_Block_Mage_Core_Template
{
	public function _construct() {
		parent::_construct();
		if ($this->mHelper('config')->getProvider() == 'recaptcha') {
			$this->setTemplate('extendware/ewspamprotection/recaptcha/script.phtml');
		} elseif ($this->mHelper('config')->getProvider() == 'opencaptcha') {
			$this->setTemplate('extendware/ewspamprotection/opencaptcha/script.phtml');
		}
	}
	
	public function getPublicKey() {
		return Mage::getStoreConfig('ewspamprotection/general/public_key');
	}
	
	public function getJsObject() {
		if ($this->hasJsObject()) {
			return $this->getData('js_object');
		}
		return 'ewspamprotection';
	}
	
	public function getImageId() {
		if ($this->hasImageId()) {
			return $this->getData('image_id');
		}
		return 'ewspamprotection_image';
	}
}
