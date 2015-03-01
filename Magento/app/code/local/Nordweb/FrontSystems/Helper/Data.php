<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_FrontSystems_Helper_Data extends Mage_Core_Helper_Abstract {



    
    public function AuthenticateFS()
    {
        Mage::log('********************* New Call ***********************');

        //Declare some paramaters for our soapclient and create it.
        $headerParams  = array("soap_version"=> SOAP_1_1,
                        "trace"=>1,
                        "exceptions"=>0,
                         "soap_defencoding"=>'UTF-8');
        
        
        $client = new SoapClient('https://dinbutikkdev.frontsystems.no/webshop/WebshopIntegration.svc?wsdl',$headerParams);
        //Mage::log('$client: ' .get_object_vars($client));
        
        
        //Logon
        $retval = $client->Logon(array('username'=>'follestadwebshop', 'password'=>'2*3er6'));
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

        Mage::log('138');
    }
    
    
     public function AddNewSale($orderInstance)
    {
     //[BRANDID_FK] => 4789
     //               [BrandName] => Fingerman
     //               [CATID_FK] => 0
     //               [COLOURID_FK] => 0
     //               [COMPANYID_FK] => 110
     //               [Color] => - - - - 
     //               [Cost] => 139
     //               [GROUPID_FK] => 1759
     //               [Gender] => m
     //               [GroupName] => Accessories
     //               [IDENTITY] => 001861040100
     //               [Ins] => 2013-03-08T11:27:15.557
     //               [IsDiscontinued] => 
     //               [IsNetAvailable] => 1
     //               [IsVisibleWhenStockIsEmpty] => 
     //               [Label] => OS
     //               [Name] => mens coll
     //               [Number] => 2343
     //               [OutPrice] => 499
     //               [PRODUCTID] => 186104
     //               [SEASONID_FK] => 447
     //               [SIZEID_FK] => 526
     //               [SUBGROUPID_FK] => 5466
     //               [SeasonName] => NOS
     //               [Size] => 0
     //               [SizeLabel] => 1
     //               [Subgroup] => Hansker
     //               [Variant] => 
     //               [WebPrice] => 0
     
        //Mage::log(get_class_methods($orderInstance));
        //Mage::log('*************************** orderInstance ****************************');
        //Mage::log($orderInstance->toXml());
        //Mage::log('*************************** orderInstance->customer ****************************');
        //Mage::log($orderInstance->customer->toXml());
        //Mage::log('*************************** orderInstance->quote ****************************');
        //Mage::log($orderInstance->quote->toXml());
        
        //$orderIncrementId = $orderInstance->increment_id;
        //$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        //Mage::log('*************************** $order ****************************');
        //Mage::log($order);
        //Mage::log($order->toXml());
        
      
       
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
        Mage::log('Client authenticated');
        

        
        //<xs:element name="ArrayOfWebSalesPayment" nillable="true" type="tns:ArrayOfWebSalesPayment"/>
        //<xs:complexType name="WebSalesPayment">
        //<xs:sequence>
        //<xs:element minOccurs="0" name="Amount" type="xs:decimal"/>
        //<xs:element minOccurs="0" name="CardType" type="tns:CardTypeEnum"/>
        //<xs:element minOccurs="0" name="Currency" nillable="true" type="xs:string"/>
        //<xs:element minOccurs="0" name="ExtRef" nillable="true" type="xs:string"/>
        //<xs:element minOccurs="0" name="LastCompletedStep" type="tns:LastSuccessfulStepEnum"/>
        //<xs:element minOccurs="0" name="PaymentType" type="tns:PaymentTypeEnum"/>
        //<xs:element minOccurs="0" name="ResponseBody" nillable="true" type="xs:string"/>
        //</xs:sequence>
        //</xs:complexType>
        //<xs:element name="WebSalesPayment" nillable="true" type="tns:WebSalesPayment"/>
        
        Mage::log('*************************** $paymentLine ****************************');
            Mage::log('$orderInstance->grand_total' . $orderInstance->grand_total);
         
            
        $paymentLine = array(
                      "Amount"=> $orderInstance->grand_total,
                      "CardType"=> "Visa",
                      "Currency"=> "NOK",
                      "ExtRef"=> "ExtRef for NewSale in FS",
                      "LastCompletedStep"=> "Capture",
                      "PaymentType"=> "NetAxept",
                      "ResponseBody"=> "ResponseBody here",
                      
                       );
        $paymentLines = array($paymentLine);
        
        $receipt = null; //binary file?
        $saleDateTime = null;//date("Y-m-d H:i:s:u"); //$date->now();
        $saleGuid = Mage::helper('frontSystems')->getGUID();
        
        
//        <xs:element name="ArrayOfWebSalesLine" nillable="true" type="tns:ArrayOfWebSalesLine"/>
//<xs:complexType name="WebSalesLine">
//<xs:sequence>
//<xs:element minOccurs="0" name="Identitiy" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Price" type="xs:decimal"/>
//<xs:element minOccurs="0" name="Qty" type="xs:decimal"/>
//<xs:element minOccurs="0" name="ShipmentExtId" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="StockID" type="xs:int"/>
//<xs:element minOccurs="0" name="Text" nillable="true" type="xs:string"/>
//</xs:sequence>
//</xs:complexType>
//<xs:element name="WebSalesLine" nillable="true" type="tns:WebSalesLine"/>

       
        $saleLines = array();
 

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInstance->increment_id); 
        $ordered_items = $order->getAllItems(); 
 
         
        Foreach($ordered_items as $item){     
             
            Mage::log('*************************** $salesitem ****************************');
            Mage::log('$item->getSku() ' . $item->getSku());
            Mage::log('$item->getPrice() ' . $item->getPriceInclTax());
            Mage::log('$item->getQtyOrdered() ' . $item->getQtyOrdered());
            Mage::log('$item->getName() ' . $item->getName());
            Mage::log($item->toXml());
            //Mage::log(get_object_vars($item));
            
            
             $saleLine = array(
                      "Identitiy"=> $item->getSku(), //$item->getItemId()
                      "Price"=> $item->getPriceInclTax(),
                      "Qty"=> $item->getQtyOrdered(),
                      "ShipmentExtId"=> "",
                      //"StockID"=> $item->getSku(), //?
                      "Text"=> $item->getName(),
                       );
            array_push($saleLines, $saleLine);
        } 
       
        
        
//        <xs:element name="ArrayOfWebShipment" nillable="true" type="tns:ArrayOfWebShipment"/>
//<xs:complexType name="WebShipment">
//<xs:sequence>
//<xs:element minOccurs="0" name="ExtID" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Price" type="xs:decimal"/>
//<xs:element minOccurs="0" name="Provider" type="tns:ShipmentProviderEnum"/>
//<xs:element minOccurs="0" name="RegisteredDateTime" type="xs:dateTime"/>
//<xs:element minOccurs="0" name="ReturnLabel" nillable="true" type="xs:base64Binary"/>
//<xs:element minOccurs="0" name="ShipmentLabel" nillable="true" type="xs:base64Binary"/>
//<xs:element minOccurs="0" name="TrackingURL" nillable="true" type="xs:string"/>
//</xs:sequence>
//</xs:complexType>
//<xs:element name="WebShipment" nillable="true" type="tns:WebShipment"/>
        

//xs:simpleType name="ShipmentProviderEnum">
//<xs:restriction base="xs:string">
//<xs:enumeration value="Posten"/>
//<xs:enumeration value="MyPack"/>
//<xs:enumeration value="InStore"/>
//<xs:enumeration value="PickupPoint"/>
//</xs:restriction>
//</xs:simpleType>
         $shipment = array(
                      "ExtID"=> "Some extID",
                      "Price"=> 50.00,
                      "Provider"=> "Posten",
                      //"RegisteredDateTime"=> "",
                      "ReturnLabel"=> null,
                      "ShipmentLabel"=> null,
                      "TrackingURL"=> "http://www.dev.follestad.no/trackingUrlHere",
                       );
        //$shipments = array($shipment);
        $shipments = null;
      
       
        
        //<xs:complexType name="Websale">
        //<xs:sequence>
        //<xs:element minOccurs="0" name="Comment" nillable="true" type="xs:string"/>
        //<xs:element minOccurs="0" name="CustomerID" type="xs:int"/>
        //<xs:element minOccurs="0" name="DeliveryAddressID" type="xs:int"/>
        //<xs:element minOccurs="0" name="ExtRef" nillable="true" type="xs:string"/>
        //<xs:element minOccurs="0" name="InvoiceAddressID" type="xs:int"/>
        //<xs:element minOccurs="0" name="IsComplete" type="xs:boolean"/>
        //<xs:element minOccurs="0" name="IsVoided" type="xs:boolean"/>
        //<xs:element minOccurs="0" name="PaymentLines" nillable="true" type="tns:ArrayOfWebSalesPayment"/>
        //<xs:element minOccurs="0" name="Receipt" nillable="true" type="xs:base64Binary"/>
        //<xs:element minOccurs="0" name="SaleDateTime" type="xs:dateTime"/>
        //<xs:element minOccurs="0" name="SaleGuid" type="ser:guid"/>
        //<xs:element minOccurs="0" name="SalesLines" nillable="true" type="tns:ArrayOfWebSalesLine"/>
        //<xs:element minOccurs="0" name="Shipments" nillable="true" type="tns:ArrayOfWebShipment"/>
        //</xs:sequence>
        //</xs:complexType>
        $fsWebCustomer = $this->GetCustomer($orderInstance->customer_email);
        if(empty($fsWebCustomer)) //Insert customer
        {
            $resultInt = $this->InsertCustomer($orderInstance);
            $fsWebCustomer = $this->GetCustomer($orderInstance->customer_email);
        }
        
        $saleObject = array(
                      "Comment"=> "Comment for NewSale in FS",
                      "CustomerID"=> $fsWebCustomer->CUSTOMERID, //call getCustomer and possibly InsertCustomer
                      "DeliveryAddressID"=> 0,
                      "ExtRef"=> "ExtRef for NewSale in FS",
                      "InvoiceAddressID"=> 0,
                      "IsComplete"=> true,
                      "IsVoided"=> false,
                      "PaymentLines"=> $paymentLines,
                      "Receipt"=> $receipt,
                      //"SaleDateTime"=> $saleDateTime,
                      "SaleGuid"=> $saleGuid,
                      "SalesLines"=> $saleLines,
                      "Shipments"=> $shipments,
                       );
        
        
        
        
        
        
        $retval = $clientAuthenticated->NewSale(array('key'=>$fsKey, 'sale'=>$saleObject));
        
        //Mage::log('retval:');
        //Mage::log($retval);
        //  Mage::log('get_class_methods:');
        //Mage::log(get_class_methods($retval));
        //  Mage::log('get_object_vars:');
        //Mage::log(get_object_vars($retval));
        
        Mage::log($retval->faultcode);
        Mage::log($retval->faultstring);
        Mage::log($retval->detail);

        
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
        Mage::helper('frontSystems')->prettyPrintArray( $fsNewSaleResult );
        //echo '<br/><br/>';
        
        //todo - Mark as sold in Magento, assume this, or marked as not sold if something goes wrong?
        //Mage::log('Calling Magento to store');
        //Mage::helper('frontSystems')->StoreProduct($fsWebProducts);
        
    }
    
    public function GetCustomer($email)
    {
        
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
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
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS("CardTypeEnum");
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
        Mage::log('Client authenticated');
        

        
     //<xs:complexType name="WebsaleAddress">
//<xs:sequence>
//<xs:element minOccurs="0" name="ADDRESSID" type="xs:int"/>
//<xs:element minOccurs="0" name="Address" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="City" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Comment" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Country" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="CustomerID" type="xs:int"/>
//<xs:element minOccurs="0" name="IsDefaultDeliveryAddress" type="xs:boolean"/>
//<xs:element minOccurs="0" name="Name" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Phone" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Zip" nillable="true" type="xs:string"/>



$billingAddress = $orderInstance->getBillingAddress();
$shippingAddress = $orderInstance->getShippingAddress();

        $websaleAddress = array(
                      //"ADDRESSID"=> $customer->,
                      "Address"=> $shippingAddress->getStreetFull(),
                      "City"=> $shippingAddress->getCity(),
                      "Comment"=> "Comment for customer address here",
                      "Country"=> $shippingAddress->getCountry_id(),
                      //"CustomerID"=> ,
                      "IsDefaultDeliveryAddress"=> 1,
                      "Name"=> "ShippingAddress",
                      "Phone"=> $shippingAddress->getTelephone(),
                      "Zip"=> $shippingAddress->getPostcode(),
                      
                       );
        $websaleAddresses = array($websaleAddress);
        
      
        
        
//    <xs:complexType name="WebCustomer">
//<xs:sequence>
//<xs:element minOccurs="0" name="Address" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Addresses" nillable="true" type="tns:ArrayOfWebsaleAddress"/>
//<xs:element minOccurs="0" name="AgreedSendEmail" type="xs:boolean"/>
//<xs:element minOccurs="0" name="AgreedSendSMS" type="xs:boolean"/>
//<xs:element minOccurs="0" name="CUSTOMERID" type="xs:int"/>
//<xs:element minOccurs="0" name="CardNo" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="City" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Comment" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Country" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="DlvAddress" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="DlvCity" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="DlvComment" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="DlvName" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="DlvPhone" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="DlvZip" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Email" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="FirstName" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="LastName" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="Phone" nillable="true" type="xs:string"/>
//<xs:element minOccurs="0" name="StdDiscount" type="xs:decimal"/>
//<xs:element minOccurs="0" name="Zip" nillable="true" type="xs:string"/>

//CUSTOMER XML
//<password><![CDATA[beliefs3]]></password>
//<group_id><![CDATA[1]]></group_id>
//<tax_class_id><![CDATA[3]]></tax_class_id>
//<firstname><![CDATA[Rune]]></firstname>
//<lastname><![CDATA[Horneland]]></lastname>
//<email><![CDATA[email@runehorneland.com]]></email>
//<telephone><![CDATA[99107868]]></telephone>
//<country_id><![CDATA[NO]]></country_id>
//<city><![CDATA[Oslo]]></city>
//<postcode><![CDATA[1156]]></postcode>
//<region_id><![CDATA[1]]></region_id>
//<region><![CDATA[-]]></region>
//<customer_password><![CDATA[beliefs3]]></customer_password>
//<confirm_password><![CDATA[beliefs3]]></confirm_password>
//<save_in_address_book><![CDATA[1]]></save_in_address_book>
//<use_for_shipping><![CDATA[1]]></use_for_shipping>
//<prefix><![CDATA[]]></prefix>
//<middlename><![CDATA[]]></middlename>
//<suffix><![CDATA[]]></suffix>
//<dob><![CDATA[]]></dob>
//<taxvat><![CDATA[]]></taxvat>
//<gender><![CDATA[]]></gender>
//<password_hash><![CDATA[286844fa1b68ae5cfb05e526fe5a5b89:uj0c8Kl5o9yHyULiQX54l6L4LwsB3UCO]]></password_hash>
//<password_confirmation><![CDATA[]]></password_confirmation>
//<store_id><![CDATA[1]]></store_id>
//<entity_type_id><![CDATA[1]]></entity_type_id>
//<parent_id><![CDATA[0]]></parent_id>
//<created_at><![CDATA[2015-02-22 13:01:19]]></created_at>
//<updated_at><![CDATA[2015-02-22 13:01:19]]></updated_at>
//<created_in><![CDATA[Follestad default view]]></created_in>
//<website_id><![CDATA[1]]></website_id>
//<disable_auto_group_change><![CDATA[0]]></disable_auto_group_change>
//<dob_is_formated><![CDATA[1]]></dob_is_formated>
//<confirmation><![CDATA[]]></confirmation>
//<entity_id><![CDATA[3393]]></entity_id>
//<default_billing><![CDATA[1146]]></default_billing>
//<default_shipping><![CDATA[1146]]></default_shipping>

//ROOT XML
//<billing_address_id><![CDATA[6559]]></billing_address_id>
//<shipping_address_id><![CDATA[6560]]></shipping_address_id>
      

        
        $webCustomer = array(
                      "Address"=> $billingAddress->getStreetFull(),
                      "Addresses"=> $websaleAddresses,
                      "AgreedSendEmail"=> 1,
                      "AgreedSendSMS"=> 0,
                      //"CUSTOMERID"=> 0,
                      //"CardNo"=> true,
                      "City"=> $billingAddress->getCity(),
                      "Comment"=> "Comment for customer here",
                      "Country"=> $billingAddress->getCountry_id(),
                      "DlvAddress"=> $shippingAddress->getStreetFull(),
                      "DlvCity"=> $shippingAddress->getCity(),
                      "DlvComment"=> "Comment deliveryAddress here",
                      "DlvName"=> "DeliveryAddress",
                      "DlvPhone"=> $shippingAddress->getTelephone(),
                      "DlvZip"=> $shippingAddress->getPostcode(),
                      "Email"=> $orderInstance->customer->email,
                      "FirstName"=> $orderInstance->customer->firstname,
                      "LastName"=> $orderInstance->customer->lastname,
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