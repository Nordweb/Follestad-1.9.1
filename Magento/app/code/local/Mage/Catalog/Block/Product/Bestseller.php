<?php
class Mage_Catalog_Block_Product_Bestseller extends Mage_Catalog_Block_Product_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $storeId = Mage::app()->getStore()->getId();
        echo '<!-- '.$storeId.' -->';
        $products = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name', 'price', 'small_image'))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->setOrder('ordered_qty', 'desc'); // most best sellers on top
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);

        $products->setPageSize(8)->setCurPage(1);
        $this->setProductCollection($products);
       // echo '<!--'.$products->getSelect().'-->';
        /*
         *
         SELECT SUM(order_items.qty_ordered) AS `ordered_qty`, `order_items`.`name` AS `order_items_name`, `order_items`.`product_id` AS `entity_id`, `e`.`entity_type_id`, `e`.`attribute_set_id`, `e`.`type_id`, `e`.`sku`, `e`.`has_options`, `e`.`required_options`, `e`.`created_at`, `e`.`updated_at`, `cat_index`.`position` AS `cat_index_position` FROM `follestad_sales_flat_order_item` AS `order_items`
 INNER JOIN `follestad_sales_flat_order` AS `order` ON `order`.entity_id = order_items.order_id AND `order`.state <> 'canceled'
 LEFT JOIN `follestad_catalog_product_entity` AS `e` ON (e.type_id NOT IN ('grouped', 'configurable', 'bundle')) AND e.entity_id = order_items.product_id AND e.entity_type_id = 4
 INNER JOIN `follestad_catalog_category_product_index` AS `cat_index` ON cat_index.product_id=e.entity_id AND cat_index.store_id='1' AND cat_index.visibility IN(2, 4) AND cat_index.category_id='2' WHERE (parent_item_id IS NULL) GROUP BY `order_items`.`product_id` HAVING (SUM(order_items.qty_ordered) > 0) ORDER BY `ordered_qty` desc
         *
         */
    }
}