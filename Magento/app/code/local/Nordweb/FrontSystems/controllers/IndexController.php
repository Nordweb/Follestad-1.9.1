<!--<a href="http://dev.follestad.no/frontSystems/Index/GetProducts">GetProducts</a><br />
<a href="http://dev.follestad.no/frontSystems/Index/GetStockCountByProductID?id=186104">GetStockCountByProductID</a>-->

<?php


class Nordweb_FrontSystems_IndexController extends Mage_Core_Controller_Front_Action {

    

    
    public function GetProductsAction()
    {
        
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
        Mage::log('Client authenticated');
        
        //GetProducts
        $retval = $clientAuthenticated->GetProducts(array('key'=>$fsKey));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $fsWebProducts = $retval->GetProductsResult;
        Mage::log('Successfully got all products');
       
        //Mage::log(get_class_methods($products->getFirstItem()));
        //Mage::log(get_class_methods($fsWebProducts->Product));
        //Mage::log(get_object_vars($fsWebProducts->Product[0]));
        //echo '' + $fsWebProducts->Product[0]->PRODUCTID; 
        
        
        Mage::helper('frontSystems')->prettyPrintArray( $fsWebProducts );
        //echo '<br/><br/>';
        
        //Test - Store in Magento
        Mage::log('Calling Magento to store');
        Mage::helper('frontSystems')->StoreProduct($fsWebProducts);
        
     
    }
    
    
    public function AddNewSaleAction()
    {
    
        Mage::log('IndexController: Calling helpers "AddNewSale" to send in Sale');
        Mage::helper('frontSystems')->AddNewSale();
        
    }
    
    
    public function GetCustomersAction()
    {
        
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        
        Mage::log('Client authenticated');
        
        //GetCustomers
        $retval = $clientAuthenticated->GetCustomers(array('key'=>$fsKey));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $fsCustomers = $retval->GetCustomersResult;
        Mage::log('Successfully got all Customers');
        
        //Mage::log(get_class_methods($products->getFirstItem()));
        //Mage::log(get_class_methods($fsWebProducts->Product));
        //Mage::log(get_object_vars($fsWebProducts->Product[0]));
        //echo '' + $fsWebProducts->Product[0]->PRODUCTID; 
        
        
        Mage::helper('frontSystems')->prettyPrintArray( $fsCustomers );
        //echo '<br/><br/>';
        
        ////Test - Store in Magento
        //Mage::log('Calling Magento to store');
        //Mage::helper('frontSystems')->StoreProduct($fsWebProducts);
        
        
    }
    
    public function GetStockCountByProductIDAction()
    {
        
        //auth
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        

        //GetStockCountByProductID 
        $params = $this->getRequest()->getParams();
        $productID = $params['id'];
        
        echo '<br/>$productID:';
        print_r($productID);


        
        $retval = $clientAuthenticated->GetStockCountByProductID(array('key'=>$fsKey, 'productID'=>$productID));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $GetStockCountByProductIDResult = $retval->GetStockCountByProductIDResult;
        echo '<br/>$GetStockCountByProductIDResult:';
        print_r($GetStockCountByProductIDResult);
        
        Mage::helper('frontSystems')->prettyPrintArray( $GetStockCountByProductIDResult );
        echo '<br/><br/>';


    }
    
   
    
