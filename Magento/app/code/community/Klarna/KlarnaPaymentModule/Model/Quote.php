<?php
/**
 * Invoice fee totals quote
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
 * Class to handle the invoice fee totals quote
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Quote extends Mage_Sales_Model_Quote
{
    /**
     * Get all quote totals
     *
     * We need to remove a klarna_tax line that is created from
     * one of our models. Otherwise there will be two tax lines in the
     * checkout
     *
     * @return array
     */
    public function getTotals()
    {
        $totals = parent::getTotals();
        unset($totals['klarna_tax']);
        $totalsIndex = array_keys($totals);
        if (array_search('klarna_fee', $totalsIndex) === false) {
            return $totals;
        }
        unset($totalsIndex[array_search('klarna_fee', $totalsIndex)]);
        $fee = $totals['klarna_fee'];
        unset($totals['klarna_fee']);

        $feeIndex = array_search('shipping', $totalsIndex);
        if ($feeIndex === false) {
            $feeIndex = array_search('subtotal', $totalsIndex)+1;
        }

        $sortedTotals = array();
        $size = count($totalsIndex);
        for ($i=0; $i<$size; $i++) {
            if ($i == $feeIndex) {
                $sortedTotals['klarna_fee'] = $fee;
            }
            $sortedTotals[array_shift($totalsIndex)] = array_shift($totals);
        }

        return $sortedTotals;
    }

}


