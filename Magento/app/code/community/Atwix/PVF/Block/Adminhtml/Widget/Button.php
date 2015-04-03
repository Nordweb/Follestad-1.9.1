<?php

class Atwix_PVF_Block_Adminhtml_Widget_Button extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * @var Mage_Catalog_Model_Product Product instance
     */
    private $_product;

    /**
     * Block construct, setting data for button, getting current product
     */
    protected function _construct()
    {
        $this->_product = Mage::registry('current_product');
        parent::_construct();
        $this->setData(array(
            'label'     => Mage::helper('catalog')->__('View Product Page'),
            'onclick'   => 'window.open(\''.Mage::getModel('core/url')->getUrl() . $this->_product->getUrlPath() .'\')',
            'disabled'  => !$this->_isVisible(),
            'title' => (!$this->_isVisible())?
                Mage::helper('catalog')->__('Product is not visible on frontend'):
                Mage::helper('catalog')->__('View Product Page')
        ));
    }

    /**
     * Checking product visibility
     *
     * @return bool
     */
    private function _isVisible()
    {
         //return $this->_product->isVisibleInCatalog() && $this->_product->isVisibleInSiteVisibility();
        
        //Not enable button if has parent, then it's part of a parent-configurable and should not be viewed seperately
        $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($this->_product->getId());
        if(!$parentIds)
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($this->_product->getId());
        if(isset($parentIds[0])){
            //has parent, return false
            return false;
            
        }
        
        return true; //Default
    }
}