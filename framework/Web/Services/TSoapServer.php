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

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Wsdl\WsdlGenerator;

/**
 * TSoapServer class.
 *
 * TSoapServer is a wrapper of the PHP SoapServer class.
 * It associates a SOAP provider class to the SoapServer object.
 * It also manages the URI for the SOAP service and WSDL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\Services
 * @since 3.1
 */
class TSoapServer extends \Prado\TApplicationComponent
{
	const WSDL_CACHE_PREFIX = 'wsdl.';

	private $_id;
	private $_provider;

	private $_version = '';
	private $_actor = '';
	private $_encoding = '';
	private $_uri = '';
	private $_classMap;
	private $_persistent = false;
	private $_wsdlUri = '';

	private $_requestedMethod;

	private $_server;

	/**
	 * @return string the ID of the SOAP server
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $id the ID of the SOAP server
	 * @throws TInvalidDataValueException if the ID ends with '.wsdl'.
	 */
	public function setID($id)
	{
		if (strrpos($this->_id, '.wsdl') === strlen($this->_id) - 5) {
			throw new TInvalidDataValueException('soapserver_id_invalid', $id);
		}
		$this->_id = $id;
	}

	/**
	 * Handles the SOAP request.
	 */
	public function run()
	{
		if (($provider = $this->getProvider()) !== null) {
			Prado::using($provider);
			$providerClass = ($pos = strrpos($provider, '.')) !== false ? substr($provider, $pos + 1) : $provider;
			$this->guessMethodCallRequested($providerClass);
			$server = $this->createServer();
			$server->setClass($providerClass, $this);
			if ($this->_persistent) {
				$server->setPersistence(SOAP_PERSISTENCE_SESSION);
			}
		} else {
			$server = $this->createServer();
		}
		try {
			$server->handle();
		} catch (\Exception $e) {
			if ($this->getApplication()->getMode() === TApplicationMode::Debug) {
				$this->fault($e->getMessage(), $e->__toString());
			} else {
				$this->fault($e->getMessage());
			}
		}
	}

	/**
	 * Generate a SOAP fault message.
	 * @param string $title message title
	 * @param mixed $details message details
	 * @param string $code message code, defalt is 'SERVER'.
	 * @param string $actor actors
	 * @param string $name message name
	 */
	public function fault($title, $details = '', $code = 'SERVER', $actor = '', $name = '')
	{
		Prado::trace('SOAP-Fault ' . $code . ' ' . $title . ' : ' . $details, 'Prado\Web\Services\TSoapService');
		$this->_server->fault($code, $title, $actor, $details, $name);
	}

	/**
	 * Guess the SOAP method request from the actual SOAP message
	 *
	 * @param string $class current handler class.
	 */
	protected function guessMethodCallRequested($class)
	{
		$namespace = $class . 'wsdl';
		$message = file_get_contents("php://input");
		$matches = [];
		if (preg_match('/xmlns:([^=]+)="urn:' . $namespace . '"/', $message, $matches)) {
			if (preg_match('/<' . $matches[1] . ':([a-zA-Z_]+[a-zA-Z0-9_]+)/', $message, $method)) {
				$this->_requestedMethod = $method[1];
			}
		}
	}

	/**
	 * Soap method guessed from the SOAP message received.
	 * @return string soap method request, null if not found.
	 */
	public function getRequestedMethod()
	{
		return $this->_requestedMethod;
	}

	/**
	 * Creates the SoapServer instance.
	 * @return SoapServer
	 */
	protected function createServer()
	{
		if ($this->_server === null) {
			if ($this->getApplication()->getMode() === TApplicationMode::Debug) {
				ini_set("soap.wsdl_cache_enabled", 0);
			}
			$this->_server = new \SoapServer($this->getWsdlUri(), $this->getOptions());
		}
		return $this->_server;
	}

	/**
	 * @return array options for creating SoapServer instance
	 */
	protected function getOptions()
	{
		$options = [];
		if ($this->_version === '1.1') {
			$options['soap_version'] = SOAP_1_1;
		} elseif ($this->_version === '1.2') {
			$options['soap_version'] = SOAP_1_2;
		}
		if (!empty($this->_actor)) {
			$options['actor'] = $this->_actor;
		}
		if (!empty($this->_encoding)) {
			$options['encoding'] = $this->_encoding;
		}
		if (!empty($this->_uri)) {
			$options['uri'] = $this->_uri;
		}
		if (is_string($this->_classMap)) {
			foreach (preg_split('/\s*,\s*/', $this->_classMap) as $className) {
				$options['classmap'][$className] = $className;
			} //complex type uses the class name in the wsdl
		}
		return $options;
	}

