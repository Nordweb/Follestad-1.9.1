<?php
/**
 * The Klarna AJAX provider.
 * This class provides data for AJAX calls
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * KiTT_Ajax
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_Ajax
{
    /**
     * @var Klarna
     */
    private $_api;

    /**
     * @var KiTT_Config
     */
    private $_config;

    private $_addresses;

    private $_productprice;

    /**
     * @var KlarnaFormatter
     */
    public $formatter;

    /**
     * @var array setup values for templates
     */
    public $setupValues = array();

    /**
     * Create a KlarnaAjax
     *
     * @param KiTT_Config       $config       Object holding EID, paths and etc.
     * @param KiTT_Addresses    $addresses    Object used for fetching addresses
     * @param KiTT_Productprice $productprice Object used for updating the ppbox
     */
    public function __construct ($config, $addresses, $productprice)
    {
        if (! $config instanceof KiTT_Config) {
            throw new InvalidArgumentException(
                '$config must be an instance of KiTT_Config'
            );
        }
        $this->_config = $config;

        $this->_addresses = $addresses;
        $this->_productprice = $productprice;
    }

    /**
     * Serialise list of addresses with unique address key spliced in as JSON
     *
     * @param array $addresses array of KlarnaAddress
     *
     * @return string a json string
     */
    private function _getAddressesJson($addresses)
    {
        $output = array();
        foreach ($addresses as $index => $addr) {
            $output[] = array(
                'company_name' => utf8_encode($addr->getCompanyName()),
                'first_name' => utf8_encode($addr->getFirstName()),
                'last_name' => utf8_encode($addr->getLastName()),
                'street' => utf8_encode($addr->getStreet()),
                'zip' => utf8_encode($addr->getZipCode()),
                'city' => utf8_encode($addr->getCity()),
                'country_code' => $addr->getCountryCode(),
                'key' => KiTT_Addresses::getAddressKey($addr)
            );
        }
        return json_encode($output);
    }

    /**
     * Make a get_addresses call to KRED for the pno in GET/POST
     *
     * @return array containing content and content-type
     */
    public function getAddress ()
    {
        $addrs = $this->_addresses->getAddresses(
            KiTT_HTTPContext::toString('pno')
        );

        return array(
            'type' => 'application/json',
            'value' => $this->_getAddressesJson($addrs)
        );
    }

    /**
     * DON̈́'T FORGET TO FIX
     * Render a replacement part payment box for new sum
     *
     * @return string replacement html
     */
    // public function updateProductPrice()
    // {
    //     return $this->_productprice->show();
    // }
}
