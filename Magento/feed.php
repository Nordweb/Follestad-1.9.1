<?php
//header ("Content-type: text/xml; charset=utf-8");
header ("Content-type: text/plain; charset=utf-8");

// Set no time limit only if php is not running in Safe Mode
if (!ini_get("safe_mode")) {
	set_time_limit(0);
}
ignore_user_abort();
error_reporting(E_ALL^E_NOTICE);
$_SVR = array();

$path_include = "app/Mage.php";


// Include configuration file
if(!file_exists($path_include)) {
	exit();
}
else {
	require_once $path_include;
}

// Get list of stores
if (isset($_GET['show_stores']) && ($_GET['show_stores'] == 'on') && 0) {
	
	$stores = Mage::app()->getStores();
	foreach ($stores as $i) {
	//	print $i->getId() . " : " . $i->getCode() . "<br />";
	//	print  Mage::app()->getStore($i)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "<br />";
	//	print "--------------------------------------<br />";
	}
	exit;
}

// Get store
if (isset($_GET['store']) && ($_GET['store'] != "")) {
	$stores = Mage::app()->getStores();
	foreach ($stores as $i) {
		if ($i->getCode() == $_GET['store']) {
			$storeId = $i->getId();
		}
	}
}
// Get default store
if (!isset($storeId)) {
	// Get store ID use this to filter products
	$storeId = Mage::app()->getStore()->getId();
}

$websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
$custGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();

// URL options - possible values on, off
if (isset($_GET['url_path'])){
	$url_path = ($_GET['url_path'] == "on") ? "on" : "off";
}
else {
	$url_path = "off";
}

// Datafeed specific settings
$datafeed_separator = "|"; // Possible options are \t or |

$get_brand_enabled = 1; // 1 = enabled ; 0 = disabled

$endrecord = '<EOL>';

// Define VAT value
$vat_value = 1.24; // 24%

// Description options - possible values on, off
if (isset($_GET['description'])){
	$show_description = ($_GET['description'] == "off") ? "off" : "on";
}
else {
	$show_description = "on";
}

if (isset($_GET['currency'])) {
	$currency_code = $_GET['currency'];
	$convert_price_to_currency = 1;
}

// Image options - possible values on, off
if (isset($_GET['image'])){
	$show_image = ($_GET['image'] == "off") ? "off" : "on";
}
else {
	$show_image = "on";
}

// Add VAT to prices
if (isset($_GET['add_vat'])){
	$add_vat = ($_GET['add_vat'] == "on") ? "on" : "off";
}
else {
	$add_vat = "off";
}

// Force special price
if (isset($_GET['specialprice'])){
	$specialprice = ($_GET['specialprice'] == "on") ? "on" : "off";
}
else {
	$specialprice = "off";
}

// Get current date
$datetime = date("Y-m-d G:i:s");

