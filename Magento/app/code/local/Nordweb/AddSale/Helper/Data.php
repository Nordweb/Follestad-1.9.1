<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_AddSale_Helper_Data extends Mage_Core_Helper_Abstract {



    
    public function AuthenticateFS()
    {
        

        //Declare some paramaters for our soapclient and create it.
        $headerParams  = array("soap_version"=> SOAP_1_1,
                        "trace"=>1,
                        "exceptions"=>0,
                         "soap_defencoding"=>'UTF-8');
        
        
        $url = Mage::getStoreConfig('nordweb/nordweb_group/frontsystemsapi_input',Mage::app()->getStore());
        $user = Mage::getStoreConfig('nordweb/nordweb_group/apiuser_input',Mage::app()->getStore());
        $pwd = Mage::getStoreConfig('nordweb/nordweb_group/apipwd_input',Mage::app()->getStore());
        
        $client = new SoapClient($url,$headerParams);
        $retval = $client->Logon(array('username'=>$user, 'password'=>$pwd));
        //Mage::log('$retval: ' .get_object_vars($retval));
        
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $fsKey = $retval->LogonResult;

        //Declare some paramaters for our soapclient and create it.
        $headerParamsAuth  = array("soap_version"=> SOAP_1_1,
                       "trace"=>1,
                       "exceptions"=>0,
                        "soap_defencoding"=>'UTF-8',
                        'key'=>$fsKey,
                       );
        $clientAuthenticated = new SoapClient('https://dinbutikkdev.frontsystems.no/webshop/WebshopIntegration.svc?wsdl',$headerParamsAuth);
        return array ($clientAuthenticated, $fsKey);
    }
    
    
    public function StoreProduct($fsWebProducts)
    {
        
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $productLookup = Mage::getModel('catalog/product')->loadByAttribute('sku', $fsWebProducts->Product[0]->IDENTITY);
        

        
        Mage::log('$fsWebProducts->Product[0]->IDENTITY: ' .$fsWebProducts->Product[0]->IDENTITY);
        Mage::log('$productLookup->sku: ' .$productLookup->sku);
        //Mage::log(get_class_methods(Mage::getModel('catalog/product')));
        //Mage::log(get_object_vars(Mage::getModel('catalog/product')));
        
        if($productLookup == null or $productLookup == '' or $productLookup->sku == null or $productLookup->sku == ''):
            
            Mage::log('Product not found, creating it, with SKU ' .$fsWebProducts->Product[0]->IDENTITY);
            
            
            try {
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                $product = Mage::getModel('catalog/product');
                $product
                    ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
                    ->setAttributeSetId(9) //ID of a attribute set named 'default'
                    ->setTypeId('configurable') //product type
                    ->setCreatedAt(strtotime('now')) //product creation time
                    ->setSku($fsWebProducts->Product[0]->IDENTITY) //SKU
                    ->setName('Product created programmatically') //product name
                    ->setWeight(8.00)
                    ->setStatus(1) //product status (1 - enabled, 2 - disabled)
                    ->setTaxClassId(4) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
                    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //catalog and search visibility
                    ->setManufacturer(28) //manufacturer id
                    ->setColor(24)
                    ->setNewsFromDate(strtotime('now')) //product set as new from
                    ->setNewsToDate('06/30/2015') //product set as new to
                    ->setCountryOfManufacture('NO') //country of manufacture (2-letter country code)
                    //->setPrice(11.22) //price in form 11.22
                    ->setCost(22.33) //price in form 11.22
                    //->setSpecialPrice(3.44) //special price in form 11.22
                    ->setSpecialFromDate(strtotime('now')) //special price from (MM-DD-YYYY)
                    ->setSpecialToDate('06/30/2015') //special price to (MM-DD-YYYY)
                    ->setMsrpEnabled(1) //enable MAP
                    ->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
                    ->setMsrp(99.99) //Manufacturer's Suggested Retail Price
                    ->setMetaTitle('Programamtic meta title here')
                    ->setMetaKeyword('Programamtic meta keyword here')
                    ->setMetaDescription('Programamtic meta desc here')
                    ->setDescription('This is a long description')
                    ->setShortDescription('This is a short description')
                    ->setMediaGallery (array('images'=>array (), 'values'=>array ())) //media gallery initialization
                    ->setStockData(array(
                                       'use_config_manage_stock' => 0, //'Use config settings' checkbox
                                       'manage_stock'=>1, //manage stock
                                       'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
                                       'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
                                       'is_in_stock' => 1, //Stock Availability
                                       'qty' => 999 //qty
                                   )
                    )
                    ->setCategoryIds(array(3, 10)); //assign product to categories
                $product->save();
            }
            catch(Exception $e){
                Mage::log($e->getMessage());
            }
            
        else:
            Mage::log('Product with SKU already exists: ' . $fsWebProducts->Product[0]->IDENTITY);
            
        endif;

     
    }
    
    
     public function AddNewSale($orderInstance)
    {
    
    try {
        //auth
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
        Mage::log('Client authenticated');
       
        $receipt = null; 
        $saleDateTime = null;
        $saleGuid = Mage::helper('addfsproducts')->getGUID();
        

        $saleLines = array();
 
        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInstance->increment_id); 
        $ordered_items = $order->getAllItems();
     
      
        $payment = $order->getPayment();
      
        //Get card type
        $paymentCode = "";
        if(!empty($payment))
        {
             //Get Payment Info
            $paymentCode = $payment->getMethodInstance()->getCode();
           
        }
        

        $paymentLine = array(
                      "Amount"=> $orderInstance->grand_total,
                      //"CardType"=> "Visa", 
                      "Currency"=> "NOK",
                      "ExtRef"=> $paymentCode,
                      "LastCompletedStep"=> "Capture", 
                      "PaymentType"=> "NetAxept", 
                      "ResponseBody"=> "",
                      
                       );
        $paymentLines = array($paymentLine);
 
         
        Foreach($ordered_items as $item){     
        
        
            if(strlen($item->getSku()) < 6)
            {
                continue;
            }
            
            
            
            //Mage::log('$item: ');
            //Mage::log($item);
            Mage::log('$item->getOrderItem(): ');
            Mage::log($item->getOrderItem());
            //if ($item->getProductType() == "configurable") {
            //    continue;
            //}
            $price = $item->getPriceInclTax();
            if (empty($price) || $price == 0) {
                continue;
            }
             
            Mage::log('*************************** $salesitem ****************************');
            Mage::log('$item->getSku(): ' . $item->getSku());
            Mage::log('$item->getPrice(): ' . $item->getPriceInclTax());
            Mage::log('$item->getQtyOrdered(): ' . $item->getQtyOrdered());
            Mage::log('$item->getName(): ' . $item->getName());
            
            

            $saleLine = array(
                      "Identitiy"=> $item->getSku(), 
                      "Price"=> $item->getPriceInclTax(),
                      "Qty"=> $item->getQtyOrdered(),
                      "ShipmentExtId"=> "",
                      "StockID"=> Mage::getStoreConfig('nordweb/nordweb_group4/stockid_for_purchase',Mage::app()->getStore()),
                      "Text"=> $item->getName(),
                       );
            array_push($saleLines, $saleLine);
        } 
        
        if(!isset($saleLines) || !count($saleLines) > 0)
           return;
       
        Mage::log('*************************** $shipment ****************************');
        Mage::log('$order->getShippingMethod(): ');
        Mage::log($order->getShippingMethod());
        Mage::log('$order->getShippingPrice(): ');
        Mage::log($order->getShippingPrice());
        Mage::log('$order->getShippingDescription(): ');
        Mage::log($order->getShippingDescription());

         $shipment = array(
                      "ExtID"=> $order->getShippingMethod(),
                      "Price"=> $order->getShippingPrice(), 
                      "Provider"=> $order->getShippingDescription(), 
                      //"RegisteredDateTime"=> "",
                      "ReturnLabel"=> null,
                      "ShipmentLabel"=> null,
                      "TrackingURL"=> "",
                       );
        $shipments = array($shipment);
        $shipments = null;
      
       
        $fsWebCustomer = $this->GetCustomer($orderInstance->customer_email);
        if(empty($fsWebCustomer)) //Insert customer
        {
            $resultInt = $this->InsertCustomer($orderInstance);
            $fsWebCustomer = $this->GetCustomer($orderInstance->customer_email);
        }
        
        Mage::log('$fsWebCustomer: ');
        Mage::log($fsWebCustomer);
        Mage::log('$fsWebCustomer->Addresses: ');
        Mage::log($fsWebCustomer->Addresses);
         Mage::log('$fsWebCustomer->Addresses->WebsaleAddress: ');
        Mage::log($fsWebCustomer->Addresses->WebsaleAddress);
        Mage::log('$fsWebCustomer->Addresses->WebsaleAddress->ADDRESSID: ');
        Mage::log($fsWebCustomer->Addresses->WebsaleAddress->ADDRESSID);
        
        $saleObject = array(
                      "Comment"=> "",
                      "CustomerID"=> $fsWebCustomer->CUSTOMERID, 
                      "DeliveryAddressID"=> $fsWebCustomer->Addresses->WebsaleAddress->ADDRESSID,
                      "ExtRef"=> "",
                      "InvoiceAddressID"=> $fsWebCustomer->Addresses->WebsaleAddress->ADDRESSID,
                      "IsComplete"=> true,
                      "IsVoided"=> false,
                      "PaymentLines"=> $paymentLines,
                      "Receipt"=> $receipt,
                      //"SaleDateTime"=> $saleDateTime,
                      "SaleGuid"=> $saleGuid,
                      "SalesLines"=> $saleLines,
                      "Shipments"=> $shipments,
                       );
                       
                       
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('================ FULL SALE BEING SENT TO FRONT ===============');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log($saleObject);
        
        $retval = $clientAuthenticated->NewSale(array('key'=>$fsKey, 'sale'=>$saleObject));
        

        if (is_soap_fault($retval)) {
            Mage::log($retval->faultcode);
            Mage::log($retval->faultstring);
            Mage::log($retval->detail);
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
            Mage::throwException('<b>Vi beklager</b><br/>Det har oppst&aring;tt en feil ved innsending av ordren til Follestad. Vennligst pr&oslash;v igjen. <br/>Hvis ikke det fungerer, kontakt support p&aring;: <a href="mailto:support@follestad.no">support@follestad.no</a><br/><br/><b>Feilmelding fra teknisk system:</b><br/>"<i>' . $retval->faultstring . '</i>"');
        }
        $fsNewSaleResult = $retval->NewSaleResult;
        Mage::log('Registered sale successfully in Front Systems');
        
        
        
        Mage::log('New sale Result:' .$fsNewSaleResult);
        
        echo '<br/><br/>New sale Result:';
        Mage::helper('addfsproducts')->prettyPrintArray( $fsNewSaleResult );
        
        } 
        catch (Exception $e) 
        {
            
             Mage::log($e->getMessage());
             
            //// the message
            //$msg = $e->getMessage();

            //// use wordwrap() if lines are longer than 70 characters
            //$msg = wordwrap($msg,70);
            
            //// To send HTML mail, the Content-type header must be set
            //$headers  = 'MIME-Version: 1.0' . "\r\n";
            //$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            //// Additional headers
            //$headers .= 'To: Rune <rune@nordweb.no>' . "\r\n";
            //$headers .= 'From: webshop@follestad.com' . "\r\n";

            //// send email
            //mail("rune@nordweb.no", "Follestad.no - Exception in AddSale()",$msg, $headers);
            
          

            //try {
            //    $mail->send();
              
            //}
            //catch (Exception $e2) {
            //   Mage::log($e2->getMessage());
            //}
            
            //throw it again
            throw $e;
        }
       
        
    }
    
    public function GetCustomer($email)
    {
        
        //auth
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
        Mage::log('Client authenticated');
        
        //GetCustomer
        $retval = $clientAuthenticated->GetCustomer(array('key'=>$fsKey, 'email'=>$email));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $fsWebCustomer = $retval->GetCustomerResult;
        if(empty($fsWebCustomer) || empty($fsWebCustomer->Email))
        {
            return null;
        }
        
        Mage::log('Successfully found customer');
        return $fsWebCustomer;

    }
    



    public function InsertCustomer($orderInstance)
    {
        //auth
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS("CardTypeEnum");
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
         $billingAddress = $orderInstance->getBillingAddress();
        $shippingAddress = $orderInstance->getShippingAddress();
        
        
        Mage::log('Client authenticated');
        Mage::log('$orderInstance->customer->email');
        Mage::log( $orderInstance->customer->email);
        Mage::log('$billingAddress->getFirstName()');
        Mage::log($billingAddress->getFirstName());
        Mage::log('$shippingAddress->getFirstName()');
        Mage::log($shippingAddress->getFirstName());
        Mage::log('$orderInstance->customer_email');
        Mage::log($orderInstance->customer_email);
        Mage::log('$orderInstance->customer->firstName');
        Mage::log($orderInstance->customer->firstName);
        
       

        $websaleAddressShipping = array(
                      //"ADDRESSID"=> $customer->,
                      "Address"=> $shippingAddress->getStreetFull(),
                      "City"=> $shippingAddress->getCity(),
                      "Comment"=> "ShippingAddress",
                      "Country"=> $shippingAddress->getCountry_id(),
                      //"CustomerID"=> ,
                      "IsDefaultDeliveryAddress"=> 1,
                      "Name"=> "ShippingAddress",
                      "Phone"=> $shippingAddress->getTelephone(),
                      "Zip"=> $shippingAddress->getPostcode(),
                      "Email"=> $shippingAddress->getEmail(),
                      
                       );
                       
         $websaleAddressBilling = array(
                      //"ADDRESSID"=> $customer->,
                      "Address"=> $billingAddress->getStreetFull(),
                      "City"=> $billingAddress->getCity(),
                      "Comment"=> "BillingAddress",
                      "Country"=> $billingAddress->getCountry_id(),
                      //"CustomerID"=> ,
                      "IsDefaultDeliveryAddress"=> 0,
                      "Name"=> "BillingAddress",
                      "Phone"=> $billingAddress->getTelephone(),
                      "Zip"=> $billingAddress->getPostcode(),
                      "Email"=> $billingAddress->getEmail(),
                      
                       );
        $websaleAddresses = array($websaleAddressShipping, $websaleAddressBilling);
        

        $webCustomer = array(
                      "Address"=> $billingAddress->getStreetFull(),
                      "Addresses"=> $websaleAddresses,
                      "AgreedSendEmail"=> 1,
                      "AgreedSendSMS"=> 0,
                      //"CUSTOMERID"=> 0,
                      //"CardNo"=> true,
                      "City"=> $billingAddress->getCity(),
                      "Comment"=> "",
                      "Country"=> $billingAddress->getCountry_id(),
                      "DlvAddress"=> $shippingAddress->getStreetFull(),
                      "DlvCity"=> $shippingAddress->getCity(),
                      "DlvComment"=> "",
                      "DlvName"=> "DeliveryAddress",
                      "DlvPhone"=> $shippingAddress->getTelephone(),
                      "DlvZip"=> $shippingAddress->getPostcode(),
                      "Email"=> $billingAddress->getEmail(),
                      "FirstName"=> $billingAddress->getFirstname(),
                      "LastName"=> $billingAddress->getLastname(),
                      "Phone"=> $billingAddress->getTelephone(),
                      "StdDiscount"=> 0.00,
                      "Zip"=> $billingAddress->getPostcode(),
                       );
                       
         Mage::log($webCustomer);
        
        //Call WebService
        $retval = $clientAuthenticated->InsertCustomer(array('key'=>$fsKey, 'customer'=>$webCustomer));
        
        //Check for errors
        if (is_soap_fault($retval)) {
            Mage::log($retval->faultcode);
            Mage::log($retval->faultstring);
            Mage::log($retval->detail);
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
            throw new Exception($retval->faultstring);
        }
        $fsInsertCustomerInt = $retval->InsertCustomerResult;
        Mage::log('Inserted customer successfully in Front Systems');
        
        return $fsInsertCustomerInt;
    }
    
    
    function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}
    
  
    
    
    
    
    function prettyPrintArray( $array )
    {
        
        
        
?>
<pre>
    <?php
        print_r($array);
    ?>
</pre>
<?php

    }
}?>