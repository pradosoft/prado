<?php

/**
 * THttpHeadersManager class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * THttpHeadersManager class
 *
 * THttpHeadersManager is a module that can be used to provide
 * additional custom headers to be sent alongside responses.
 *
 * By default, {@see \Prado\Web\THttpResponse} doesn't use any THttpHeadersManager.
 * If you want to use your customized headers manager, load your manager class
 * as an application module and set {@see \Prado\Web\THttpResponse::setHeadersManager() THttpResponse.HeadersManager}
 * with the ID of your headers manager module:
 *
 * ```xml
 * <module id="httpHeaders" class="THttpHeadersManager">
 *   <header Name="Strict-Transport-Security" Value="max-age=31536000" />
 *   <header Name="X-Content-Type-Options" Value="nosniff" />
 *   <header Name="X-Frame-Options" Value="DENY" />
 *   <header class="THttpHeaderCSP">
 *      <policy Name="default-src">'self' www.gstatic.com NONCE</policy>
 *      <policy Name="frame-src">'self' www.google.com</policy>
 *   </header>
 * </module>
 * <module id="response" class="THttpResponse" HeadersManager="httpHeaders" />
 * ```
 *
 * Or equivalently in PHP application configuration:
 * ```php
 * 'modules' => [
 *     'httpHeaders' => [
 *         'class' => 'THttpHeadersManager',
 *         'headers' => [
 *             ['properties' => ['Name' => 'Strict-Transport-Security', 'Value' => 'max-age=31536000']],
 *             ['properties' => ['Name' => 'X-Content-Type-Options', 'Value' => 'nosniff']],
 *             ['properties' => ['Name' => 'X-Frame-Options', 'Value' => 'DENY']],
 *             [
 *                 'class' => 'THttpHeaderCSP',
 *                 'policies' => [
 *                     ['name' => 'default-src', 'value' => "'self' www.gstatic.com NONCE"],
 *                     ['name' => 'frame-src',   'value' => "'self' www.google.com"],
 *                 ],
 *             ],
 *         ],
 *     ],
 *     'response' => [
 *         'class' => 'THttpResponse',
 *         'properties' => ['HeadersManager' => 'httpHeaders'],
 *     ],
 * ],
 * ```
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.3.3
 */
class THttpHeadersManager extends \Prado\TModule
{
	/**
	 * @var THttpHeader[] list of key:value headers.
	 */
	protected $_headers = [];

	/**
	 * @var bool whether headers has been sent
	 */
	private $_headersSent = false;

	private $_defaultMappingClass = \Prado\Web\THttpHeader::class;

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module, can be null
	 */
	public function init($config)
	{
		parent::init($config);
		$this->loadHeaders($config);
	}

	/**
	 * @return string the default class of headers. Defaults to THttpHeader.
	 */
	public function getDefaultMappingClass()
	{
		return $this->_defaultMappingClass;
	}

	/**
	 * Load and configure each header.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration node
	 * @throws TConfigurationException if specific header class is invalid
	 */
	protected function loadHeaders($config)
	{
		$defaultClass = $this->getDefaultMappingClass();

		if (is_array($config)) {
			if (isset($config['headers']) && is_array($config['headers'])) {
				foreach ($config['headers'] as $header) {
					$class = $header['class'] ?? $defaultClass;
					$properties = $header['properties'] ?? [];
					$this->buildHeader($class, $properties, $header);
				}
			}
		} else {
			foreach ($config->getElementsByTagName('header') as $header) {
				$properties = $header->getAttributes();
				if (($class = $properties->remove('class')) === null) {
					$class = $defaultClass;
				}
				$this->buildHeader($class, $properties, $header);
			}
		}
	}

	/**
	 * Creates a header of `$class`, applies `$properties`, appends it to the
	 * header list, and finally calls {@see THttpHeader::init()} with `$config`.
	 * @param string $class class name; must extend {@see THttpHeader}.
	 * @param iterable $properties name-value pairs forwarded to {@see THttpHeader::setSubproperty()}.
	 * @param array|\Prado\Xml\TXmlElement $config raw config node forwarded to {@see THttpHeader::init()}.
	 * @throws TConfigurationException if `$class` does not extend {@see THttpHeader}.
	 */
	private function buildHeader($class, $properties, $config)
	{
		$header = Prado::createComponent($class, $this);
		if (!($header instanceof THttpHeader)) {
			throw new TConfigurationException('httpheadersmanager_header_required');
		}
		foreach ($properties as $name => $value) {
			$header->setSubproperty($name, $value);
		}

		$this->_headers[] = $header;
		$header->init($config);
	}

	/**
	 * Returns the nonce from the first {@see THttpHeaderCSP} header that has one,
	 * or `null` if none is registered.
	 * @return ?string the nonce value, or null
	 */
	public function getNonce(): ?string
	{
		foreach ($this->_headers as $header) {
			if ($header instanceof THttpHeaderCSP && ($nonce = $header->getNonce()) !== null) {
				return $nonce;
			}
		}
		return null;
	}

	/**
	 * Ensures that custom headers are sent by the module
	 */
	public function ensureHeadersSent()
	{
		if (!$this->_headersSent) {
			$this->sendHeaders();
		}
	}

	/**
	 * Send the HTTP headers
	 */
	protected function sendHeaders()
	{
		foreach ($this->_headers as $header) {
			$this->getResponse()->appendHeader((string) $header);
		}
		$this->_headersSent = true;
	}
}
