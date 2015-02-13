<?php
/**
 * File used to extend the Klarna library for the Magento module
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

require_once dirname(__FILE__)
    . '/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once dirname(__FILE__) . '/Klarna/Klarna.php';

/**
 * KlarnaMage
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class KlarnaMage extends Klarna
{
    private $_activateReservation = false;

    /**
     * constructor
     *
     * Sets the client_vsn
     */
    public function __construct()
    {
        $this->VERSION = 'php:magento:4.1.2';
    }

    /**
     * Overriding this so we can set the IP to 0.0.0.0 in the paramlist
     * This has to be done when activating a reservation so it can fall back
     * and use the IP that was used to place the reservation in the first place
     *
     * @return string
     */
    public function getClientIP()
    {
        if ($this->_activateReservation) {
            return '0.0.0.0';
        } else {
            return parent::getClientIP();
        }
    }

    /**
     * Set the internal activateReservation variable to true
     *
     * @return void
     */
    public function enableActivateReservation()
    {
        $this->_activateReservation = true;
    }

}
