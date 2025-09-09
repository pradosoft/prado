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
 * with the ID of your URL manager module:
 *
 * ```xml
 * <module id="headers" class="THttpHeadersManager">
 *   <header Name="Strict-Transport-Security" Value="max-age=31536000" />
 *   <header Name="X-Content-Type-Options" Value="nosniff" />
 *   <header Name="X-Frame-Options" Value="DENY" />
 * </module>
 * <module id="response" class="THttpResponse" HeadersManager="headers" />
 * ```
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.3.2
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
	private $_headersSent;

	private $_defaultMappingClass = \Prado\Web\THttpHeader::class;

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param mixed $config configuration for this module, can be null
	 */
	public function init($config)
	{
		parent::init($config);
		$this->loadHeaders($config);
	}

	/**
	 * @return string the default class of headers. Defaults to THttpHeader.
	 * @since 3.1.1
	 */
	public function getDefaultMappingClass()
	{
		return $this->_defaultMappingClass;
	}

	/**
	 * Load and configure each header.
	 * @param mixed $config configuration node
	 * @throws TConfigurationException if specific header class is invalid
	 */
	protected function loadHeaders($config)
	{
		$defaultClass = $this->getDefaultMappingClass();

		if (is_array($config)) {
			if (isset($config['urls']) && is_array($config['urls'])) {
				foreach ($config['urls'] as $header) {
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
			$this->getResponse()->appendHeader($header->getName() . ': ' . $header->getValue());
		}
		$this->_headersSent = true;
	}
}
