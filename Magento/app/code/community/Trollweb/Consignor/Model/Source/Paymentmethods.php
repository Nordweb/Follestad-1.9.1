<?php
/**
 * Magento Consignor Integration
 *
 * LICENSE AND USAGE INFORMATION
 * It is NOT allowed to modify, copy or re-sell this file or any
 * part of it. Please contact us by email at support@trollweb.no or
 * visit us at www.trollweb.no if you have any questions about this.
 * Trollweb is not responsible for any problems caused by this file.
 *
 * Visit us at http://www.trollweb.no today!
 *
 * @category   Trollweb
 * @package    Trollweb_Consignor
 * @copyright  Copyright (c) 2011 Trollweb (http://www.trollweb.no)
 * @license    Single-site License
 *
 */

class Trollweb_Consignor_Model_Source_Paymentmethods
{
  public function toOptionArray()
  {
      $method_array = array();
      $method_array[] = array('value' => '','label' => '-None-');
      $methods = $this->getPaymentMethodList();
      foreach ($methods as $method => $title)
      {
        $method_array[] = array('value' => $method, 'label' => $title);
      }

      return $method_array;

  }

  public function getPaymentMethodList()
  {
      $methods = array();
      $payment_methods = Mage::getStoreConfig(Mage_Payment_Helper_Data::XML_PATH_PAYMENT_METHODS);;

      foreach ($payment_methods as $code => $data) {
          if ((isset($data['title']))) {
              $methods[$code] = $data['title'];
          } else {
              $method = Mage::helper('payment')->getMethodInstance($code);
              if ($method) {
                $methods[$code] = $method->getConfigData('title');
              }
          }
      }
      asort($methods);
      return $methods;
  }
}