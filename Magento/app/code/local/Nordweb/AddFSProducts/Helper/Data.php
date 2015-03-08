<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_AddFSProducts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetProductsFromFSBySKU($SKUOfConfigurable)
    {
        
        Mage::log('SKU from Magento is: ');
        Mage::log($SKUOfConfigurable);
    
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Client authenticated');
        
        
        //GetProducts
        //$retval = $clientAuthenticated->GetProducts(array('key'=>$fsKey));
        //if (is_soap_fault($retval)) {
        //    trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        //}
        //$fsWebProducts = $retval->GetProductsResult;
        //Mage::log('Successfully got all products by SKU');
        
        //GetFullProductInfo
        $retval = $clientAuthenticated->GetFullProductInfo(array('key'=>$fsKey, 'productid'=>$SKUOfConfigurable));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $allProductsAndStockCountForThisConfigurableProduct = $retval->GetFullProductInfoResult;
        Mage::log('Successfully got all products by SKU');
        
        //Mage::log(get_class_methods($products->getFirstItem()));
        //Mage::log(get_class_methods($fsWebProducts->Product));
        //Mage::log(get_object_vars($fsWebProducts->Product[0]));
        //echo '' + $fsWebProducts->Product[0]->PRODUCTID; 
        
        
        //$this->prettyPrintArray( $allProductsAndStockCountForThisConfigurableProduct );
        //$this->prettyPrintArray( $fsWebProducts );
        //echo '<br/><br/>';
        
        //Store in Magento
        Mage::log('Calling Magento to store these FS-products as simple products under the calling configurable product');
        $this->StoreSimpleProductsUnderCallingConfigurable($SKUOfConfigurable, $allProductsAndStockCountForThisConfigurableProduct);
        
     
    }
    
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
    

    public function StoreSimpleProductsUnderCallingConfigurable($SKUOfConfigurable, $allProductsAndStockCountForThisConfigurableProduct)
    {
        
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $configurableProductInMagento = Mage::getModel('catalog/product')->loadByAttribute('sku', $SKUOfConfigurable);
        

        
        //Mage::log('$fsWebProducts->Product[0]->IDENTITY: ' .$fsWebProducts->Product[0]->IDENTITY);
        //Mage::log('$productLookup->sku: ' .$productLookup->sku);
        //Mage::log(get_class_methods(Mage::getModel('catalog/product')));
        //Mage::log(get_object_vars(Mage::getModel('catalog/product')));
        
        //if($productLookup == null or $productLookup == '' or $productLookup->sku == null or $productLookup->sku == ''):
            
        //    Mage::log('Product not found, creating it, with SKU ' .$fsWebProducts->Product[0]->IDENTITY);
        
        
//        <xs:complexType name="FullProductInfo">
//<xs:sequence>
//<xs:element minOccurs="0" name="Products" nillable="true" type="tns:ArrayOfProduct"/>
//<xs:element minOccurs="0" name="StockCounts" nillable="true" type="tns:ArrayOfStockCount"/>

 //$this->prettyPrintArray( $allProductsAndStockCountForThisConfigurableProduct->StockCounts );
 
    //get existing simple products and added them to array
    $simpleProducsProductIds = $configurableProductInMagento->getTypeInstance()->getUsedProductIds();
	$newTotalSimpleProductsArray = array();
	foreach ( $simpleProducsProductIds as $existingId ) {
		$newTotalSimpleProductsArray[$existingId] = 1;
	}

       $stockCounts = $allProductsAndStockCountForThisConfigurableProduct->StockCounts;

        //$this->prettyPrintArray($stockCounts->StockCount[0]);
        
        //Foreach($stockCounts->StockCount as $stockItem){ 
        
        //debug
         $stockItem = $stockCounts->StockCount[0];   
         
         $this->prettyPrintArray($stockItem);
         
            try {
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                $simpleProduct = Mage::getModel('catalog/product');
                $simpleProduct
                    ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
                    ->setAttributeSetId(9) //ID of a attribute set named 'default'
                    ->setTypeId('simple') //product type
                    ->setCreatedAt(strtotime('now')) //product creation time
                    ->setSku($stockItem->Identity) //SKU
                    ->setName($configurableProductInMagento->getName()) //product name
                    ->setWeight(1.00)
                    ->setStatus(1) //product status (1 - enabled, 2 - disabled)
                    //->setTaxClassId(4) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
                    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) //catalog and search visibility
                    //->setManufacturer(28) //manufacturer id
                    //->setColor(24)
                    //->setNewsFromDate(strtotime('now')) //product set as new from
                    //->setNewsToDate('06/30/2015') //product set as new to
                    //->setCountryOfManufacture('NO') //country of manufacture (2-letter country code)
                    ->setPrice($configurableProductInMagento->getPrice()) //price in form 11.22
                    //->setCost(22.33) //price in form 11.22
                    //->setSpecialPrice(3.44) //special price in form 11.22
                    //->setSpecialFromDate(strtotime('now')) //special price from (MM-DD-YYYY)
                    //->setSpecialToDate('06/30/2015') //special price to (MM-DD-YYYY)
                    //->setMsrpEnabled(1) //enable MAP
                    //->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
                    //->setMsrp(99.99) //Manufacturer's Suggested Retail Price
                    //->setMetaTitle('Programamtic meta title here')
                    //->setMetaKeyword('Programamtic meta keyword here')
                    //->setMetaDescription('Programamtic meta desc here')
                    //->setDescription('This is a long description')
                    //->setShortDescription('This is a short description')
                    //->setMediaGallery (array('images'=>array (), 'values'=>array ())) //media gallery initialization
                    ->setStockData(array(
                                       'use_config_manage_stock' => 0, //'Use config settings' checkbox
                                       //'manage_stock'=>1, //manage stock
                                       //'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
                                       //'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
                                       'is_in_stock' => 1, //Stock Availability
                                       'qty' => $stockItem->Qty //qty
                                   )
                    );
                    //->setCategoryIds(array(3, 10)); //assign product to categories
                $simpleProduct->save();
                
                //Add to parent configurable product
                $newTotalSimpleProductsArray[$simpleProduct->getId()] = 1;
            }
            catch(Exception $e){
                Mage::log($e->getMessage());
            }
       // }
        
        	
        //Save added simple products from FS
        Mage::getResourceModel('catalog/product_type_configurable')->saveProducts($configurableProductInMagento, array_keys($newTotalSimpleProductsArray));
            
        //else:
        //    Mage::log('Product with SKU already exists: ' . $fsWebProducts->Product[0]->IDENTITY);
            
        //endif;

        Mage::log('138');
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