	/**
	 * Returns the WSDL content of the SOAP server.
	 * If {@link getWsdlUri WsdlUri} is set, its content will be returned.
	 * If not, the {@link setProvider Provider} class will be investigated
	 * and the WSDL will be automatically genearted.
	 * @return string the WSDL content of the SOAP server
	 */
	public function getWsdl()
	{
		if ($this->_wsdlUri === '') {
			$provider = $this->getProvider();
			$providerClass = ($pos = strrpos($provider, '.')) !== false ? substr($provider, $pos + 1) : $provider;
			Prado::using($provider);
			if ($this->getApplication()->getMode() === TApplicationMode::Performance && ($cache = $this->getApplication()->getCache()) !== null) {
				$wsdl = $cache->get(self::WSDL_CACHE_PREFIX . $providerClass);
				if (is_string($wsdl)) {
					return $wsdl;
				}
				$wsdl = WsdlGenerator::generate($providerClass, $this->getUri(), $this->getEncoding());
				$cache->set(self::WSDL_CACHE_PREFIX . $providerClass, $wsdl);
				return $wsdl;
			} else {
				return WsdlGenerator::generate($providerClass, $this->getUri(), $this->getEncoding());
			}
		} else {
			return file_get_contents($this->_wsdlUri);
		}
	}

	/**
	 * @return string the URI for WSDL
	 */
	public function getWsdlUri()
	{
		if ($this->_wsdlUri === '') {
			return $this->getRequest()->getBaseUrl() . $this->getService()->constructUrl($this->getID() . '.wsdl', false);
		} else {
			return $this->_wsdlUri;
		}
	}

	/**
	 * @param string $value the URI for WSDL
	 */
	public function setWsdlUri($value)
	{
		$this->_wsdlUri = $value;
	}

	/**
	 * @return string the URI for the SOAP service
	 */
	public function getUri()
	{
		if ($this->_uri === '') {
			return $this->getRequest()->getBaseUrl() . $this->getService()->constructUrl($this->getID(), false);
		} else {
			return $this->_uri;
		}
	}

	/**
	 * @param string $uri the URI for the SOAP service
	 */
	public function setUri($uri)
	{
		$this->_uri = $uri;
	}

	/**
	 * @return string the SOAP provider class (in namespace format)
	 */
	public function getProvider()
	{
		return $this->_provider;
	}

	/**
	 * @param string $provider the SOAP provider class (in namespace format)
	 */
	public function setProvider($provider)
	{
		$this->_provider = $provider;
	}

	/**
	 * @return string SOAP version, defaults to empty (meaning not set).
	 */
	public function getVersion()
	{
		return $this->_version;
	}

	/**
	 * @param string $value SOAP version, either '1.1' or '1.2'
	 * @throws TInvalidDataValueException if neither '1.1' nor '1.2'
	 */
	public function setVersion($value)
	{
		if ($value === '1.1' || $value === '1.2' || $value === '') {
			$this->_version = $value;
		} else {
			throw new TInvalidDataValueException('soapserver_version_invalid', $value);
		}
	}

	/**
	 * @return string actor of the SOAP service
	 */
	public function getActor()
	{
		return $this->_actor;
	}

	/**
	 * @param string $value actor of the SOAP service
	 */
	public function setActor($value)
	{
		$this->_actor = $value;
	}

	/**
	 * @return string encoding of the SOAP service
	 */
	public function getEncoding()
	{
		return $this->_encoding;
	}

	/**
	 * @param string $value encoding of the SOAP service
	 */
	public function setEncoding($value)
	{
		$this->_encoding = $value;
	}

	/**
	 * @return bool whether the SOAP service is persistent within session. Defaults to false.
	 */
	public function getSessionPersistent()
	{
		return $this->_persistent;
	}

	/**
	 * @param bool $value whether the SOAP service is persistent within session.
	 */
	public function setSessionPersistent($value)
	{
		$this->_persistent = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string comma delimit list of complex type classes.
	 */
	public function getClassMaps()
	{
		return $this->_classMap;
	}

	/**
	 * @param mixed $classes
	 * @return string comma delimit list of class names
	 */
	public function setClassMaps($classes)
	{
		$this->_classMap = $classes;
	}
}
