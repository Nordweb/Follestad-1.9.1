<?php

class Nordweb_FrontSystems_Model_Observer extends Mage_Core_Model_Abstract{
    
    
    public function callFromCron()
    {
        Mage::log('********************* Called from cron ***********************');
    }
}?>