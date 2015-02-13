<?php
/**
 * Magento Consignor Integration
 *
 * LICENSE AND USAGE INFORMATION
 * It is NOT allowed to modify, copy or re-sell this file or any
 * part of it. Please contact us by email at support@trollweb.no or
 * visit us at www.trollweb.no if you have any questions about this.
 * Trollweb is not responsible for any problems caused by this file.
 *
 * Visit us at http://www.trollweb.no today!
 *
 * @category   Trollweb
 * @package    Trollweb_Consignor
 * @copyright  Copyright (c) 2011 Trollweb (http://www.trollweb.no)
 * @license    Single-site License
 *
 */

class Trollweb_Consignor_Model_Soap extends Varien_Object
{
    /**
     * Soap server
     *
     * @var SoapServer
     */
    protected $_soap = null;

    public function setController($controller)
    {
         $this->setData('controller', $controller);
         return $this;
    }

    public function getController()
    {
        return $this->getData('controller');
    }

	/**
     * Run webservice
     *
     * @param Mage_Api_Controller_Action $controller
     * @return Mage_Api_Model_Server_Adapter_Soap
     */
    public function run()
    {
        $urlModel = Mage::getModel('core/url')
            ->setUseSession(false);
        if ($this->getController()->getRequest()->getParam('wsdl') !== null) {
            // Generating wsdl content from template
            $io   = new Varien_Io_File();
            $io->open(array('path'=>Mage::getModuleDir('etc', 'Trollweb_Consignor')));

            $wsdlContent = $io->read('wsdl.xml');
      			$tns_path = $urlModel->getBaseUrl() . 'consignor/api';
      			$wsdlContent = str_replace('{{tns.path}}', $tns_path, $wsdlContent);

            $this->getController()->getResponse()
                ->setHeader('Content-Type','text/xml')
                ->setBody($wsdlContent);
        } elseif ($this->_extensionLoaded()) {
            $this->_soap = new SoapServer($urlModel->getBaseUrl() . 'consignor/api?wsdl',array('encoding'=>'UTF-8'));
            use_soap_error_handler(false);
            $this->_soap->setClass('Trollweb_Consignor_Model_Server');
            $this->getController()->getResponse()
                ->setHeader('Content-Type', 'text/xml; charset=UTF-8')
                ->setBody($this->_soap->handle());

        } else {
            $this->fault('0', 'Unable to load Soap extension on the server');
        }
        return $this;
    }

    /**
     * Dispatch webservice fault
     *
     * @param int $code
     * @param string $message
     */
    public function fault($code, $message)
    {
        if ($this->_extensionLoaded()) {
            throw new SoapFault($code, $message);
        } else {
            die('<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                <SOAP-ENV:Body>
                <SOAP-ENV:Fault>
                <faultcode>' . $code . '</faultcode>
                <faultstring>' . $message . '</faultstring>
                </SOAP-ENV:Fault>
                </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>');
        }

    }

    /**
     *  Check whether Soap extension is loaded
     *
     *  @return	  boolean
     */
    protected function _extensionLoaded()
    {
        return class_exists('SoapServer', false);
    }

} // Class Trollweb_Consignor_Model_Soap End
