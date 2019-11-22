<?php
/**
 * TPageService class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Security\TAuthorizationRule;
use Prado\TApplicationConfiguration;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\TApplication;
use Prado\Xml\TXmlElement;
use Prado\Xml\TXmlDocument;

/**
 * TPageConfiguration class
 *
 * TPageConfiguration represents the configuration for a page.
 * The page is specified by a dot-connected path.
 * Configurations along this path are merged together to be provided for the page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\Services
 * @since 3.0
 */
class TPageConfiguration extends \Prado\TComponent
{
	/**
	 * @var array list of application configurations
	 */
	private $_appConfigs = [];
	/**
	 * @var array list of page initial property values
	 */
	private $_properties = [];
	/**
	 * @var TAuthorizationRuleCollection list of authorization rules
	 */
	private $_rules = [];
	/**
	 * @var array list of included configurations
	 */
	private $_includes = [];
	/**
	 * @var string the currently request page in the format of Path\To\PageName
	 */
	private $_pagePath = '';

	/**
	 * Constructor.
	 * @param string $pagePath the currently request page in the format of Path\To\PageName
	 */
	public function __construct($pagePath)
	{
		$this->_pagePath = $pagePath;
	}

	/**
	 * @return array list of external configuration files. Each element is like $filePath=>$condition
	 */
	public function getExternalConfigurations()
	{
		return $this->_includes;
	}

	/**
	 * Returns list of page initial property values.
	 * Each array element represents a single property with the key
	 * being the property name and the value the initial property value.
	 * @return array list of page initial property values
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * Returns list of authorization rules.
	 * The authorization rules are aggregated (bottom-up) from configuration files
	 * along the path to the specified page.
	 * @return TAuthorizationRuleCollection collection of authorization rules
	 */
	public function getRules()
	{
		return $this->_rules;
	}

	/**
	 * @return array list of application configurations specified along page path
	 */
	public function getApplicationConfigurations()
	{
		return $this->_appConfigs;
	}

	/**
	 * Loads configuration for a page specified in a path format.
	 * @param string $basePath root path for pages
	 */
	public function loadFromFiles($basePath)
	{
		$paths = explode('.', $this->_pagePath);
		$page = array_pop($paths);
		$path = $basePath;
		$configPagePath = '';
		$fileName = Prado::getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP
			? TPageService::CONFIG_FILE_PHP
			: TPageService::CONFIG_FILE_XML;
		foreach ($paths as $p) {
			$this->loadFromFile($path . DIRECTORY_SEPARATOR . $fileName, $configPagePath);
			$path .= DIRECTORY_SEPARATOR . $p;
			if ($configPagePath === '') {
				$configPagePath = $p;
			} else {
				$configPagePath .= '.' . $p;
			}
		}
		$this->loadFromFile($path . DIRECTORY_SEPARATOR . $fileName, $configPagePath);
		$this->_rules = new TAuthorizationRuleCollection($this->_rules);
	}

	/**
	 * Loads a specific config file.
	 * @param string $fname config file name
	 * @param string $configPagePath the page path that the config file is associated with. The page path doesn't include the page name.
	 */
	public function loadFromFile($fname, $configPagePath)
	{
		Prado::trace("Loading page configuration file $fname", 'Prado\Web\Services\TPageService');
		if (empty($fname) || !is_file($fname)) {
			return;
		}

		if (Prado::getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			$fcontent = include $fname;
			$this->loadFromPhp($fcontent, dirname($fname), $configPagePath);
		} else {
			$dom = new TXmlDocument;
			if ($dom->loadFromFile($fname)) {
				$this->loadFromXml($dom, dirname($fname), $configPagePath);
			} else {
				throw new TConfigurationException('pageserviceconf_file_invalid', $fname);
			}
		}
	}

