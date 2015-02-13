<?php
class Pingbull_Cron_Adminhtml_CronbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Long processed orders"));
	   $this->renderLayout();
    }
}