    public function indexAction() {
        
        //$sProduct = Mage::getModel('catalog/product');
        //Mage::log(get_class_methods($sProduct));
        //Mage::helper('fsGetProducts')->prettyPrintArray( $sProduct );
        
        $myModel = Mage::getModel('frontSystems/SomeModel');
        print_r($myModel);
        
        
        //Mage::helper('frontSystems')->prettyPrintArray( $sProduct->getAttributes() );
        //Mage::log(get_object_vars($sProduct->getData()));
        
        
		//echo 'Hello World!<br/>';
		//$params = $this->getRequest()->getParams();
        //$product = Mage::getModel('catalog/product')->load($params['id']);
        //echo 'Write out a product: Hello, '. $product->getName();
        //Mage::log($product);

        //print_r($product->getData()); // outputs product data
        //Mage::log(get_class_methods($product)); // writes available product methods to log


        //$products = Mage::getModel('catalog/product')->getCollection();
        //var_dump($products->getFirstItem()->getData());
        //echo '<br/><br/>';
        //print_r($products->getFirstItem()->getData());
        //Mage::log($products->getFirstItem()->getData());
        //Mage::log(get_class_methods($products->getFirstItem())); // writes available product methods to log
        //echo '<br/><br/>Is in stock?';
        //print_r($products->getFirstItem()->isInStock());

        //Mage::log('Your Log Message', Zend_Log::INFO, 'your_log_file.log');

        //$products = Mage::getModel('catalog/product')->getCollection();
        ////$product = Mage::getModel('catalog/product')->load($params['id']);

        //$qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($products)->getQty();
        //echo '<br/><br/>Stock:';
        //print_r($qtyStock);

        //$params = $this->getRequest()->getParams();
        //$product_id = $params['id'];
        //$model = Mage::getModel('catalog/product');
        //$_product = $model->load($product_id);
        //$stocklevel = (int)Mage::getModel('cataloginventory/stock_item')
        //                ->loadByProduct($_product)->getQty();

        //echo '<br/><br/>Stock';
        //print_r($_product->getData());  echo ': ';
        //print_r($stocklevel);


        //$product = Mage::getModel('catalog/product')->load($params['id']);

        //// get stock data
        //$stockData = $product->getStockItem();
        //printf(PHP_EOL.'Stock: qty=%d, instock=%s, man_stock=%s, use_cfg_man_stock=%s'.PHP_EOL,
        //    $stockData->getData('qty'),
        //    $stockData->getData('is_in_stock'),
        //    $stockData->getData('manage_stock'),
        //    $stockData->getData('use_config_manage_stock')
        //);
        // prints out qty=0, instock=, man_stock=, use_cfg_man_stock=


        // $stockQty = 1
        //$stockItem = Mage::getModel('cataloginventory/stock_item');
        //$stockItem->assignProduct($product);
        //$stockItem->setData('is_in_stock', 1);
        //$stockItem->setData('stock_id', 1);
        //$stockItem->setData('store_id', 1);
        //$stockItem->setData('manage_stock', 0);
        //$stockItem->setData('use_config_manage_stock', 0);
        //$stockItem->setData('min_sale_qty', 0);
        //$stockItem->setData('use_config_min_sale_qty', 0);
        //$stockItem->setData('max_sale_qty', 1000);
        //$stockItem->setData('use_config_max_sale_qty', 0);
        //$stockItem->setData('qty', $stockQty);
        //$stockItem->save();

        //$product->save();
        //$product->load();
        //$stockData = $product->getStockItem();
        //printf('New Stock: qty=%d, instock=%s, man_stock=%s, use_cfg_man_stock=%s'.PHP_EOL,
        //    $stockData->getData('qty'),
        //    $stockData->getData('is_in_stock'),
        //    $stockData->getData('manage_stock'),
        //    $stockData->getData('use_config_manage_stock')
        //);
        // prints out qty=1, instock=1, man_stock=0, use_cfg_man_stock=0

	}


}

abstract class Enum
{

    const NONE = null;

    final private function __construct()
    {
        ; // non-constructable
    }

    final private function __clone()
    {
        ; // non-cloneable
    }

    final public static function toArray()
    {
        return (new \ReflectionClass(get_called_class()))->getConstants();
    }

    final public static function isValid($value)
    {
        return in_array($value, static::toArray());
    }

}

final class CardTypes extends Enum
{


    const VISA                         = 3;
     const ECMC                         = 4;
      const Amex                         = 5;
       const Diners                         = 6;
        const BankAxxess                         = 10000;
   
    
}




?>