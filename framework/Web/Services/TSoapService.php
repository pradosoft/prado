<?php
/**
 * TSoapService and TSoapServer class file
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;

/**
 * TSoapService class
 *
 * TSoapService processes SOAP requests for a PRADO application.
 * TSoapService requires PHP SOAP extension to be loaded.
 *
 * TSoapService manages a set of SOAP providers. Each SOAP provider
 * is a class that implements a set of SOAP methods which are exposed
 * to SOAP clients for remote invocation. TSoapService generates WSDL
 * automatically for the SOAP providers by default.
 *
 * To use TSoapService, configure it in the application specification like following:
 * <code>
 *   <services>
 *     <service id="soap" class="Prado\Web\Services\TSoapService">
 *       <soap id="stockquote" provider="MyStockQuote" />
 *     </service>
 *   </services>
 * </code>
 * PHP configuration style:
 * <code>
 *  'services' => array(
 *    'soap' => array(
 *     'class' => 'Prado\Web\Services\TSoapService'
 *     'properties' => array(
 *       'provider' => 'MyStockQuote'
 *	   )
 *    )
 *  )
 * </code>
 *
 * The WSDL for the provider class "MyStockQuote" is generated based on special
 * comment tags in the class. In particular, if a class method's comment
 * contains the keyword "@soapmethod", it is considered to be a SOAP method
 * and will be exposed to SOAP clients. For example,
 * <code>
 *   class MyStockQuote {
 *      / **
 *       * @param string $symbol the stock symbol
 *       * @return float the stock price
 *       * @soapmethod
 *       * /
 *      public function getQuote($symbol) {...}
 *   }
 * </code>
 *
 * With the above SOAP provider, a typical SOAP client may call the method "getQuote"
 * remotely like the following:
 * <code>
 *   $client=new SoapClient("http://hostname/path/to/index.php?soap=stockquote.wsdl");
 *   echo $client->getQuote("ibm");
 * </code>
 *
 * Each <soap> element in the application specification actually configures
 * the properties of a SOAP server which defaults to {@link TSoapServer}.
 * Therefore, any writable property of {@link TSoapServer} may appear as an attribute
 * in the <soap> element. For example, the "provider" attribute refers to
 * the {@link TSoapServer::setProvider Provider} property of {@link TSoapServer}.
 * The following configuration specifies that the SOAP server is persistent within
 * the user session (that means a MyStockQuote object will be stored in session)
 * <code>
 *   <services>
 *     <service id="soap" class="Prado\Web\Services\TSoapService">
 *       <soap id="stockquote" provider="MyStockQuote" SessionPersistent="true" />
 *     </service>
 *   </services>
 * </code>
 *
 * You may also use your own SOAP server class by specifying the "class" attribute of <soap>.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Web\Services
 * @since 3.1
 */
class TSoapService extends \Prado\TService
{
	const DEFAULT_SOAP_SERVER = 'Prado\Web\Services\TSoapServer';
	private $_servers = [];
	private $_configFile;
	private $_wsdlRequest = false;
	private $_serverID;

