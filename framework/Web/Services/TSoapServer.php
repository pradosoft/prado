<?php

/**
 * TSoapService and TSoapServer class file
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TConfigurationException;
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
 * {@see setWsdlStyle WsdlStyle} selects the style of a generated WSDL. The
 * default, 'rpc', carries SOAP encoding, as WSDL 1.1 and SOAP 1.1 define it, and
 * is what every earlier release produced. 'document' follows WS-I Basic Profile
 * 1.1, which prohibits SOAP encoding, and is what an interoperable client
 * expects. 'document' needs pradosoft/prado-wsdlgenerator 1.2 or later, and is
 * refused where the installed generator predates it.
 *
 * XML configuration style:
 * ```xml
 *   <services>
 *     <service id="soap" class="Prado\Web\Services\TSoapService">
 *       <soap id="stockquote" provider="MyStockQuote" WsdlStyle="document" />
 *     </service>
 *   </services>
 * ```
 * PHP configuration style:
 * ```php
 *   'services' => [
 *     'soap' => [
 *       'class' => 'Prado\Web\Services\TSoapService',
 *       'soap' => [
 *         'stockquote' => [
 *           'properties' => [
 *             'provider' => 'MyStockQuote',
 *             'wsdlstyle' => 'document',
 *           ],
 *         ],
 *       ],
 *     ],
 *   ]
 * ```
 * The style has no effect when {@see setWsdlUri WsdlUri} names a WSDL to serve.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1
 * @method TSoapService getService()
 */
class TSoapServer extends \Prado\TApplicationComponent
{
	public const WSDL_CACHE_PREFIX = 'wsdl.';

	/**
	 * Remote procedure calls with SOAP encoding, as WSDL 1.1 and SOAP 1.1 define
	 * it. Every generator this package has depended on produces it.
	 * @since 4.4.0
	 */
	public const WSDL_STYLE_RPC = 'rpc';

	/**
	 * Document style with literal encoding, following WS-I Basic Profile 1.1.
	 * Needs pradosoft/prado-wsdlgenerator 1.2 or later.
	 * @since 4.4.0
	 */
	public const WSDL_STYLE_DOCUMENT = 'document';

	private $_id;
	private $_provider;

