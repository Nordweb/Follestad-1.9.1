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

class Trollweb_Consignor_Model_Server
{
	public $Addresses = array();

	public function __construct()
    {
        set_error_handler(array($this, 'handlePhpError'), E_ALL);
    }

    public function handlePhpError($errorCode, $errorMessage, $errorFile)
    {
        Mage::log($errorMessage . $errorFile);
        if (in_array($errorCode, array(E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR))) {
            $this->_fault('internal');
        }
        return true;
    }


    /**
     * Retrive webservice session
     *
     * @return Mage_Api_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('api/session');
    }

    /**
     * Retrive webservice configuration
     *
     * @return Mage_Api_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('api/config');
    }

    /**
     * Start webservice session
     *
     * @param string $sessionId
     * @return Mage_Api_Model_Server_Handler_Abstract
     */
    protected function _startSession($sessionId=null)
    {
        $this->_getSession()->setSessionId($sessionId);
        $this->_getSession()->init('api', 'api');
        return $this;
    }


    /**
     * Dispatch webservice fault
     *
     * @param string $faultName
     * @param string $resourceName
     * @param string $customMessage
     */
    protected function _fault($faultName, $resourceName=null, $customMessage=null)
    {
        $faults = $this->_getConfig()->getFaults($resourceName);
        if (!isset($faults[$faultName]) && !is_null($resourceName)) {
            $this->_fault($faultName);
            return;
        } elseif (!isset($faults[$faultName])) {
            $this->_fault('unknown');
            return;
        }
        $this->fault(
            $faults[$faultName]['code'],
            (is_null($customMessage) ? $faults[$faultName]['message'] : $customMessage)
        );
    }

    protected function _extensionLoaded()
    {
        return class_exists('SoapServer', false);
    }

