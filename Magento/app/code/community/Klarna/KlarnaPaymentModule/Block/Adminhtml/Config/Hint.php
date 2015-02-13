<?php
/**
 * Configuration Block
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
 * Hint for where configuration may be found.
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Block_Adminhtml_Config_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{

    protected $_template = 'klarna/config/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $string = Mage::helper('klarnaPaymentModule/lang')
            ->fetch('magento_config_hint', 'en');

        $url = $this->escapeHtml(
            $this->getUrl(
                'adminhtml/system_config/edit', array('section' => 'klarna')
            )
        );
        $string = KiTT_String::injectLink($string, $url);

        $this->assign('string', $string);
        return $this->toHtml();
    }
}
