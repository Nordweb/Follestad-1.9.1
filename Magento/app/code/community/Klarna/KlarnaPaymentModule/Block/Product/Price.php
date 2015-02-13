<?php

/**
 * Class to show our product page box.
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

require_once Mage::getRoot() .
    '/code/community/Klarna/KlarnaPaymentModule/Helper/Api.php';

/**
 * Product Price extension
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Product_Price
extends Mage_Bundle_Block_Catalog_Product_Price
{

    const NONKLARNA = -1;
    var $block = null;

    /**
     * Override toHtml and append our own Html
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();

        $storeId = Mage::app()->getStore()->getId();

        $isActive = Mage::getStoreConfig(
            'klarna/payments/partpay_enabled', $storeId
        );

        if (!$isActive) {
            return $html;
        }

        if (!$this->getTemplate() == "catalog/product/price.phtml"
            && !$this->getTemplate() == "bundle/catalog/product/price.phtml"
        ) {
            return $html;
        }

        $isProductPage = strstr(
            strtolower(
                Mage::app()->getFrontController()->getRequest()->getRequestUri()
            ),
            "/product/view/"
        );

        if (!$isProductPage) {
            return $html;
        }

        $helper = Mage::helper('klarnaPaymentModule/api');
        $data = Mage::helper("klarnaPaymentModule");
        $country = $data->guessCountryCode($data->guessCustomerAddress());

        if ($country === self::NONKLARNA) {
            return $html;
        }

        $klarna = new Klarna();
        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        if (!$klarna->checkCountryCurrency($country, $currency)) {
            return $html;
        }

        $price = $this->_getPrice();

        // If we have a product that cost over 250 euro we
        // do not display the part payment price for the Netherlands.
        if ($price >= 250 && $country == KlarnaCountry::NL) {
            return $html;
        }

        $routeName = Mage::app()
            ->getFrontController()
            ->getRequest()
            ->getRouteName();

        // If we are at product page and already have created the price
        // block we do not want to do that again
        if ($this->getLayout()->getBlock('klarna_price_block')
            && $routeName == "catalog"
        ) {
            return $html;
        }

        // If we are not in the catalog, return
        if (!$routeName == "catalog") {
            return $html;
        }

                // Only display this for primary products and not related, cross or
        // up-sell products the main products will not have a suffix
        $suffix = $this->getIdSuffix();

        if ($suffix != null) {
            return $html;
        }

        // Load the main product displayed on the page and see if it is a
        // collection.
        $tmpProduct = Mage::getModel(
            'catalog/product'
        )->load(Mage::app()->getRequest()->getParam('id'));

        // Don't show box for collections for dutch customers
        if ($tmpProduct->getGroupedLinkCollection()->count() > 0
            && $country == KlarnaCountry::NL
        ) {
            return $html;
        }

        try {
            $helper->loadConfig($country, $storeId, "klarna_partpayment");
        } catch (Exception $e) {
            return $html;
        }

        $helper->getKlarnaObject()->setCountry($country);
        $helper->configureKiTT();

        $pclassCollection = $helper->getPClassCollection(
            $price,
            KiTT::PART,
            KlarnaFlags::PRODUCT_PAGE
        );

        // It's a normal product, should be enough space for the part
        // payment box
        $html .= $this->getLayout()->createBlock(
            'core/template', 'klarna_price_block'
        )->setTemplate('klarna/product/price.phtml')->toHtml();

        $html .= KiTT::partPaymentBox(
            KiTT::locale($country),
            $pclassCollection
        )->show();

        return $html;
    }

    /**
     * Get the price of the product
     *
     * @return float
     */
    private function _getPrice()
    {
        $_taxHelper = $this->helper('tax');
        $curr = Mage::app()->getStore()->getCurrentCurrencyCode();
        $base_currency = Mage::app()->getStore()->getBaseCurrencyCode();
        $convert = ($base_currency != $curr);

        $rate = 1;
        if ($convert) {
            $cCurr = Mage::getModel('directory/currency');
            $cCurr->load($base_currency);
            $rate = $cCurr->getRate($curr);
        }

        if ($this->getProduct()->getSpecialPrice() > 0) {
            return $_taxHelper->getPrice(
                $this->getProduct(),
                $this->getProduct()->getSpecialPrice(),
                true
            ) * $rate;
        }
        return $_taxHelper->getPrice(
            $this->getProduct(),
            $this->getProduct()->getFinalPrice(),
            true
        ) * $rate;
    }

}