try{
	
	// Get shop currency
	$default_currency = Mage::getModel('directory/currency')->getConfigBaseCurrencies();
	$datafeed_default_currency = $default_currency[0];

	if (@$convert_price_to_currency == 1) {
		$currency_value_rate = Mage::getModel('directory/currency')->getCurrencyRates($datafeed_default_currency, $currency_code);
		$datafeed_currency = $currency_code;
	}
	else {
		$datafeed_currency = $datafeed_default_currency;
	}

	$CAT = getCategories();
	
	$GROUPED = array();
	$grouped_prodIds = array();
	$bundle_prodIds = array();
	$conf_prodIds = array();
	$prodIds = array();

	// Get grouped products
	$grouped_products = Mage::getModel('catalog/product')->getCollection();
	$grouped_products->addAttributeToFilter('status', 1);//enabled
	$grouped_products->addAttributeToFilter('type_id', 'grouped');//catalog, search
	$grouped_products->addAttributeToSelect('*');
	$grouped_products->addStoreFilter($storeId); 
	$grouped_prodIds = $grouped_products->getAllIds();
		
	
	foreach($grouped_prodIds as $grouped_productId) { 
		
		$grouped_product = Mage::getModel('catalog/product');
		$grouped_product->load($grouped_productId);
		$grouped_product->getLinkInstance()->useGroupedLinks();
		foreach ($grouped_product->getGroupedLinkCollection() as $_link) {
			$GROUPED[$_link->getLinkedProductId()] = $grouped_productId;
		}
	}
	
	// Get bundle products
	$bundle_products = Mage::getModel('catalog/product')->getCollection();
	$bundle_products->addAttributeToFilter('status', 1);//enabled
	$bundle_products->addAttributeToFilter('type_id', 'bundle');//catalog, search
	$bundle_products->addAttributeToSelect('*');
	$bundle_products->addStoreFilter($storeId);
	$bundle_prodIds = $bundle_products->getAllIds();
		
	foreach($bundle_prodIds as $bundle_productId) {
		
		$bundle_product = Mage::getModel('catalog/product');
		$bundle_product->load($bundle_productId);
		$bundle_product->getLinkInstance()->useRelatedLinks();
		foreach ($bundle_product->getRelatedLinkCollection() as $_link) {
			$GROUPED[$_link->getLinkedProductId()] = $bundle_productId;
		}

	}
	
	// Get configurable products
	$conf_products = Mage::getModel('catalog/product')->getCollection();
	$conf_products->addAttributeToFilter('status', 1);//enabled
	$conf_products->addAttributeToFilter('type_id', 'configurable');//catalog, search
	$conf_products->addAttributeToSelect('*');
	$conf_products->addStoreFilter($storeId);
	$conf_prodIds = $conf_products->getAllIds();
		
	foreach($conf_prodIds as $conf_productId) { 
		$conf_product = Mage::getModel('catalog/product');
		$conf_product->load($conf_productId);
		$conf_product->getLinkInstance()->useRelatedLinks();
		foreach ($conf_product->getRelatedLinkCollection() as $_link) {
			$GROUPED[$_link->getLinkedProductId()] = $conf_productId;
		}

	}
	
	// ******************************************************************************
	// Get the products
	$products = Mage::getModel('catalog/product')->getCollection();
	$products->addAttributeToFilter('status', 1);//enabled
	$products->addAttributeToFilter('visibility', 4); //catalog, search
	$products->addAttributeToFilter('type_id', 'simple'); //catalog, search
	$products->addAttributeToSelect('*');
	$products->addStoreFilter($storeId);
	$prodIds = $products->getAllIds();

	// Merge all products simpe, configurable, bundle, grouped
	$ALL_PRODS = array();
	$ALL_PRODS = array_merge($prodIds, $grouped_prodIds, $bundle_prodIds, $conf_prodIds);
	
	// Array to check if product is already send
	$already_sent = array();
	
	echo '<?xml version="1.0" encoding="UTF-8"?>
<products>' . "\n";


	foreach($ALL_PRODS as $productId) {

		// If we've sent this one, skip the rest - this is to ensure that we do not get duplicate products
		if (@$already_sent[$productId] == 1) continue;

		$PRODUCT = array();
		$PRODUCT = function_to_get_product_details($productId);
		
		
/*	echo $PRODUCT['prod_name'] . $datafeed_separator .
	     $PRODUCT['brand'] . $datafeed_separator .
	     $PRODUCT['prod_desc'] . $datafeed_separator .
	     $PRODUCT['prod_short_desc'] . $datafeed_separator .
	     $PRODUCT['prod_price'] . $datafeed_separator .
        $PRODUCT['prod_sku'] . $datafeed_separator .
	     $PRODUCT['prod_id'] . $datafeed_separator .
	     $PRODUCT['prod_url'] . $datafeed_separator .
	     $PRODUCT['availability'] . $datafeed_separator .
	     $PRODUCT['category_name'] . $datafeed_separator .
	     $PRODUCT['prod_image'] . $datafeed_separator .
	     $PRODUCT['spese_sped'] . $datafeed_separator .
	     $PRODUCT['manufacturer'] . $datafeed_separator .	
	     $endrecord."\n";	*/
	 /*   echo '<pre>';
	    print_r($PRODUCT);	
		echo '</pre>';	
		die();*/
		$category = $PRODUCT['category_name'];
		$category = explode('>', $category);
		if(is_array($category)) {
			$category = trim(end($category));
		}
		$image = $PRODUCT['prod_image'];
		$img_type = end(explode('.', $image));
		
   	 echo '<product id="' . $PRODUCT['prod_id'] . '">' . "\n"
		. '	<name>' . nor_to_html($PRODUCT['prod_name']) . '</name>' . "\n"
		. '	<brand>' . nor_to_html($PRODUCT['brand']) . '</brand>' . "\n"
		. '	<description>' . nor_to_html($PRODUCT['prod_desc']) . '</description>' . "\n"
		. '	<price currency="NOK">' . $PRODUCT['prod_price'] . '</price>' . "\n"
		. '	<images>' . "\n"
		. '		<image url="' . $PRODUCT['prod_image'] . '" />' . "\n"
		. '	</images>' . "\n"
		. '	<categories>' . "\n"
		. '		<category>' . nor_to_html(htmlspecialchars($PRODUCT['category_name'])) . '</category>' . "\n"
		. '	</categories>' . "\n"
		. '	<link>' . $PRODUCT['prod_url'] . '</link>' . "\n"
		. '</product>' . "\n";

		$already_sent[$productId] = 1;
	}
	echo '</products>' . "\n";
	die();
}
catch(Exception $e){
	die($e->getMessage());
}