	private $_version = '';
	private $_actor = '';
	private $_encoding = '';
	private $_uri = '';
	private $_classMap;
	private $_persistent = false;
	private $_wsdlUri = '';
	private $_wsdlStyle = self::WSDL_STYLE_RPC;

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
		if ($this->_id !== null && (strrpos($this->_id, '.wsdl') === strlen($this->_id) - 5)) {
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
			$providerClass = Prado::usingClass($provider);
			if (!is_string($providerClass)) {
				throw new TConfigurationException('soapserver_provider_invalid', $provider);
			}
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
		Prado::trace('SOAP-Fault ' . $code . ' ' . $title . ' : ' . $details, TSoapService::class);
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
	 * @return \SoapServer
	 */
	protected function createServer()
	{
		if ($this->_server === null) {
			if ($this->getApplication()->getMode() === TApplicationMode::Debug) {
				static::setIniWsdlCache(false);
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
	 * If {@see getWsdlUri WsdlUri} is set, its content will be returned.
	 * If not, the {@see setProvider Provider} class will be investigated
	 * and the WSDL will be automatically genearted.
	 * @return string the WSDL content of the SOAP server
	 */
	public function getWsdl()
	{
		if ($this->_wsdlUri === '') {
			$provider = $this->getProvider();
			$providerClass = Prado::usingClass($provider);
			if (!is_string($providerClass)) {
				throw new TConfigurationException('soapserver_provider_invalid', $provider);
			}
			if ($this->getApplication()->getMode() === TApplicationMode::Performance && ($cache = $this->getApplication()->getCache()) !== null) {
				// The style is part of the key, so a document is never served for
				// a provider whose rpc document was cached, or the other way round.
				$key = self::WSDL_CACHE_PREFIX . $this->getWsdlStyle() . '.' . $providerClass;
				$wsdl = $cache->get($key);
				if (is_string($wsdl)) {
					return $wsdl;
				}
				$wsdl = $this->generateWsdl($providerClass);
				$cache->set($key, $wsdl);
				return $wsdl;
			} else {
				return $this->generateWsdl($providerClass);
			}
		} else {
			return file_get_contents($this->_wsdlUri);
		}
	}

	/**
	 * Generates the WSDL of a provider in the style {@see getWsdlStyle WsdlStyle}
	 * carries. The style is an argument only where it is asked for, so the default
	 * reaches the generator the way it always did and a generator that predates
	 * {@see WSDL_STYLE_DOCUMENT} still serves it. Any other style is refused rather
	 * than silently ignored, because a generator that does not accept it discards
	 * the argument without complaint.
	 * @param string $providerClass the resolved provider class
	 * @throws TConfigurationException if the generator cannot produce the style
	 * @return string the WSDL of the provider
	 * @since 4.4.0
	 */
	protected function generateWsdl($providerClass)
	{
		$arguments = [$providerClass, $this->getUri(), $this->getEncoding()];

		$style = $this->getWsdlStyle();
		if ($style !== self::WSDL_STYLE_RPC) {
			if (!$this->getGeneratorHasStyle()) {
				throw new TConfigurationException('soapserver_wsdlstyle_unsupported', $style);
			}
			$arguments[] = $style;
		}

		return WsdlGenerator::generate(...$arguments);
	}

	/**
	 * Tells whether the installed generator takes a style. An extra argument to a
	 * function that does not declare it is discarded by PHP, so the style has to
	 * be looked for rather than assumed.
	 * @return bool whether WsdlGenerator::generate() accepts a style
	 * @since 4.4.0
	 */
	protected function getGeneratorHasStyle()
	{
		static $hasStyle = null;
		if ($hasStyle === null) {
			$hasStyle = (new \ReflectionMethod(WsdlGenerator::class, 'generate'))->getNumberOfParameters() >= 4;
		}
		return $hasStyle;
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
	 * Returns the style of a generated WSDL. The style has no effect when
	 * {@see getWsdlUri WsdlUri} names a WSDL to serve.
	 * @return string the style of a generated WSDL, 'rpc' or 'document'
	 * @since 4.4.0
	 */
	public function getWsdlStyle()
	{
		return $this->_wsdlStyle;
	}

	/**
	 * Sets the style of a generated WSDL. 'rpc' carries SOAP encoding, as WSDL 1.1
	 * and SOAP 1.1 define it, and is the default. 'document' follows WS-I Basic
	 * Profile 1.1, which prohibits SOAP encoding.
	 * @param string $value the style of a generated WSDL, 'rpc' or 'document'
	 * @throws TInvalidDataValueException if neither 'rpc' nor 'document'
	 * @since 4.4.0
	 */
	public function setWsdlStyle($value)
	{
		if ($value !== self::WSDL_STYLE_RPC && $value !== self::WSDL_STYLE_DOCUMENT) {
			throw new TInvalidDataValueException('soapserver_wsdlstyle_invalid', $value);
		}

		$this->_wsdlStyle = $value;
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
	 * @param string $classes comma delimit list of class names
	 */
	public function setClassMaps($classes)
	{
		$this->_classMap = $classes;
	}

	/**
	 * Provides if the PHP SOAP WSDL cache is enabled. This accounts for
	 * if `soap.wsdl_cache_enabled` is unset or without a value (default: true), and when the value is not zero.
	 * This is provided so subclasses can override, specifically for testing without calling {@see \ini_get()}.
	 * @return bool Returns whether the cache is enabled.
	 * @since 4.3.3
	 */
	public static function getIniWsdlCache(): bool
	{
		// on by default, meaning false, '', or '1'
		$enabled = ini_get('soap.wsdl_cache_enabled');
		return $enabled === false || $enabled === '' || (int) $enabled !== 0;
	}

	/**
	 * This isolates setting the php.ini `soap.wsdl_cache_enabled`.
	 * This is provided so subclasses can override, specifically for testing without calling {@see \ini_set()}.
	 * without calling {@see \ini_set()}.
	 * @param ?bool $value Whether the cache is enabled. null for restore.
	 * @return bool|string True if restored, false if not set, string of the old value if successful.
	 * @since 4.3.3
	 */
	public static function setIniWsdlCache(?bool $value): string|bool
	{
		if ($value === null) {
			ini_restore('soap.wsdl_cache_enabled');
			return true;
		} else {
			return ini_set('soap.wsdl_cache_enabled', (int) $value);
		}
	}
}
