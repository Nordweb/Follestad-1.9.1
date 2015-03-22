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
        
        
        //deleting existing products
        $this->deleteAllExistingSimpleProductsBelongingToThis($configurableProductInMagento);
        

        
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
    //$simpleProductsProductIds = $configurableProductInMagento->getTypeInstance()->getUsedProductIds();
    //$newTotalSimpleProductsArray = array();
    //foreach ( $simpleProductsProductIds as $existingId ) {
    //    $newTotalSimpleProductsArray[$existingId] = 1;
    //}

       //$stockCounts = $allProductsAndStockCountForThisConfigurableProduct->StockCounts;

        //$this->prettyPrintArray($stockCounts->StockCount[0]);
        
        //ToDo - Need to foreach both products and their stockcounts
        //Foreach($stockCounts->StockCount as $stockItem){ 
        
        //debug
         //$stockItem = $stockCounts->StockCount[0];   
         //$this->prettyPrintArray($stockItem);
         
         //   try {
         //       Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
         //       $simpleProduct = Mage::getModel('catalog/product');
         //       $simpleProduct
         //           ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
         //           ->setAttributeSetId(4) //ID of a attribute set named 'default'
         //           ->setTypeId('simple') //product type
         //           ->setCreatedAt(strtotime('now')) //product creation time
         //           ->setSku($stockItem->Identity) //SKU
         //           ->setName($configurableProductInMagento->getName() . "-L") //product name
         //           ->setWeight(1.00)
         //           ->setStatus(1) //product status (1 - enabled, 2 - disabled)
         //           ->setTaxClassId($configurableProductInMagento->getTaxClassId()) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
         //           ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) //catalog and search visibility
         //           //->setManufacturer(28) //manufacturer id
         //           //->setColor(24)
         //           //->setNewsFromDate(strtotime('now')) //product set as new from
         //           //->setNewsToDate('06/30/2015') //product set as new to
         //           //->setCountryOfManufacture('NO') //country of manufacture (2-letter country code)
         //           ->setPrice($configurableProductInMagento->getPrice()) //price in form 11.22
         //           //->setCost(22.33) //price in form 11.22
         //           //->setSpecialPrice(3.44) //special price in form 11.22
         //           //->setSpecialFromDate(strtotime('now')) //special price from (MM-DD-YYYY)
         //           //->setSpecialToDate('06/30/2015') //special price to (MM-DD-YYYY)
         //           //->setMsrpEnabled(1) //enable MAP
         //           //->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
         //           //->setMsrp(99.99) //Manufacturer's Suggested Retail Price
         //           //->setMetaTitle('Programamtic meta title here')
         //           //->setMetaKeyword('Programamtic meta keyword here')
         //           //->setMetaDescription('Programamtic meta desc here')
         //           ->setDescription($configurableProductInMagento->getDescription())
         //           ->setShortDescription($configurableProductInMagento->getShortDescription())
         //           //->setMediaGallery (array('images'=>array (), 'values'=>array ())) //media gallery initialization
         //           ->setStockData(array(
         //                              'use_config_manage_stock' => 1, //'Use config settings' checkbox
         //                              //'manage_stock'=>1, //manage stock
         //                              //'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
         //                              //'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
         //                              'is_in_stock' => 1, //Stock Availability
         //                              'qty' => $stockItem->Qty //qty
         //                          )
         //           );
         //           //->setCategoryIds(array(3, 10)); //assign product to categories
         //       //$simpleProduct->save();
                
         //         $simpleProductData = array();
         //          $simpleProductData = array( //[$simpleProduct->getId()] = id of a simple product associated with this configurable
         //           '0' => array(
         //               'label' => "L",//substr($stockItem->Identity, strlen($stockItem->Identity)-5, 2), //attribute label
         //               'attribute_id' => '136', //attribute ID of attribute SIZE (FOLLESTAD) in my store
         //               'value_index' => '3', //value of 'Green' index of the attribute 'color'
         //               //'is_percent' => '0', //fixed/percent price for this option
         //               //'pricing_value' => '21' //value for the pricing
         //           )
         //       );
         //       $simpleProduct->setConfigurableProductsData($simpleProductData);
         //       $simpleProduct->save();
                
                
         //       //Add to parent configurable product
         //       $newTotalSimpleProductsArray[$simpleProduct->getId()] = 1;
                
         //       /**/
         //       /** assigning associated product to configurable */
         //       /**/
         //       //$configurableProductInMagento->getTypeInstance()->setUsedProductAttributeIds(array(136)); //attribute ID of attribute 'SIZE (FOLLESTAD)' in my store
         //       //$configurableAttributesData = $configurableProductInMagento->getTypeInstance()->getConfigurableAttributesAsArray();
 
         //       //$configurableProductInMagento->setCanSaveConfigurableAttributes(true);
         //       //$configurableProductInMagento->setConfigurableAttributesData($configurableAttributesData);
 
         //       //$configurableProductsData = $configurableProductInMagento->getConfigurableProductsData();//array();
         //       // Mage::log('Existing $configurableProductsData:');
         //       // Mage::log($configurableProductsData);
                 
         //       //$configurableProductsData[$simpleProduct->getId()] = array( //[$simpleProduct->getId()] = id of a simple product associated with this configurable
         //       //    '0' => array(
         //       //        'label' => "L",//substr($stockItem->Identity, strlen($stockItem->Identity)-5, 2), //attribute label
         //       //        'attribute_id' => '136', //attribute ID of attribute SIZE (FOLLESTAD) in my store
         //       //        'value_index' => '3', //value of 'Green' index of the attribute 'color'
         //       //        //'is_percent' => '0', //fixed/percent price for this option
         //       //        //'pricing_value' => '21' //value for the pricing
         //       //    )
         //       //);
                
         //       Mage::log('label');
         //       Mage::log(substr($stockItem->Identity, strlen($stockItem->Identity)-4, 2));
         //       Mage::log('value_index');
         //       Mage::log(substr($stockItem->Identity, strlen($stockItem->Identity)-2, 2));
         //       Mage::log('changed $configurableProductsData:');
         //       Mage::log($configurableProductsData);
                
         //       //$configurableProductInMagento->setConfigurableProductsData($configurableProductsData);
         //       //$configurableProductInMagento->save();
            
         //   }
         //   catch(Exception $e){
         //       Mage::log($e->getMessage());
         //   }
       // }
        
        	
        //Save added simple products from FS
        //Mage::getResourceModel('catalog/product_type_configurable')->saveProducts($configurableProductInMagento, array_keys($newTotalSimpleProductsArray));
        
        
        
        
            
        //else:
        //    Mage::log('Product with SKU already exists: ' . $fsWebProducts->Product[0]->IDENTITY);
            
        //endif;

        //Mage::log('138');
        
        
        // There's some more advanced logic above the foreach loop which determines how to define $configurable_attribute, // which is beyond the scope of this article. For reference purposes, I'm hard coding a value for // $configurable_attribute here, and it's associated numerical attribute ID... 

        $configurable_attribute = "size"; 
        $attr_id = 136; 
        $simpleProducts = array(); 
        

        // Loop through a pre-populated array of data gathered from the CSV files (or database) of old system.. 
        //foreach ($main_product_data['simple_products'] as $simple_product_data) {
        
            //debug
            $stockCounts = $allProductsAndStockCountForThisConfigurableProduct->StockCounts;
            $stockItem = $stockCounts->StockCount[0];   
            $attr_value = "L";
        
            // Again, I have more logic to determine these fields, but for clarity, I'm still including the variables here hardcoded.. $attr_value = $simple_product_data['size']; 
            $attr_id = 136;   

            // We need the actual option ID of the attribute value ("XXL", "Large", etc..) so we can assign it to the product model later.. 
            // The code for getAttributeOptionValue and addAttributeOption is part of another article (linked below this code snippet) 
            $configurableAttributeOptionId = $this->getAttributeOptionValue($configurable_attribute, $attr_value); 
            if (!$configurableAttributeOptionId) { 
                $configurableAttributeOptionId = $this->addAttributeOption($configurable_attribute, $attr_value); 
            }   

            // Create the Magento product model 
            $sProduct = Mage::getModel('catalog/product'); 
            $sProduct 
                ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) 
                ->setWebsiteIds(array(1)) 
                ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED) 
                ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) 
                //->setAttributeSetId($_attributeSetMap[$data['product_type']]['attribute_set']) 
                ->setData($configurable_attribute, $configurableAttributeOptionId) 
                ->setAttributeSetId(4) //ID of a attribute set named 'default'
                ->setCreatedAt(strtotime('now')) //product creation time
                ->setSku($stockItem->Identity) //SKU
                ->setName("[FS] " . $configurableProductInMagento->getName() . "-L") //product name
                ->setWeight(1.00)
                ->setTaxClassId($configurableProductInMagento->getTaxClassId()) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
                ->setPrice($configurableProductInMagento->getPrice()) //price in form 11.22
                ->setDescription($configurableProductInMagento->getDescription())
                ->setShortDescription($configurableProductInMagento->getShortDescription())
                ->setStockData(array(
                                    'use_config_manage_stock' => 1, //'Use config settings' checkbox
                                    //'manage_stock'=>1, //manage stock
                                    //'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
                                    //'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
                                    'is_in_stock' => 1, //Stock Availability
                                    'qty' => $stockItem->Qty //qty
                                )
            );
            
           
            // Set the stock data. Let Magento handle this as opposed to manually creating a cataloginventory/stock_item model.. 
            //$sProduct->setStockData(array( 
            //    'is_in_stock' => 1, 
            //    'qty' => 99999 
            //    )
            //);   

            $sProduct->save();   

            // Store some data for later once we've created the configurable product, so we can 
            // associate this simple product to it later.. 
            array_push( 
                $simpleProducts, 
                    array( 
                        "id" => $sProduct->getId(), 
                        "price" => $sProduct->getPrice(), 
                        "attr_code" => $configurable_attribute, 
                        "attr_id" => $attr_id, 
                        "value" => $configurableAttributeOptionId, 
                        "label" => $attr_value 
                    ) 
            );   

            
            /******************************** Configurable stuff ********************************/
            
            
            $configurableProductInMagento->setCanSaveConfigurableAttributes(true);
            $configurableProductInMagento->setCanSaveCustomOptions(true);
           
            $cProductTypeInstance = $configurableProductInMagento->getTypeInstance(); 
            // This array is is an array of attribute ID's which the configurable product swings around (i.e; where you say when you 
            // create a configurable product in the admin area what attributes to use as options) 
            // $_attributeIds is an array which maps the attribute(s) used for configuration so their numerical counterparts. 
            // (there's probably a better way of doing this, but i was lazy, and it saved extra db calls); 
            //$_attributeIds = array("size" => 999, "color", => 1000, "material" => 1001); // etc..   
            $_attributeIds = array("size" => 136);   

            // Now we need to get the information back in Magento's own format, and add bits of data to what it gives us.. 
            $attributes_array = $cProductTypeInstance->getConfigurableAttributesAsArray(); 
            foreach($attributes_array as $key => $attribute_array) { 
                $attributes_array[$key]['use_default'] = 1; 
                $attributes_array[$key]['position'] = 0;   
                if (isset($attribute_array['frontend_label'])) { 
                    $attributes_array[$key]['label'] = $attribute_array['frontend_label']; 
                } 
                else { 
                    $attributes_array[$key]['label'] = $attribute_array['attribute_code']; 
                } 
            }   

            // Add it back to the configurable product.. 
            $configurableProductInMagento->setConfigurableAttributesData($attributes_array);   

            // Remember that $simpleProducts array we created earlier? Now we need that data.. 
            $dataArray = array(); 
            foreach ($simpleProducts as $simpleArray) { 
                $dataArray[$simpleArray['id']] = array(); 
                foreach ($attributes_array as $attrArray) { 
                    array_push( 
                        $dataArray[$simpleArray['id']], 
                            array( 
                                "attribute_id" => $simpleArray['attr_id'], 
                                "label" => $simpleArray['label'], 
                                "is_percent" => false, 
                                "pricing_value" => $simpleArray['price'] 
                            ) 
                    ); 
                } 
            }   

            // This tells Magento to associate the given simple products to this configurable product.. 
            $configurableProductInMagento->setConfigurableProductsData($dataArray);   

            // Finally...! 
            $configurableProductInMagento->save();



        //}
    }
    
    public function getAttributeOptionValue($arg_attribute, $arg_value) { 
        $attribute_model = Mage::getModel('eav/entity_attribute'); 
        $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;   
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute); 
        $attribute = $attribute_model->load($attribute_code);   
        $attribute_table = $attribute_options_model->setAttribute($attribute); 
        $options = $attribute_options_model->getAllOptions(false);   
    
        foreach($options as $option) { 
            if ($option['label'] == $arg_value) { 
                return $option['value']; 
            } 
        }   
        return false; 
    }

    public function addAttributeOption($arg_attribute, $arg_value) { 
        $attribute_model = Mage::getModel('eav/entity_attribute'); 
        $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;   
        
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute); 
        $attribute = $attribute_model->load($attribute_code);   
       
        $attribute_table = $attribute_options_model->setAttribute($attribute); 
        $options = $attribute_options_model->getAllOptions(false);   
        
        $value['option'] = array($arg_value,$arg_value); 
        $result = array('value' => $value); 
        $attribute->setData('option',$result); 
        $attribute->save();   
        
        return $this->getAttributeOptionValue($arg_attribute, $arg_value); 
    }
    
    public function deleteAllExistingSimpleProductsBelongingToThis($_product) { 
    
        if($_product->getTypeId() == "configurable") {
	        $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
	        $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
	        foreach($simple_collection as $simple_product){
	           try{     
                    Mage::log("Deleting product: " . $simple_product->getId());
                    Mage::getModel("catalog/product")->load( $simple_product->getId()  )->delete(); 
               }
               catch(Exception $e)
               {     
                    Mage::log("Delete failed");
                    Mage::log($e->getMessage());
         
               }
	        }
        }

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