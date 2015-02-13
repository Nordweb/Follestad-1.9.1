<?php
/**
 * Countries source
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class to add a country source to the config page
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Source_Countries
{

    /**
     * Convert collection items to array for select options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('core')->__('Norway'),
                'value' => 'NO'
            ),
            array(
                'label' => Mage::helper('core')->__('Sweden'),
                'value' => 'SE',
            ),
            array(
                'label' => Mage::helper('core')->__('Finland'),
                'value' => 'FI',
            ),
            array(
                'label' => Mage::helper('core')->__('Denmark'),
                'value' => 'DK',
            ),
            array(
                'label' => Mage::helper('core')->__('Netherlands'),
                'value' => 'NL',
            ),
            array(
                'label' => Mage::helper('core')->__('Germany'),
                'value' => 'DE'
            )
        );
    }

}
