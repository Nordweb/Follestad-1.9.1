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
                         "soap_defencoding"=>'UTF-8',
                        "classmap" => array('CardTypeEnum' => 'CardTypeEnum'));
        
        
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