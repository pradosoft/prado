<?php

/**
 * TEmailConfiguration class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Xml\TXmlElement;
use Prado\Xml\TXmlDocument;

/**
 * TPageConfiguration class
 *
 * TPageConfiguration represents the configuration for a page.
 * The page is specified by a dot separated path.
 * Configurations along this path are merged together to be provided for the page.
 *
 * There are no authorization rules
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @method void dyLoadEmailConfigurationFromPhp($config, $configPath, $configPagePath)
 * @method void dyLoadEmailConfigurationFromXml($dom, $configPath, $configPagePath)
 * @method void dyApplyEmailConfiguration()
 */
class TEmailConfiguration extends \Prado\Web\Services\TPageConfiguration
{
	/**
	 * @var array list of page initial property values
	 */
	private $_emailProperties = [];

	/**
	 * Returns list of page initial property values.
	 * Each array element represents a single property with the key
	 * being the property name and the value the initial property value.
	 * @param $pagePath the pagePath to include page properties.
	 * @return array list of page initial property values
	 */
	public function getEmailProperties($pagePath)
	{
		$properties = $this->getProperties();

		foreach ($this->_emailProperties as $id => $prop) {
			if ($this->hasPageMatch($id, $pagePath)) {
				$properties = array_merge($properties, $prop);
			}
		}
		return $properties;
	}

	public function loadFromPhp($config, $configPath, $configPagePath)
	{
		$this->loadPageConfigurationFromPhp($config, $configPath, $configPagePath);
		$this->dyLoadEmailConfigurationFromPhp($config, $configPath, $configPagePath);
	}

	/**
	 * Loads a page configuration.
	 * The configuration includes information for both application
	 * and page service.
	 * @param \Prado\Xml\TXmlElement $dom config xml element
	 * @param string $configPath the directory containing this configuration
	 * @param string $configPagePath the page path that the config XML is associated with. The page path doesn't include the page name.
	 */
	public function loadFromXml($dom, $configPath, $configPagePath)
	{
		$this->loadPageConfigurationFromXml($dom, $configPath, $configPagePath);
		$this->dyLoadEmailConfigurationFromXml($dom, $configPath, $configPagePath);
	}

	/**
	 * Loads the configuration specific for application part
	 * @param array $config config xml element
	 * @param string $configPath base path corresponding to this array
	 */
	public function loadApplicationConfigurationFromPhp($config, $configPath)
	{
		//Emails have no Application Configuration, do nothing here
	}

	/**
	 * Loads the configuration specific for application part
	 * @param \Prado\Xml\TXmlElement $dom config xml element
	 * @param string $configPath base path corresponding to this xml element
	 */
	public function loadApplicationConfigurationFromXml($dom, $configPath)
	{
		//Emails have no Application Configuration, do nothing here
	}

	/**
	 * If the Page Configuration page path matches the id, add the page properties to the configuration.
	 * @param mixed $id
	 * @param mixed $properties
	 */
	protected function processPageProperties($id, $properties)
	{
		$this->_emailProperties[$id] = array_merge($this->_emailProperties[$id] ?? [], $properties);
	}

	/**
	 * @return bool false, emails have no authorization nor rules.
	 */
	public function getHasAuthorization()
	{
		return false;
	}

	/**
	 * @return string the plural configuration type, 'emails'
	 */
	public function getConfigurationNodes()
	{
		return 'emails';
	}

	/**
	 * @return string the singular configuration type, 'email'
	 */
	public function getConfigurationNode()
	{
		return 'email';
	}
}
