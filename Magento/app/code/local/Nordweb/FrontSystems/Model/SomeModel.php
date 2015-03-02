<?php

class Nordweb_FrontSystems_Model_Observer extends Mage_Core_Model_Abstract{
    
    
    public function callFromCron()
    {
        Mage::log('********************* Called from cron ***********************');
    }
    
    
}

class Nordweb_FrontSystems_Model_SomeModel extends Mage_Core_Model_Abstract{
    
    
    
    public function callFromMagentoWhenSale($observer)
    {
        Mage::log('********************* Called from Magento when sale  ***********************');
        

        $orderInstance = $observer->getOrder();
  
        Mage::log('Nordweb_FrontSystems_Model_SomeModel: Calling helpers "AddNewSale" to send in Sale');
        Mage::helper('frontSystems')->AddNewSale($orderInstance);
    }
    
    
}?>