	public function fault($code, $message)
    {
        if ($this->_extensionLoaded()) {
            throw new SoapFault($code, $message);
        } else {
            die('<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                <SOAP-ENV:Body>
                <SOAP-ENV:Fault>
                <faultcode>' . $code . '</faultcode>
                <faultstring>' . $message . '</faultstring>
                </SOAP-ENV:Fault>
                </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>');
        }

    }

    /**
     * Start web service session
     *
     * @return string
     */
    public function startSession()
    {
        $this->_startSession();
        return $this->_getSession()->getSessionId();
    }


    /**
     * End web service session
     *
     * @param string $sessionId
     * @return boolean
     */
    public function endSession($sessionId)
    {
        $this->_startSession($sessionId);
        $this->_getSession()->clear();
        return true;
    }


    /**
     * Login user and Retrieve session id
     *
     * @param string $username
     * @param string $apiKey
     * @return string
     */
    public function login($credentials)
    {
        $this->_startSession();
        try {
          $this->_getSession()->login($credentials->UserName, $credentials->Password);
        } catch (Exception $e) {
            return $this->_fault('access_denied');
        }

    		if(!Mage::getStoreConfig('trollweb_consignor/consignor_config/enable')) {
                return $this->_fault('not_active','consignor');
    		}

        return $this->_getSession()->getSessionId();
    }


	public function GetOrderData($shipmentId, $credentials)
    {

		$sessionId = $this->login($credentials);
		$this->_startSession($sessionId);

    if (!$this->_getSession()->isLoggedIn($sessionId)) {
        return $this->_fault('session_expired');
    }

    $shipmentId = trim($shipmentId);

    // Check for format "1..37", or "3..789"
    if ((strlen($shipmentId) < 9) && stristr($shipmentId,'..')) {
      $len = strlen($shipmentId);
      $zeros = str_repeat('0', 9-($len-2));
      $shipmentId = str_replace('..', $zeros, $shipmentId);
    }
    elseif(strlen($shipmentId) < 9) {
      // Add "1000000" to shipmentId if we give in ie. 48.
      $len = strlen($shipmentId);
      $shipmentId = "1".str_repeat('0', 9-($len+1)).$shipmentId;
    }

		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
    if (!$shipment->getId()) {
      $this->_fault('not_exists','consignor');
    }

		$order = $shipment->getOrder();
		$city_field = Mage::getStoreConfig('trollweb_consignor/consignor_config/poststed_field',$shipment->getStoreId());
		if (!$city_field) {
		  $city_field = 'city';
		}


		$islic = $this->checkLicense(Mage::getStoreConfig('trollweb_consignor/consignor_config/serial_number',$shipment->getStoreId()),  parse_url($shipment->getStore()->getBaseUrl(),PHP_URL_HOST));

		foreach($order->getAddressesCollection() as $address) {
			if($address->getAddressType() == 'shipping') {
			  if ($address->getCompany()) {
			    $_name1 = $address->getCompany();
			    $_attention = $address->getName();
			  }
			  else {
			    $_name1 = $address->getName();
			    $_attention = '';
			  }

				$shippingArray = array(
					'Name1' => $_name1,
					'Attention' => $_attention,
					'StreetAddress1' => ($islic ? $address->getData('street') : base64_decode('VU5MSUNFTlNFRA==')),
					'Postcode'=> $address->getData('postcode'),
					'City' => $address->getData($city_field),
					'CountryName' => $address->getCountry(),
					'Name2' => '',
					'StreetAddress2' => '',
					'StreetAddress3' => '',
					'Mobile' => $address->getData('telephone'),
					'Phone' => $address->getData('telephone'),
					'Email' => $order->getCustomerEmail(),
					'Fax' => $address->getData('fax')
				);
			} elseif($address->getAddressType() == 'billing') {
				$billingArray = array(
					'PostOfficeBox' => $address->getData('street'),
					'PostOfficeBoxCity' => $address->getData($address->getData($city_field)),
					'PostOfficeBoxPostcode' => $address->getData('postcode')
				);
			}
		}

		$originalArray = array('Number' => $shipmentId,
			'CustomerOrigin' => '',
			'Contact' => '',
			'ReceiverRef' => '',
			'OurRef' => '',
			'MessageToCarrier' => '',
			'MessageToDriver' => '',
			'MessageToReceiver' => '',
			'PurchaseNo' => '',
			'ShipmentTypeNo' => '',
			'ReceiverRef' => '',
			'DeliveryConditions' => '',
			'DeliveryTime' => '',
			'PaymentTerms' => '',
			'Amount' => '',
			'Account' => '',
			'Reference' => ''
		);
		$Addresses[] = array_merge($originalArray, $shippingArray, $billingArray);
		$totalWeight = 0;
		foreach($order->getItemsCollection() as $item) {
			$product = Mage::getModel('catalog/product')
					->setStoreId($item->getStoreId())
					->load($item->getProductId());
			foreach ($product->getAttributes() as $attribute => $attrobj) {
				switch ($attribute) {
					case 'height':
						$height = ceil($attrobj->getFrontend()->getValue($product)/10);
					break;
					case 'length':
						$length = ceil($attrobj->getFrontend()->getValue($product)/10);
					break;
					case 'width':
						$width = ceil($attrobj->getFrontend()->getValue($product)/10);
					break;
					case 'weight':
						$weight = $attrobj->getFrontend()->getValue($product);
					break;
				}
			}
			$weight = $product->getWeight()*1000;
			$volume += $height*$length*$width;
			$totalWeight += $weight*$item->getQtyShipped();
		}

		$codAmount = "";
		$cod_method = explode(",",Mage::getStoreConfig('trollweb_consignor/consignor_config/cod_method',$order->getStoreId()));
		if (in_array($order->getPayment()->getMethod(),$cod_method))
		{
		  $paymentTerms = "COD";
		  $codAmount = str_replace(".",",",$order->getData('base_grand_total'));
		}
		else {
		  $paymentTerms = "";
		  $codAmount = "";
		}

		$Packages[] = array('PackagesCount' => 1,
						  'PackagesWeight' => ($islic ? $totalWeight : rand(2,32132)),
						  'CODAmount' => $codAmount,
						  'PaymentTerms' => $paymentTerms,
						  'CarrierCode' => $order->getShippingMethod(),
							'PackagesVolume' => $volume,

							'PackagesMarking' => '',
							'PackagesContents' => '',
							'PackagesHeight' => '',
							'PackagesLength' => '',
							'PackagesWidth' => '',
							'CODAccount' => '',
							'CODKID' => '',
							'CODReference' => '',
							'InsuranceAmount' => '',
							'InsuranceCategory' => '',
							'InsurancePolicyNo' => '',
							'DeliveryTerms' => '',
							'Department' => '',
							'InvoiceNumber' => '',
							//'PaymentTerms' => '',
							'PaymentType' => '',
							'ProjectName' => '',
							'ProjectNumber' => '',
						   );

		$AddressesAndPackages = array(
				 'Addresses' => $Addresses,
				 'Packages' => $Packages);

		return $AddressesAndPackages;

    }

	public function GetCustomerData($customerId, $credentials)
  {
		$sessionId = $this->login($credentials);
		$this->_startSession($sessionId);

    if (!$this->_getSession()->isLoggedIn($sessionId)) {
        return $this->_fault('session_expired');
    }
		$customer = Mage::getModel('customer/customer')->load($customerId);

		$city_field = Mage::getStoreConfig('trollweb_consignor/consignor_config/poststed_field',$customer->getStoreId());
		if (!$city_field) {
		  $city_field = 'city';
		}

		$address = $customer->getPrimaryBillingAddress();

		$Address = array('Number' => $customerId,
							'Name1' => $address->getName(),
							'StreetAddress1' => $address->getData('street'),
							'Postcode'=> $address->getData('postcode'),
							'City' => $address->getData($city_field),
							'CountryName' => $address->getCountry(),
							'Name2' => '',
							'StreetAddress2' => '',
							'StreetAddress3' => '',
							'Mobile' => '',
							'Phone' => $address->getData('telephone'),
							'Email' => $customer->getData('email'),
							'Fax' => $address->getData('fax'),
							'PostOfficeBox' => '',
							'PostOfficeBoxCity' => '',
							'PostOfficeBoxPostcode' => '',
							'CustomerOrigin' => '',
							'Attention' => '',
							'Contact' => '',
							'ReceiverRef' => '',
							'OurRef' => '',
							'MessageToCarrier' => '',
							'MessageToDriver' => '',
							'MessageToReceiver' => '',
							'PurchaseNo' => '',
							'ShipmentTypeNo' => '',
							'ReceiverRef' => '',
							'DeliveryConditions' => '',
							'DeliveryTime' => '',
							'PaymentTerms' => '',
							'Amount' => '',
							'Account' => '',
							'Reference' => ''

		);

		$outArray[] = $Address;
		return $outArray;
	}


	function UpdateData($edi, $credentials) {
/*
 *  $edi
 *  [OrderNumber] => 100000002
    [PackagesCount] => 1
    [ShipmentNumber] => 40170714830870000256
    [ShipmentTrackUrl] => http://sporing.bring.no/sporing/KMSporingslink.aspx?PackageNumber=70714830870000256
    [ColliNumbers] => 00370714830870000271;
    [PackageTrackUrl] => http://sporing.bring.no/sporing/KMSporingslink.aspx?PackageNumber=370714830870000271;
    [Carrier] => Bring
    [Product] => Bedriftspakke dør-dør
    [Price1] => 98
    [Price2] => 98
*/
		$sessionId = $this->login($credentials);
		$this->_startSession($sessionId);

    if (!$this->_getSession()->isLoggedIn($sessionId)) {
        return $this->_fault('session_expired');
    }

		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($edi->OrderNumber);
    if (!$shipment->getId()) {
        $this->_fault('not_exists');
    }

    $carriers = array();
    $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(
        $shipment->getStoreId()
    );
    foreach ($carrierInstances as $code => $carrier) {
        if ($carrier->isTrackingAvailable()) {
            $carriers[$code] = $carrier->getConfigData('title');
        }
    }

    $carrier = 'custom';
    $s_number = $edi->PackageTrackUrl;
    $edi_carrier = strtolower($edi->Carrier);
	  switch ($edi_carrier) {
	    case 'bring':
	        if (in_array('bring_fraktguiden',array_keys($carriers))) {
	          $carrier = 'bring_fraktguiden';
	          $s_number = $edi->ColliNumbers;
	        }
	        break;
	  }


	  foreach (explode(";",$s_number) as $number) {
	    if (!empty($number)) {
        $track = Mage::getModel('sales/order_shipment_track')
                      ->setNumber($number)
                      ->setCarrierCode($carrier)
                      ->setTitle($edi->Product);

        $shipment->addTrack($track)
            ->save();
	    }
	  }
	}



	function IsAlive() {
		return true;
	}

	private function doLog($logline) {
#		if (Mage::getStoreConfig('trollweb_itemupdate/general/logactive')) {
			$logDir = Mage::getBaseDir('log');
			$fh = fopen($logDir."/trollweb_consignor.log","a");
			if ($fh) {
				fwrite($fh,"[".date("d.m.Y H:i:s")."] ".$logline."\n");
				fclose($fh);
			}
#		}
	}

	/**
     * List of global faults
     *
     * @param  string $sessionId
     * @return array
     */
    public function globalFaults($sessionId)
    {
        $this->_startSession($sessionId);
        return array_values($this->_getConfig()->getFaults());
    }

    protected function checkLicense($serial,$domain) {
      $mKey = "dHJvbGx3ZWJfY29uc2lnbm9y";
      $secret = ${base64_decode('ZG9tYWlu')};
      $carray = explode('.',trim($domain));
      $regcode = $serial;
      if (count($carray) < 2) {
        $carray = array(uniqid(),uniqid());
      }

      $domain_array = array(
              'ao','ar','au','bd','bn','co','cr','cy','do','eg','et','fj','fk','gh','gn','id','il','jm','jp','kh','kw','kz','lb','lc','lr','ls',
              'mv','mw','mx','my','ng','ni','np','nz','om','pa','pe','pg','py','sa','sb','sv','sy','th','tn','tz','uk','uy','va','ve','ye','yu',
              'za','zm','zw'
                        );
      $key = $secret.$regcode.$domain.serialize($domain_array);

      $tld = trim($carray[count($carray)-1]);
      if (in_array($tld,$domain_array)) {
        $darr = array_splice($carray,-3);
      }
      else {
        $darr = array_splice($carray,-2);
      }

      $d = strtolower(join(".",$darr));
      $secret = $d;
      $offset = 0;
      $privkey = rand(1,strlen($domain));
      $offset = (strlen($key)*32)-(strlen($key)*64)+$privkey-$offset+(strlen($key)*32);
      $f = base64_decode("c2hhMQ==");
      return ($f(base64_encode(strtolower(substr($secret,0,strlen($d) % $offset).substr($d,(strlen($secret) % $offset))).base64_decode(${base64_decode('bUtleQ==')}))) == ${base64_decode('cmVnY29kZQ==')});
  }

}
