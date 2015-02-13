<?php
/**
 * PClasses Block
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   Klarna_Module_Magento
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * Template handler for the PClasses block.
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Adminhtml_Pclasses
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Render a Magento Template
     *
     * @param Varien_Data_Form_Element_Abstract $element element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setTemplate('klarna/pclasses-buttons.phtml');
        $this->assign('field', "klarna_pclasses_buttons");
        return $this->toHtml();
    }

}
