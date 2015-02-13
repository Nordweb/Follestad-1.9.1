<?php
/**
 * Order model backwards compatability fix
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
 * Klarna_KlarnaPaymentModule_Model_Order_Payment
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Klarna_KlarnaPaymentModule_Model_Order_Payment
extends Mage_Sales_Model_Order_Payment
{

    /**
     * Backwards compatability fix for Kreditor
     *
     * @param string $key   The info key to use
     * @param index  $index The index to use
     *
     * @return mixed
     */
    public function getData($key = '', $index = null)
    {
        if ((strpos($this->_data["method"], "kreditor") === false)
            && (strpos($this->_data["method"], "klarna") === false)
        ) {
            return parent::getData($key, $index);
        }

        if (strpos($key, "klarna") !== false) {
            return parent::getData($key, $index);
        }

        if ($key == "additional_information") {
            $data = parent::getData($key, $index);
            if (!is_array($data)) {
                $data = unserialize($data);
            }

            $tmp_array = array();
            foreach ($data as $key => $value) {
                $tmp_array[str_replace("kreditor", "klarna", $key)] = $value;
            }

            return serialize($tmp_array);
        }

        return parent::getData($key, $index);
    }

    /**
     * Backwards compatability fix for Kreditor
     *
     * @param string $key The info key to use
     *
     * @return mixed
     */
    public function getAdditionalInformation($key = null)
    {
        if ((strpos($this->getMethod(), "kreditor") === false)
            && (strpos($this->getMethod(), "klarna") === false)
        ) {
            return parent::getAdditionalInformation($key);
        }

        $addinfo = parent::getAdditionalInformation($key);

        if ($addinfo == null || $addinfo == "") {
            $addinfo = parent::getAdditionalInformation(
                str_replace("klarna", "kreditor", $key)
            );
        }

        if (is_array($addinfo)) {
            $tmp_array = array();
            foreach ($addinfo as $key => $value) {
                $tmp_array[str_replace("kreditor", "klarna", $key)] = $value;
            }
            return $tmp_array;
        }

        return $addinfo;
    }

}
