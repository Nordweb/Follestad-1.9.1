<?php
//die('Pingbull_Cron_Helper_Data');
class Pingbull_Cron_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function notify($sendToName, $sendToEmail, $subject, $tpl, array $vars)
    {
        $emailTemplate = Mage::getModel('core/email_template')->loadDefault($tpl);
        //Create an array of variables to assign to template
        $emailTemplateVariables = $vars;
        if (strpos('{{var order_number}}', $subject) AND isset($vars['order_number'])) {
            $subject = str_replace('{{var order_number}}', $vars['order_number'], $subject);
        }
        $emailTemplate->getProcessedTemplate($emailTemplateVariables);
        $emailTemplate->setTemplateSubject($subject);
        $emailTemplate->setSenderName(Mage::getStoreConfig('design/head/default_title'));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_sales/email'));
        Mage::log('Pingbull Order Reminder is going to send email');
        Mage::log('Order ID for Notification - '.$vars['order_number']);
        Mage::log(var_export(!Mage::getStoreConfigFlag('system/smtp/disable'), TRUE) . '; From name: ' . var_export($emailTemplate->getSenderName(), TRUE) . '; From Email: ' . var_export($emailTemplate->getSenderEmail(), TRUE) . '; Subject: ' . var_export($emailTemplate->getTemplateSubject(), TRUE) . '; Receiver Email: ' . $sendToEmail);
        $emailTemplate->send($sendToEmail, $sendToName, $emailTemplateVariables);
        return TRUE;
    }

    public function sendOrderNotifications()
    {
        $module_config = Mage::getStoreConfig('pingbull/notifications');
        //var_dump($module_config);
        if ($module_config['enabled'] == 1) {
            $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', array('processing','pending'))->addFieldToFilter('is_order_delay_notice_was_sent', array('NULL','','0'))->addAttributeToSelect('*');
            Mage::log('[ORDERS]: '.count($orders));
            $date_now = new DateTime("now");
            $delayedOrdersList = '';
            $counter = 0;
            foreach ($orders as $order) {
                $order_updated_at = $order->getCreatedAt();
                $date_updated = date_create($order_updated_at);
                $interval = date_diff($date_updated, $date_now);
                echo '[' . $order_updated_at . ']<br>';
                if ($order->getData('is_order_delay_notice_was_sent') != 1) {

                    $order_id = $order->getIncrementId();
                    echo $interval->format('%a') . '<br>';
                    if ($interval->format('%a') >= $module_config['delay']) {
                        //   echo " delayed order!";
                        $delayedOrdersList .= $order_id . ' ';
                        $order_for_update = Mage::getModel('sales/order')
                            ->load($order->getId());
                        Mage::log('Updating order #' . $order->getId() . ', Email: ' . $order->getCustomerEmail());

                        $this->notify($order->getCustomerName(), $order->getCustomerEmail(), $module_config['subject'], 'pingbull_delayed_orders', array('order_number' => $order_id, 'customer_name' => $order->getCustomerName()));
                        $this->notify('Admin', $module_config['admin_email'], $module_config['admin_subject'], 'pingbull_delayed_orders_admin', array('order_number' => $order_id, 'customer_name' => $order->getCustomerName()));
                        $order_for_update->setData('is_order_delay_notice_was_sent', 1)->save();
                        Mage::log('Order notice sent');
                        $counter++;

                    }
                }
            }
            Mage::log('Pingbull Order Reminder Cron task was executed');
            echo '<br>Total notices sent: ' . $counter . '<hr>';
            return TRUE;
        }
    }
}