	public function loadFromPhp($config, $configPath, $configPagePath)
	{
		$this->loadApplicationConfigurationFromPhp($config, $configPath);
		$this->loadPageConfigurationFromPhp($config, $configPath, $configPagePath);
	}

	/**
	 * Loads a page configuration.
	 * The configuration includes information for both application
	 * and page service.
	 * @param TXmlElement $dom config xml element
	 * @param string $configPath the directory containing this configuration
	 * @param string $configPagePath the page path that the config XML is associated with. The page path doesn't include the page name.
	 */
	public function loadFromXml($dom, $configPath, $configPagePath)
	{
		$this->loadApplicationConfigurationFromXml($dom, $configPath);
		$this->loadPageConfigurationFromXml($dom, $configPath, $configPagePath);
	}

	public function loadApplicationConfigurationFromPhp($config, $configPath)
	{
		$appConfig = new TApplicationConfiguration;
		$appConfig->loadFromPhp($config, $configPath);
		$this->_appConfigs[] = $appConfig;
	}

	/**
	 * Loads the configuration specific for application part
	 * @param TXmlElement $dom config xml element
	 * @param string $configPath base path corresponding to this xml element
	 */
	public function loadApplicationConfigurationFromXml($dom, $configPath)
	{
		$appConfig = new TApplicationConfiguration;
		$appConfig->loadFromXml($dom, $configPath);
		$this->_appConfigs[] = $appConfig;
	}

	public function loadPageConfigurationFromPhp($config, $configPath, $configPagePath)
	{
		// authorization
		if (isset($config['authorization']) && is_array($config['authorization'])) {
			$rules = [];
			foreach ($config['authorization'] as $authorization) {
				$patterns = $authorization['pages'] ?? '';
				$ruleApplies = false;
				if (empty($patterns) || trim($patterns) === '*') { // null or empty string
					$ruleApplies = true;
				} else {
					foreach (explode(',', $patterns) as $pattern) {
						if (($pattern = trim($pattern)) !== '') {
							// we know $configPagePath and $this->_pagePath
							if ($configPagePath !== '') {  // prepend the pattern with ConfigPagePath
								$pattern = $configPagePath . '.' . $pattern;
							}
							if (strcasecmp($pattern, $this->_pagePath) === 0) {
								$ruleApplies = true;
								break;
							}
							if ($pattern[strlen($pattern) - 1] === '*') { // try wildcard matching
								if (strncasecmp($this->_pagePath, $pattern, strlen($pattern) - 1) === 0) {
									$ruleApplies = true;
									break;
								}
							}
						}
					}
				}
				if ($ruleApplies) {
					$action = $authorization['action'] ?? '';
					$users = $authorization['users'] ?? '';
					$roles = $authorization['roles'] ?? '';
					$verb = $authorization['verb'] ?? '';
					$ips = $authorization['ips'] ?? '';
					$rules[] = new TAuthorizationRule($action, $users, $roles, $verb, $ips);
				}
			}
			$this->_rules = array_merge($rules, $this->_rules);
		}
		// pages
		if (isset($config['pages']) && is_array($config['pages'])) {
			if (isset($config['pages']['properties'])) {
				$this->_properties = array_merge($this->_properties, $config['pages']['properties']);
				unset($config['pages']['properties']);
			}
			foreach ($config['pages'] as $id => $page) {
				$properties = [];
				if (isset($page['properties'])) {
					$properties = $page['properties'];
					unset($page['properties']);
				}
				$matching = false;
				$id = ($configPagePath === '') ? $id : $configPagePath . '.' . $id;
				if (strcasecmp($id, $this->_pagePath) === 0) {
					$matching = true;
				} elseif ($id[strlen($id) - 1] === '*') { // try wildcard matching
					$matching = strncasecmp($this->_pagePath, $id, strlen($id) - 1) === 0;
				}
				if ($matching) {
					$this->_properties = array_merge($this->_properties, $properties);
				}
			}
		}

		// external configurations
		if (isset($config['includes']) && is_array($config['includes'])) {
			foreach ($config['includes'] as $include) {
				$when = isset($include['when']) ? true : false;
				if (!isset($include['file'])) {
					throw new TConfigurationException('pageserviceconf_includefile_required');
				}
				$filePath = $include['file'];
				if (isset($this->_includes[$filePath])) {
					$this->_includes[$filePath] = [$configPagePath, '(' . $this->_includes[$filePath][1] . ') || (' . $when . ')'];
				} else {
					$this->_includes[$filePath] = [$configPagePath, $when];
				}
			}
		}
	}

