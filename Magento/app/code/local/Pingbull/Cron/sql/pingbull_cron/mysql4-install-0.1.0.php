<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'is_order_delay_notice_was_sent', 'BOOL');
$installer->endSetup();
