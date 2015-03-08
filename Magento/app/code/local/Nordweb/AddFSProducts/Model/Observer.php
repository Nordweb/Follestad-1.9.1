<?php

class Nordweb_AddFSProducts_Model_Observer
{
    public function addFSProducts($observer)
    {
        
        $_block = $observer->getBlock();
        $_type = $_block->getType();
        if ($_type == 'adminhtml/catalog_product_edit') {
            
            $_block->setChild('add_fs_products_button',
                $_block->getLayout()->createBlock('nordweb_addfsproducts/adminhtml_widget_button')
            );
          

            $_deleteButton = $_block->getChild('delete_button');
            /* Prepend the new button to the 'Delete' button if exists */
            if (is_object($_deleteButton)) {
                $_deleteButton->setBeforeHtml($_block->getChild('add_fs_products_button')->toHtml());
            } else {
                /* Prepend the new button to the 'Reset' button if 'Delete' button does not exist */
                $_resetButton = $_block->getChild('product_view_button');
                if (is_object($_resetButton)) {
                    $_resetButton->setBeforeHtml($_block->getChild('add_fs_products_button')->toHtml());
                }
            }
        }
    }
}