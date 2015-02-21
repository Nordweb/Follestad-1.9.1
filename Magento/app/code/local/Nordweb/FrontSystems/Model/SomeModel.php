<?php

class Nordweb_FrontSystems_Model_Observer extends Mage_Core_Model_Abstract{
    
    
    public function callFromCron()
    {
        Mage::log('********************* Called from cron ***********************');
    }
    
    
}

class Nordweb_FrontSystems_Model_SomeModel extends Mage_Core_Model_Abstract{
    
    
    
     public function callFromMagentoWhenSale($observer)
      //public function callFromMagentoWhenSale()
    {
        Mage::log('********************* Called from Magento when sale  ***********************');
        
        
        //Mage::log(get_class_methods($observer));
        //Mage::log($observer->getData());
        //Mage::log($observer->toArray());
        //Mage::log(get_class_methods($observer->getEvent()));
        //Mage::log($observer->getEvent()->toXml());
        
        
         /** @var $orderInstance Mage_Sales_Model_Order */
        $orderInstance = $observer->getOrder();
        //Mage::log(get_class_methods($orderInstance));
        Mage::log($orderInstance->toXml());
        Mage::log($orderInstance->customer_email);
       
        
        ///** @var $orderAddress Mage_Sales_Model_Order_Address */
        //$orderAddress = $this->_getVatRequiredSalesAddress($orderInstance);
       

        //$vatRequestId = $orderAddress->getVatRequestId();
        //$vatRequestDate = $orderAddress->getVatRequestDate();
       
        //$orderHistoryComment = Mage::helper('customer')->__('VAT Request Identifier')
        //    . ': ' . $vatRequestId . '<br />' . Mage::helper('customer')->__('VAT Request Date')
        //    . ': ' . $vatRequestDate;
        //$orderInstance->addStatusHistoryComment($orderHistoryComment, false);
        
   
        
        Mage::log('Nordweb_FrontSystems_Model_SomeModel: Calling helpers "AddNewSale" to send in Sale');
        Mage::helper('frontSystems')->AddNewSale($orderInstance);
    }
}?>