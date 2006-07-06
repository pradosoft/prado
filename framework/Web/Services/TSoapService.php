<?php
/**
 * TSoapService class file
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @link http://www.pradosoft.com
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.Services
 */

//Prado::using('System.Web.Services.TWebService');

require_once dirname(__FILE__).'/../../3rdParty/WsdlGen/WsdlGenerator.php';

/**
 * TSoapService class
 *
 * TSoapService provides
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Web.Services
 * @since 3.1
 */
class TSoapService extends TService {

  private $_class;

  private $_server; // reference to the SOAP server

  /**
   * Constructor.
   * Sets default service ID to 'soap'.
   */
  public function __construct() {
    $this->setID('soap');
  }

  /**
   * Initializes the service.
   * This method is required by IService interface and is invoked by application.
   * @param TXmlElement service configuration
   */
  public function init($config) {
    // nothing to do here
  }

  /**
   * Runs the service.
   * 
   * This will serve a WSDL-file of the Soap server if 'wsdl' is provided as a key in
   * the URL, else if will serve the Soap server.
   */
  public function run() {
    Prado::trace("Running SOAP service",'System.Web.Services.TSoapService');

    $this->setSoapServer($this->getRequest()->getServiceParameter());
    Prado::using($this->getSoapServer()); // Load class

    // TODO: Fix protocol and host
    $uri = 'http://'.$_SERVER['HTTP_HOST'].$this->getRequest()->getRequestUri();

    //print_r($this->getRequest());
    if($this->getRequest()->itemAt('wsdl') !== null) { // Show WSDL-file
      // TODO: Check WSDL cache
      // Solution: Use Application Mode 'Debug' = no caching, 'Performance' = use cachez
      $uri = str_replace('&wsdl', '', $uri); // throw away the 'wsdl' key (this is a bit dirty)
      $uri = str_replace('wsdl', '', $uri); // throw away the 'wsdl' key (this is a bit dirty)
      $wsdl = WsdlGenerator::generate($this->_class, $uri);
      $this->getResponse()->setContentType('text/xml');
      $this->getResponse()->write($wsdl);
    } else { // Provide service
      // TODO: use TSoapServer
      $this->_server = new SoapServer($uri.'&wsdl');
      $this->_server->setClass($this->getSoapServer());
      $this->_server->handle();
    }
  }

  /**
   * @return TSoapServer
   */
  public function getSoapServer() {
    return $this->_class;
  }

  /**
   * @param TSoapServer $class
   */
  public function setSoapServer($class) {
    // TODO: ensure $class instanceof TSoapServer
    $this->_class = $class;
  }

  public function getPersistence() {

  }
  
}

?>