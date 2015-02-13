<?php

class Pingbull_Cron_Model_System_Config_Source_Day
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Zero days (test)')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('One day')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Two days')),
            array('value' => 3, 'label'=>Mage::helper('adminhtml')->__('Three days')),
            array('value' => 4, 'label'=>Mage::helper('adminhtml')->__('Four days')),
            array('value' => 5, 'label'=>Mage::helper('adminhtml')->__('Five days')),
            array('value' => 6, 'label'=>Mage::helper('adminhtml')->__('Six days')),
            array('value' => 7, 'label'=>Mage::helper('adminhtml')->__('Seven days')),
        );
    }

}
