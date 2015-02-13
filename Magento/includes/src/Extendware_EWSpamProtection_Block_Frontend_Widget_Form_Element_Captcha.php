<?php
class Extendware_EWSpamProtection_Block_Frontend_Widget_Form_Element_Captcha extends Varien_Data_Form_Element_Abstract
{
    public function getHtml()
    {
    	$captcha = $this->getLayout()->createBlock('ewspamprotection/captcha')->setConfigScope('on');
        $captcha->setChild('ewspamprotection_script', $this->getLayout()->createBlock('ewspamprotection/captcha_script'));
        $captcha->setMode('custom');
        $captcha->setTheme($this->getTheme());
        $captcha->setInfoMessage($this->getInfoMessage());
    	return '<tr><td>' . $captcha->toHtml() . '</td></tr>';
    }
}