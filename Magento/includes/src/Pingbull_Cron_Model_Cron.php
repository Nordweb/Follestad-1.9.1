<?php
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);
class Pingbull_Cron_Model_Cron extends Mage_Core_Model_Abstract
{
    public function run()
    {
        //do something
        Mage::log('Cron task from ' . __FILE__ . ' is going to be executed');
        $hlpr = Mage::helper('cron');
        $result = $hlpr->sendOrderNotifications();
        Mage::log('Notification result: '.$result);
    }
}