function function_to_get_product_details($product_id) {


	global $Mage, $show_description, $show_image, $add_vat, $datafeed_separator, $CAT, $specialprice, $url_path;

	$RESULT = array();

	$product = Mage::getModel('catalog/product');
	$stockItem = Mage::getModel('cataloginventory/stock_item');
	$product->load($product_id);
	$quantita  = 0;
	$quantita_minima  = 0;
	$availability = "";
	
	
	//gestione disponibilita
	   switch ($product->getTypeId()){

         case "simple" :       // Shipping costs flat

            if($product->getStockItem())
	         {
	           $stockItem->loadByProduct($product->getId());
	
	
	          if($stockItem->getIsInStock())
	           {
	              $quantita_minima = $stockItem->getMinQty();
	              $quantita = $stockItem->getQty();
	            //  echo $quantita_minima;
	             // echo $quantita;
	              //
   	
   	           if ($quantita < $quantita_minima)
   	           {
   	               //$availability= "1";
   	               $availability = "1";
   	           }
   	           else
   	           {
   	              $availability = "1";
   	           }
	

	           }
	           else
	           {
   	
                $availability= "6";
	           }

	         }
	         else
	         {
	         $availability = "3";
	         }
	
            break;

         case "configurable" :       // shipping costs based on the number of objects
              $availability = "3";
            break;

         case "bundle" :      //Shipping costs based on cost or based on weight
              $availability = "3";
            break;

            case "grouped" :      //Shipping costs based on cost or based on weight
             $availability = "3";
            break;



    }
	

	//finer availability management
	
	$prod_model = $product->getSku();	
	$prod_id = $product->getId();
	$prod_name = $product->getName();
	
	if   ($show_description == "off")
	{
	    $prod_desc = "";
	    $prof_short_desc = "";
	}
	else
	{


        $descrizionehtml = $product->getDescription();
        $descrizionehtml=strip_tags($descrizionehtml);
        $prod_desc = html_entity_decode($descrizionehtml, ENT_QUOTES, 'UTF-8');
        $prod_desc = CleanHtml($prod_desc);
        $prod_desc = strip_tags($prod_desc);
        $prod_desc = substr($prod_desc,0,255);



        $descrizionehtml = $product->getShortDescription();
        $descrizionehtml=strip_tags($descrizionehtml);
        $prod_short_desc = html_entity_decode($descrizionehtml, ENT_QUOTES, 'UTF-8');
        $prod_short_desc = CleanHtml($prod_short_desc);
        $prod_short_desc = strip_tags($prod_short_desc);
        $prod_short_desc = substr($prod_short_desc,0,255);
	
	}


	$prod_attribute_id = $product->getAttributeSetId();
	$prod_image = ($show_image == "off") ? "" : Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
	$prod_price = function_to_get_product_price($product, $prod_id);

	if ($url_path == "on") {
		$prod_url = "http://" . $_SERVER['SERVER_NAME'] . "/" . function_to_get_product_url($product->getUrlPath());
	}
	else {
		$prod_url = function_to_get_product_url($product->getProductUrl());
	}
			
	// Add VAT to prices
	if ($add_vat == "on") {
		$prod_price = $prod_price * $vat_value;
	}
	
	// Get manufacturer
	
	$prod_brand=$product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product); // Brand writes if enabled
	

	$cat_ids = $product->getCategoryIds();
	$cat_level = 0;
	$cat_final_id = 0;
	$category_name = "";
	
	foreach ($cat_ids as $cat_id) {
		if (isset($CAT[$cat_id]['level'])) {
			if ($CAT[$cat_id]['level'] > $cat_level) {
				$cat_level = $CAT[$cat_id]['level'];
				$cat_final_id = $cat_id;
			}
		}
	}
	if ($cat_final_id > 0) {
		$category_name = $CAT[$cat_final_id]['name'];
	}
	else {
		$category_name = "Home";
	}
	
	$category_name = str_replace("Root Catalog", "Home", $category_name);

	// Clean product name (new lines)
	$prod_name = str_replace("\n", "", strip_tags($prod_name));
	$prod_name = str_replace("\r", "", strip_tags($prod_name));
	$prod_name = str_replace("\t", " ", strip_tags($prod_name));
	
	// Clean product description (Replace new line with <BR>). In order to make sure the code does not contains other HTML code it might be a good ideea to strip_tags()
	$prod_desc = replace_not_in_tags("\n", "<BR />", $prod_desc);
	$prod_desc = str_replace("\n", " ", $prod_desc);		
	$prod_desc = str_replace("\r", "", $prod_desc);
	$prod_desc = str_replace("\t", " ", $prod_desc);
	
		// Clean short product description (Replace new line with <BR>). In order to make sure the code does not contains other HTML code it might be a good ideea to strip_tags()
	$prod_short_desc = replace_not_in_tags("\n", "<BR />", $prof_short_desc);
	$prod_short_desc = str_replace("\n", " ", $prod_short_desc);		
	$prod_short_desc = str_replace("\r", "", $prod_short_desc);
	$prod_short_desc = str_replace("\t", " ", $prod_short_desc);
	
	// Clean product names and descriptions (separators)
	if ($datafeed_separator == "\t") {
		// Continue... tabs were already removed
	}
	elseif ($datafeed_separator == "|") {
		$prod_name = str_replace("|", " ", strip_tags($prod_name));
		$prod_desc = str_replace("|", " ", $prod_desc);
		$prod_short_desc = str_replace("|", " ", $prod_short_desc);
	}
	
	if (strpos($prod_image, "no_selection")) {
		$prod_image = "";
	}
	
	
	$RESULT['prod_name'] = $prod_name;
	$RESULT['brand'] = $prod_brand;
	$RESULT['prod_desc'] = $prod_desc;
	$RESULT['prod_short_desc'] = $prod_short_desc;
	$RESULT['prod_price'] = $prod_price;
   $RESULT['prod_sku'] = $prod_model;
	$RESULT['prod_id'] = $prod_id;
	$RESULT['prod_url'] = $prod_url;
	$RESULT['availability'] = $availability;
	$RESULT['category_name'] = $category_name;
	$RESULT['prod_image'] = $prod_image;
	$RESULT['spese_sped'] = "gratis";
	$RESULT['manufacturer'] = $prod_model;


	unset($product);
	
	return $RESULT;
}

