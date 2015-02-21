<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_FrontSystems_Helper_Data extends Mage_Core_Helper_Abstract {



    
    public function AuthenticateFS($classMapIfApplicable)
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
       
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS("CardTypeEnum");
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
        $paymentLine = array(
                      "Amount"=> $orderInstance->grand_total,
                      "CardType"=> "Visa",
                      "Currency"=> "NOK",
                      "ExtRef"=> "ExtRef for NewSale in FS",
                      "LastCompletedStep"=> "Sale",
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
        $saleLine = array(
                      "Identitiy"=> "008160160220",
                      "Price"=> $orderInstance->grand_total,
                      "Qty"=> 1.00,
                      "ShipmentExtId"=> "",
                      "StockID"=> 008160160220, //?
                      "Text"=> "Handsker",
                       );
        $saleLines = array($saleLine);
        
        
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
        $saleObject = array(
                      "Comment"=> "Comment for NewSale in FS",
                      "CustomerID"=> $orderInstance->customer_id, //call getCustomer and possibly InsertCustomer
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

        
        //if (is_soap_fault($retval)) {
        //    trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        //}
        $fsNewSaleResult = $retval->NewSaleResult;
        Mage::log('Registered sale successfully in Front Systems');
        
        
        
        Mage::log('New sale Result:' .$fsNewSaleResult);
        
        echo '<br/><br/>New sale Result:';
        Mage::helper('frontSystems')->prettyPrintArray( $fsNewSaleResult );
        //echo '<br/><br/>';
        
        //todo - Marlk as sold in Magento, assume this, or marked as not sold if something goes wrong?
        //Mage::log('Calling Magento to store');
        //Mage::helper('frontSystems')->StoreProduct($fsWebProducts);
        
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