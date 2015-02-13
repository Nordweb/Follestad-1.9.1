<?php
/**
 * The Server Mode settings for KlarnaPaymentModule
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   KlarnaPaymentModule_Magento
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * Klarna_KlarnaPaymentModule_Model_Source_Servermode
 *
 * @category  Payment
 * @package   KlarnaPaymentModule_Magento
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class Klarna_KlarnaPaymentModule_Model_Source_Servermode
{

    /**
     * Return the options shown in the backend for method status
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => 'Live',
                'value' => 'LIVE'
            ),
            array(
                'label' => 'Beta',
                'value' => 'BETA',
            )
        );
    }
}
