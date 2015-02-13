<?php
/**
 * File used to add a specific config form field
 *
 * PHP Version 5.2
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class used to create a config form field for pclass actions
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Adminhtml_Updates
extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Get the element html to show as a field
     *
     * @param Varien_Data_Form_Element_Abstract $element Config form element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setTemplate("klarna/update-button.phtml");
        $this->assign('field', 'klarna_updates');
        return $this->toHtml();
    }

}
