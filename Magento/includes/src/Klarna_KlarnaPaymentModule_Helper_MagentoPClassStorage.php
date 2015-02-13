<?php
/**
 * File used to extend the Klarna MySQL storage for the Magento module
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

require_once dirname(__FILE__) . '/Klarna/pclasses/mysqlstorage.class.php';

/**
 * MagentoPClassStorage
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class MagentoPClassStorage extends MySQLStorage
{

    /**
     * Returns a flattened array of all pclasses
     *
     * @return array
     */
    public function getAllPClasses()
    {
        if (!is_array($this->pclasses)) {
            return array();
        }
        return $this->_flatten(array_values($this->pclasses));
    }

    /**
     * Flatten an array
     *
     * @param array $array array to flatten
     *
     * @return array
     */
    private function _flatten($array)
    {
        if (!is_array($array)) {
            // nothing to do if it's not an array
            return array($array);
        }
        $result = array();
        foreach ($array as $value) {
            // explode the sub-array, and add the parts
            $result = array_merge($result, $this->_flatten($value));
        }
        return $result;
    }
}