	/**
	 * Loads the configuration specific for page service.
	 * @param TXmlElement $dom config xml element
	 * @param string $configPath base path corresponding to this xml element
	 * @param string $configPagePath the page path that the config XML is associated with. The page path doesn't include the page name.
	 */
	public function loadPageConfigurationFromXml($dom, $configPath, $configPagePath)
	{
		// authorization
		if (($authorizationNode = $dom->getElementByTagName('authorization')) !== null) {
			$rules = [];
			foreach ($authorizationNode->getElements() as $node) {
				$patterns = $node->getAttribute('pages');
				$ruleApplies = false;
				if (empty($patterns) || trim($patterns) === '*') { // null or empty string
					$ruleApplies = true;
				} else {
					foreach (explode(',', $patterns) as $pattern) {
						if (($pattern = trim($pattern)) !== '') {
							// we know $configPagePath and $this->_pagePath
							if ($configPagePath !== '') {  // prepend the pattern with ConfigPagePath
								$pattern = $configPagePath . '.' . $pattern;
							}
							if (strcasecmp($pattern, $this->_pagePath) === 0) {
								$ruleApplies = true;
								break;
							}
							if ($pattern[strlen($pattern) - 1] === '*') { // try wildcard matching
								if (strncasecmp($this->_pagePath, $pattern, strlen($pattern) - 1) === 0) {
									$ruleApplies = true;
									break;
								}
							}
						}
					}
				}
				if ($ruleApplies) {
					$rules[] = new TAuthorizationRule($node->getTagName(), $node->getAttribute('users'), $node->getAttribute('roles'), $node->getAttribute('verb'), $node->getAttribute('ips'));
				}
			}
			$this->_rules = array_merge($rules, $this->_rules);
		}

		// pages
		if (($pagesNode = $dom->getElementByTagName('pages')) !== null) {
			$this->_properties = array_merge($this->_properties, $pagesNode->getAttributes()->toArray());
			// at the page folder
			foreach ($pagesNode->getElementsByTagName('page') as $node) {
				$properties = $node->getAttributes();
				$id = $properties->remove('id');
				if (empty($id)) {
					throw new TConfigurationException('pageserviceconf_page_invalid', $configPath);
				}
				$matching = false;
				$id = ($configPagePath === '') ? $id : $configPagePath . '.' . $id;
				if (strcasecmp($id, $this->_pagePath) === 0) {
					$matching = true;
				} elseif ($id[strlen($id) - 1] === '*') { // try wildcard matching
					$matching = strncasecmp($this->_pagePath, $id, strlen($id) - 1) === 0;
				}
				if ($matching) {
					$this->_properties = array_merge($this->_properties, $properties->toArray());
				}
			}
		}

		// external configurations
		foreach ($dom->getElementsByTagName('include') as $node) {
			if (($when = $node->getAttribute('when')) === null) {
				$when = true;
			}
			if (($filePath = $node->getAttribute('file')) === null) {
				throw new TConfigurationException('pageserviceconf_includefile_required');
			}
			if (isset($this->_includes[$filePath])) {
				$this->_includes[$filePath] = [$configPagePath, '(' . $this->_includes[$filePath][1] . ') || (' . $when . ')'];
			} else {
				$this->_includes[$filePath] = [$configPagePath, $when];
			}
		}
	}
}
