<?php

class Nordweb_GetAllFSProducts_Block_Adminhtml_Widget_Button extends Mage_Adminhtml_Block_Widget_Button
{
    ///**
    // * @var Mage_Catalog_Model_Product Product instance
    // */
    ////private $_product;
    //private $_allProducts;

    ///**
    // * Block construct, setting data for button, getting current product
    // */
    //protected function _construct()
    //{
    //    //$this->_product = Mage::registry('current_product');
    //    $_allProducts = Mage::getModel('catalog/product')->getCollection();
    //    parent::_construct();
    //    //$currentURL = urlencode(Mage::helper('core/url')->getCurrentUrl());
    //    $this->setData(array(
    //        $message = "Be aware: Importing products from FS will delete all existing simple products belonging configurable products.",
    //        'label'     => Mage::helper('catalog')->__('Get All FS Products'),
    //        'onclick'   => "confirmSetLocation('{$message}','{$this->getUrl('/index/getallproducts/')}');",
    //        //'onclick'   => "setLocation('{$this->getUrl('/index/getproductsfromfsbysku/sku/' . $this->_product->sku . '')}'); return false;",'
    //        //'onclick'   => Mage::helper('addfsproducts')->GetProductsFromFSBySKU($this->_product->sku), //Find SKU
    //        //'onclick'   => 'window.open(\''.Mage::getModel('core/url')->getUrl() . $this->_product->getUrlPath() .'\')',
    //        'disabled'  => !$this->_isVisible(),
    //        'title' => (!$this->_isVisible())?
    //            Mage::helper('catalog')->__('Product is not configurable and can not get products from FS'):
    //            Mage::helper('catalog')->__('Get All FS Products')
    //    ));
    //}

    ///**
    // * Checking product visibility
    // *
    // * @return bool
    // */
    //private function _isVisible()
    //{
    //    return true;//$this->_product->isVisibleInCatalog() && $this->_product->isVisibleInSiteVisibility();
    //}
}

?>

