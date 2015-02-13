<?php

class Mage_Catalog_Block_Product_Mostviewed extends Mage_Catalog_Block_Product_Abstract
{
    public function __construct ()
    {
        parent::__construct ();
        $storeId = Mage::app ()->getStore ()->getId ();
        $products = Mage::getResourceModel ('reports/product_collection')
            ->addOrderedQty ()
            ->addAttributeToSelect ('*')
            ->addAttributeToSelect (array('name', 'price', 'small_image'))
            // ->joinField('store_id', 'catalog_category_product_index', 'store_id', 'product_id=entity_id', '{{table}}.store_id = '.$storeId, 'left')
            //   ->joinField('store_id', 'follestad_catalog_category_product_index', 'store_id', 'product_id=entity_id', '{{table}}.store_id = '.$storeId, 'left')
            ->addStoreFilter ($storeId)
            ->setStoreId ($storeId)
            ->addStoreFilter ($storeId)
            ->addViewsCount ();
        Mage::getSingleton ('catalog/product_status')->addVisibleFilterToCollection ($products);
        Mage::getSingleton ('catalog/product_visibility')->addVisibleInCatalogFilterToCollection ($products);
        $products->setPageSize (8)->setCurPage (1);
        $this->setProductCollection ($products);
        /*
         SELECT COUNT(report_table_views.event_id) AS `views`, `e`.*, `cat_index`.`position` AS `cat_index_position` FROM `follestad_report_event` AS `report_table_views`
 INNER JOIN `follestad_catalog_product_entity` AS `e` ON e.entity_id = report_table_views.object_id AND e.entity_type_id = 4
 INNER JOIN `follestad_catalog_category_product_index` AS `cat_index` ON cat_index.product_id=e.entity_id AND cat_index.store_id='1' AND cat_index.visibility IN(2, 4) AND cat_index.category_id='2' WHERE (report_table_views.event_type_id = 1) GROUP BY `e`.`entity_id` HAVING (COUNT(report_table_views.event_id) > 0) ORDER BY `views` DESC
         */
    }
}