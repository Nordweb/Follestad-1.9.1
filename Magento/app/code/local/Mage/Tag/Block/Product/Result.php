<?php
/**
 * Acumen for Magento
 * http://gravitydept.com/to/acumen-magento
 *
 * @author	   Brendan Falkowski
 * @package	   gravdept_acumen
 * @copyright  Copyright 2011 Gravity Department http://gravitydept.com
 * @license	   All rights reserved.
 * @version	   1.2.5
 */

/**
 * GravDept changes:
 * ---------------------------
 * function _prepareLayout() --- created Breadcrumbs for tagged products
 * function getHeaderText() --- modified return string to be more concise
 */


/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tag
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * List of tagged products
 *
 * @category   Mage
 * @package    Mage_Tag
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class Mage_Tag_Block_Product_Result extends Mage_Catalog_Block_Product_Abstract
{
    protected $_productCollection;


    public function getTag()
    {
        return Mage::registry('current_tag');
    }

    protected function _prepareLayout()
    {
        $title = $this->getHeaderText();
        $this->getLayout()->getBlock('head')->setTitle($title);
        $this->getLayout()->getBlock('root')->setHeaderTitle($title);
        
        // custom, replicated from /app/code/core/local/Mage/CatalogSearch/Block/Result.php
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
        	$breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                //'title' => $this->__('Go to Home Page'),   // original
                'title' => $this->__('Store Home'),   // custom
                'link'  => Mage::getBaseUrl()
            ))->addCrumb('search', array(
                'label' => $title,
                'title' => $title
            ));
        }
        // end custom
        
        return parent::_prepareLayout();
    }

    public function setListOrders() {
        $this->getChild('search_result_list')
            ->setAvailableOrders(array(
                'name' => Mage::helper('tag')->__('Name'),
                'price'=>Mage::helper('tag')->__('Price'))
            );
    }

    public function setListModes() {
        $this->getChild('search_result_list')
            ->setModes(array(
                'grid' => Mage::helper('tag')->__('Grid'),
                'list' => Mage::helper('tag')->__('List'))
            );
    }

    public function setListCollection() {
        $this->getChild('search_result_list')
           ->setCollection($this->_getProductCollection());
    }

    public function getProductListHtml()
    {
        return $this->getChildHtml('search_result_list');
    }

    protected function _getProductCollection()
    {
        if(is_null($this->_productCollection)) {
            $tagModel = Mage::getModel('tag/tag');
            $this->_productCollection = $tagModel->getEntityCollection()
                ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->addTagFilter($this->getTag()->getId())
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addMinimalPrice()
                ->addUrlRewrite()
                ->setActiveFilter();
            Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_productCollection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($this->_productCollection);
        }

        return $this->_productCollection;
    }

    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = $this->_getProductCollection()->getSize();
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }

    public function getHeaderText()
    {
        if( $this->getTag()->getName() ) {
            //return Mage::helper('tag')->__("Products tagged with '%s'", $this->htmlEscape($this->getTag()->getName()));  // original
            return Mage::helper('tag')->__("Tagged: %s", $this->htmlEscape($this->getTag()->getName()));  // custom
        } else {
            return false;
        }
    }

    public function getSubheaderText()
    {
        return false;
    }

    public function getNoResultText()
    {
        return Mage::helper('tag')->__('No matches found.');
    }
}