// Function to return the Product URL based on your product ID
function function_to_get_product_url($product_url){
	
	$current_file_name = basename($_SERVER['REQUEST_URI']);
	$product_url = str_replace($current_file_name, "index.php", $product_url);
	$product_url = str_replace("datafeed_kelkoo_magento", "index", $product_url);
	
	// Eliminate id session 
	$pos_SID = strpos( $product_url, "?SID");
	if ($pos_SID) {
		$product_url = substr($product_url, 0, $pos_SID);
	}
	return $product_url;
}

function replace_not_in_tags($find_str, $replace_str, $string) {
	
	$find = array($find_str);
	$replace = array($replace_str);	
	preg_match_all('#[^>]+(?=<)|[^>]+$#', $string, $matches, PREG_SET_ORDER);	
	foreach ($matches as $val) {	
		if (trim($val[0]) != "") {
			$string = str_replace($val[0], str_replace($find, $replace, $val[0]), $string);
		}
	}	
	return $string;
}

function function_to_get_product_price($product, $productId) {
	
	global $Mage, $specialprice, $storeId, $websiteId, $custGroup;
	
	$_taxHelper  = Mage::helper('tax');
	
	if ( $product->getSpecialPrice() && ( ($specialprice == "on") || ( (date("Y-m-d G:i:s") > $product->getSpecialFromDate() || !$product->getSpecialFromDate()) &&  (date("Y-m-d G:i:s") < $product->getSpecialToDate() || !$product->getSpecialToDate()) ) ) ){
		$finalPrice = $product->getSpecialPrice();
	} 
	else {
		$finalPrice = $product->getPrice();
	}
	
	// Get ruleprice
	$rulePrice = Mage::getResourceModel('catalogrule/rule')->getRulePrice(Mage::app()->getLocale()->storeTimeStamp($storeId), $websiteId, $custGroup, $productId);
	
	if ($rulePrice !== null && $rulePrice !== false) {
		$finalPrice = min($finalPrice, $rulePrice);
	}
	
	$finalPrice = $_taxHelper->getPrice($product, $finalPrice, true);
	
	return $finalPrice;
}

