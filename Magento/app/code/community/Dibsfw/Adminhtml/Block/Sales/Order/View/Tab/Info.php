<?php

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
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order information tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Dibsfw_Adminhtml_Block_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info {

    public function getPaymentHtml() {
        $res = parent::getPaymentHtml();

        $sButtons = '';
        $oOrder = $this->getSource();

        $oPaymentObj = $oOrder->getPayment()->getMethodInstance();
        $sPaymentClass = get_class($oPaymentObj);
        if(strpos($sPaymentClass, "Dibsfw") !== FALSE) {
            $oPaymentObj->dibsflex_api_checkTable();
            $oOrderInfo = $oPaymentObj->dibsflex_helper_getOrderObj($oOrder);
            $sButtons = $oPaymentObj->dibsflex_api_cgibuttons($oOrderInfo->order_id);

            // Read info directly from the database   	
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sTablePrefix = Mage::getConfig()->getTablePrefix();
            $row = $read->fetchRow("select * from " . $sTablePrefix . "dibs_orderdata where orderid = " . $this->getOrder()->getIncrementId());

            if(count($row) > 0) {
                if($row['status'] == '1') {
                    // Payment has been made to this order
                    $res .= "<br /><br />" . "<table border='0' width='100%'>";
                    $res .= "<tr><td colspan='2'><b>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_7') . "</b></td></tr>";

                    if($row['transact'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_8') . "</td>";
                        $res .= "<td>" . $row['transact'] . "</td></tr>";
                    }

                    if($row['amount'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_9') . "</td>";
                        $res .= "<td>" . $this->getOrder()->getOrderCurrencyCode() . "&nbsp;" . number_format(((int) $row['amount']) / 100, 2, ',', ' ') . "</td></tr>";
                    }

                    if($row['currency'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_10') . "</td>";
                        $res .= "<td>" . $row['currency'] . "</td></tr>";
                    }

                    if($row['fee'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_11') . "</td>";
                        $res .= "<td>" . $this->getOrder()->getOrderCurrencyCode() . "&nbsp;" . number_format(((int) $row['fee']) / 100, 2, ',', ' ') . "</td></tr>";
                    }

                    if($row['paytype'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_12') . "</td>";
                        $res .= "<td>" . $oPaymentObj->cms_dibs_printLogo($row['paytype']) . "</td></tr>";
                    }

                    if($row['cardnomask'] != '0' && $row['cardprefix'] == '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_13') . "</td>";
                        $res .= "<td>............" . trim($row['cardnomask'], 'X') . "</td></tr>";
                    }

                    if($row['cardprefix'] != '0' && $row['cardnomask'] == '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_13') . "</td>";
                        $res .= "<td>" . $row['cardprefix'] . "..........</td></tr>";
                    }

                    if($row['cardprefix'] != '0' && $row['cardnomask'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_13') . "</td>";
                        if(substr($row['cardnomask'], 0, 6) === $row['cardprefix']) {
                            $res .= "<td>" . str_replace("X", ".", $row['cardnomask']) . "</td></tr>";
                        }
                        else
                            $res .= "<td>" . $row['cardprefix'] . '......' . trim($row['cardnomask'], 'X') . "</td></tr>";
                    }

                    if($row['cardexpdate'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_14') . "</td>";
                        $res .= "<td>" . substr($row['cardexpdate'], 2, 2) . " / " . substr($row['cardexpdate'], 0, 2) . "</td></tr>";
                    }

                    if($row['cardcountry'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_15') . "</td>";
                        $res .= "<td>" . $row['cardcountry'] . "</td></tr>";
                    }

                    if($row['acquirer'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_16') . "</td>";
                        $res .= "<td>" . $row['acquirer'] . "</td></tr>";
                    }

                    if($row['enrolled'] != '0') {
                        $res .= "<tr><td>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_17') . "</td>";
                        $res .= "<td>" . $row['enrolled'] . "</td></tr>";
                    }

                    $res .= '<tr><td colspan="2">' . $sButtons . '</td></tr>';

                    $res .= "</table><br />";
                    $res .= "<a href='https://payment.architrade.com/admin/' target='_blank'>" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_18') . "</a>";
                    $res .= "<br /><br />";
                }
                else {
                    $res .= "<br />" . Mage::helper('dibsfw')->__('DIBSFW_LABEL_19') . "<br />";
                }
            }
        }
        
        return $res;
    }
}