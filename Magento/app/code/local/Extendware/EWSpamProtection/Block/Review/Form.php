<?php
class Extendware_EWSpamProtection_Block_Review_Form extends Mage_Review_Block_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('extendware/ewspamprotection/review/form.phtml');
    }
}
