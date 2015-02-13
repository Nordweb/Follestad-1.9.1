<?php
class Extendware_EWSpamProtection_Block_Captcha extends Extendware_EWCore_Block_Mage_Core_Template
{
	public function _construct() {
		parent::_construct();

		if ($this->mHelper('config')->getProvider() == 'recaptcha') {
			$this->setTemplate('extendware/ewspamprotection/recaptcha.phtml');
		} elseif ($this->mHelper('config')->getProvider() == 'opencaptcha') {
			$this->setTemplate('extendware/ewspamprotection/opencaptcha.phtml');
		}
		
	}
	
	protected function _getChildHtml($name, $useCache = true)
    {
        $child = $this->getChild($name);
        if ($child) {
        	$child->setJsObject($this->getUniqueId('ewspamprotection'));
        }
        return parent::_getChildHtml($name, $useCache);
    }
    
	public function getUniqueId($id = '') {
		return $id .= substr(spl_object_hash($this), 0, 8);
	}
	
	public function getCaptcha() {
		return Mage::getSingleton('ewspamprotection/captcha');
	}
	
	public function isEnabled()
	{
		$captcha = $this->getCaptcha();
    	$captcha->setConfigScope($this->getConfigScope());

    	return $captcha->isEnabled() && !$captcha->isUnlockedAction();
	}
	
	protected function _toHtml() {
		if (!$this->isEnabled()) {
			return '';
		}
		
		return parent::_toHtml();
	}
	
}