// Get all categories whith breadcrumbs
function getCategories(){
	//$storeId = Mage::app()->getStore()->getId(); 
//->setStoreId($storeId)
	$collection = Mage::getModel('catalog/category')->getCollection()
		->addAttributeToSelect("name");
	$catIds = $collection->getAllIds();

	$cat = Mage::getModel('catalog/category');

	$max_level = 0;

	foreach ($catIds as $catId) {
		$cat_single = $cat->load($catId);
		$level = $cat_single->getLevel();
		if ($level > $max_level) {
			$max_level = $level;
		}

		$CAT_TMP[$level][$catId]['name'] = $cat_single->getName();
		$CAT_TMP[$level][$catId]['childrens'] = $cat_single->getChildren();
	}

	$CAT = array();
	
	for ($k = 0; $k <= $max_level; $k++) {
		if (is_array($CAT_TMP[$k])) {
			foreach ($CAT_TMP[$k] as $i=>$v) {
				if (isset($CAT[$i]['name']) && ($CAT[$i]['name'] != "")) {
					$CAT[$i]['name'] .= " / " . $v['name'];
					$CAT[$i]['level'] = $k;
				}
				else {
					$CAT[$i]['name'] = $v['name'];
					$CAT[$i]['level'] = $k;
				}

				if (($v['name'] != "") && ($v['childrens'] != "")) {
					if (strpos($v['childrens'], ",")) {
						$children_ids = explode(",", $v['childrens']);
						foreach ($children_ids as $children) {
							if (isset($CAT[$children]['name']) && ($CAT[$children]['name'] != "")) {
								$CAT[$children]['name'] = $CAT[$i]['name'];
							}
							else {
								$CAT[$children]['name'] = $CAT[$i]['name'];
							}
						}
					}
					else {
						if (isset($CAT[$v['childrens']]['name']) && ($CAT[$v['childrens']]['name'] != "")) {
							$CAT[$v['childrens']]['name'] = $CAT[$i]['name'];
						}
						else {
							$CAT[$v['childrens']]['name'] = $CAT[$i]['name'];
						}
					}
				}
			}
		}
	}
	unset($collection);
	unset($CAT_TMP);
	return $CAT;
}

function print_array($_ARR) {
	print "<pre>";
	print_r($_ARR);
	print "</pre>";
}


   function CleanHtml($string)
   {
      $string = strip_tags($string);                    //remove html tags
      $string = substr($string,0,255);               //cut the string up to a maximum of 255 characters
      $search = array ("'<script[^>]*?>.*?</script>'si",   // Removing the javascript
                   "'<[\/\!]*?[^<>]*?>'si",           // Removing HTML
                         "'([\r\n])[\s]+'",                  // Removing whitespace
                      "'([\r])+'",                         // Removing whitespace
                      "'([\n])+'",                      // Removing whitespace
                         "'&(quot|#34);'i",                   // Substitution of HTML entities
                         "'&(amp|#38);'i",
                         "'&(lt|#60);'i",
                       "'&(gt|#62);'i",
                     "'&(nbsp|#160);'i",
                     "'&(iexcl|#161);'i",
                      "'&(cent|#162);'i",
                      "'&(pound|#163);'i",
                      "'&(copy|#169);'i",
                      "'&#(\d+);'e");                      // Evaluate as PHP code

      $replace = array ("",
                 "",
                 "",
             "",
               "",
                 "",
                 "",
                 "",
                 "",
                 "",
                 chr(161),
                 chr(162),
                 chr(163),
                 chr(169),
                 "chr(\\1)");

      $descrizione1 = preg_replace($search, $replace, $string);
      return $descrizione1;
   }


function nor_to_html($txt) {
	return $txt;
	//return str_replace(array('æ', 'ø' ,'å', 'Æ', 'Ø' ,'Å' ,'Ã'), array('', '' ,'', '', '' ,'', ''), $txt);
	return strtr($txt,  
"ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",  
"AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn"  
); 
}