	/**
	 * Constructor.
	 * Sets default service ID to 'soap'.
	 */
	public function __construct()
	{
		$this->setID('soap');
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if {@link getConfigFile ConfigFile} is invalid.
	 */
	public function init($config)
	{
		if ($this->_configFile !== null) {
			if (is_file($this->_configFile)) {
				$dom = new TXmlDocument;
				$dom->loadFromFile($this->_configFile);
				$this->loadConfig($dom);
			} else {
				throw new TConfigurationException('soapservice_configfile_invalid', $this->_configFile);
			}
		}
		$this->loadConfig($config);

		$this->resolveRequest();
	}

	/**
	 * Resolves the request parameter.
	 * It identifies the server ID and whether the request is for WSDL.
	 * @throws THttpException if the server ID cannot be found
	 * @see getServerID
	 * @see getIsWsdlRequest
	 */
	protected function resolveRequest()
	{
		$serverID = $this->getRequest()->getServiceParameter();
		if (($pos = strrpos($serverID, '.wsdl')) === strlen($serverID) - 5) {
			$serverID = substr($serverID, 0, $pos);
			$this->_wsdlRequest = true;
		} else {
			$this->_wsdlRequest = false;
		}
		$this->_serverID = $serverID;
		if (!isset($this->_servers[$serverID])) {
			throw new THttpException(400, 'soapservice_request_invalid', $serverID);
		}
	}

	/**
	 * Loads configuration from an XML element
	 * @param mixed $config configuration node
	 * @throws TConfigurationException if soap server id is not specified or duplicated
	 */
	private function loadConfig($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			if (is_array($config)) {
				foreach ($config['soap'] as $id => $server) {
					$properties = $server['properties'] ?? [];
					if (isset($this->_servers[$id])) {
						throw new TConfigurationException('soapservice_serverid_duplicated', $id);
					}
					$this->_servers[$id] = $properties;
				}
			}
		} else {
			foreach ($config->getElementsByTagName('soap') as $serverXML) {
				$properties = $serverXML->getAttributes();
				if (($id = $properties->remove('id')) === null) {
					throw new TConfigurationException('soapservice_serverid_required');
				}
				if (isset($this->_servers[$id])) {
					throw new TConfigurationException('soapservice_serverid_duplicated', $id);
				}
				$this->_servers[$id] = $properties;
			}
		}
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string $value external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TInvalidDataValueException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if (($this->_configFile = Prado::getPathOfNamespace($value, Prado::getApplication()->getConfigurationFileExt())) === null) {
			throw new TConfigurationException('soapservice_configfile_invalid', $value);
		}
	}

	/**
	 * Constructs a URL with specified page path and GET parameters.
	 * @param string $serverID soap server ID
	 * @param array $getParams list of GET parameters, null if no GET parameters required
	 * @param bool $encodeAmpersand whether to encode the ampersand in URL, defaults to true.
	 * @param bool $encodeGetItems whether to encode the GET parameters (their names and values), defaults to true.
	 * @return string URL for the page and GET parameters
	 */
	public function constructUrl($serverID, $getParams = null, $encodeAmpersand = true, $encodeGetItems = true)
	{
		return $this->getRequest()->constructUrl($this->getID(), $serverID, $getParams, $encodeAmpersand, $encodeGetItems);
	}

	/**
	 * @return bool whether this is a request for WSDL
	 */
	public function getIsWsdlRequest()
	{
		return $this->_wsdlRequest;
	}

	/**
	 * @return string the SOAP server ID
	 */
	public function getServerID()
	{
		return $this->_serverID;
	}

	/**
	 * Creates the requested SOAP server.
	 * The SOAP server is initialized with the property values specified
	 * in the configuration.
	 * @return TSoapServer the SOAP server instance
	 */
	protected function createServer()
	{
		$properties = $this->_servers[$this->_serverID];
		$serverClass = null;
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP && isset($config['class'])) {
			$serverClass = $config['class'];
		} elseif ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_XML) {
			$serverClass = $properties->remove('class');
		}
		if ($serverClass === null) {
			$serverClass = self::DEFAULT_SOAP_SERVER;
		}
		Prado::using($serverClass);
		$className = ($pos = strrpos($serverClass, '.')) !== false ? substr($serverClass, $pos + 1) : $serverClass;
		if ($className !== self::DEFAULT_SOAP_SERVER && !is_subclass_of($className, self::DEFAULT_SOAP_SERVER)) {
			throw new TConfigurationException('soapservice_server_invalid', $serverClass);
		}
		$server = new $className;
		$server->setID($this->_serverID);
		foreach ($properties as $name => $value) {
			$server->setSubproperty($name, $value);
		}
		return $server;
	}

	/**
	 * Runs the service.
	 * If the service parameter ends with '.wsdl', it will serve a WSDL file for
	 * the specified soap server.
	 * Otherwise, it will handle the soap request using the specified server.
	 */
	public function run()
	{
		Prado::trace("Running SOAP service", 'Prado\Web\Services\TSoapService');
		$server = $this->createServer();
		$this->getResponse()->setContentType('text/xml');
		$this->getResponse()->setCharset($server->getEncoding());
		if ($this->getIsWsdlRequest()) {
			// server WSDL file
			Prado::trace("Generating WSDL", 'Prado\Web\Services\TSoapService');
			$this->getResponse()->clear();
			$this->getResponse()->write($server->getWsdl());
		} else {
			// provide SOAP service
			Prado::trace("Handling SOAP request", 'Prado\Web\Services\TSoapService');
			$server->run();
		}
	}
}
