<?php

/**
 * TPluginModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TException;
use Prado\TPropertyValue;
use Prado\Util\Behaviors\TPageServiceExtraPathsBehavior;
use ReflectionClass;

/**
 * TPluginModule class.
 *
 * TPluginModule is for extending PRADO through Composer packages.  This installs
 * its own Pages, where available, and its own error message file for the module.
 *
 * Plugin pages should implement their *.page with the following page code:
 * ```php
 *	<com:TContent ID=<%$ PluginContentId %>>
 *		...  your page content ...
 * 	</com:TContent>
 * ```
 * The 'PluginContentId' application parameter is what all plugin pages should implement as
 * the page TContent ID.  This way all plugin pages can be changed to the page MasterClass
 * layout TContentPlaceHolder for the application and layout. For example in the application
 * configuration file:
 * ```xml
 *	<parameters>
 *	 <parameter id="PluginContentId" value="Main" />
 *  </parameters>
 * ```
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPluginModule extends \Prado\TModule implements IPluginModule
{
	/** Module pages directory for finding pages of a module	 */
	public const PAGES_DIRECTORY = 'Pages';

	/** @var string path of the plugin */
	private $_pluginPath;

	/** @var string path of the pages folder for the plugin*/
	private $_pagesPath;

	/** @var string relative pages path to $_pluginPath */
	private $_relativePagesPath = self::PAGES_DIRECTORY;

	/**
	 * initializes the plugin module, looks for a Pages directory and adds a new {@see TBehavior}
	 * to help TPageService find any plugin module pages
	 * @param array $config this is the manifest for the plugin module
	 */
	public function init($config)
	{
		if ($this->getPluginPagesPath() !== null) {
			$this->getApplication()->attachEventHandler('onBeginRequest', [$this, 'attachPageServiceBehavior']);
		}

		if ($errorFile = $this->getErrorFile()) {
			TException::addMessageFile($errorFile);
		}
		parent::init($config);
	}

	/**
	 * Called onBeginRequest
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function attachPageServiceBehavior($sender, $param)
	{
		$service = $this->getService();
		if ($service instanceof \Prado\Web\Services\TPageService) {
			$service->attachEventHandler('onAdditionalPagePaths', [$this, 'additionalPagePaths']);
		}
	}

	/**
	 * additionalPagePaths returns possible alternative paths for the $pagePath
	 * @param \Prado\Web\Services\TPageService $service
	 * @param string $pagePath
	 */
	public function additionalPagePaths($service, $pagePath)
	{
		return $this->getPluginPagesPath() . DIRECTORY_SEPARATOR . strtr($pagePath, '.', DIRECTORY_SEPARATOR);
	}

	/**
	 * @return null|string the path of the error file for the plugin module
	 */
	public function getErrorFile()
	{
		$errorFile = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'errorMessages.txt';
		if (is_file($errorFile)) {
			return $errorFile;
		}
		return null;
	}

	/**
	 * @return string path of the plugin
	 */
	public function getPluginPath()
	{
		if ($this->_pluginPath === null) {
			$reflect = new ReflectionClass($this::class);
			$this->_pluginPath = dirname($reflect->getFileName());
		}
		return $this->_pluginPath;
	}

	/**
	 * @param string $path
	 */
	public function setPluginPath($path)
	{
		$this->_pluginPath = $path;
	}

	/**
	 * @return string the path of the Pages director for the plugin, if available
	 */
	public function getPluginPagesPath()
	{
		if ($this->_pagesPath === null) {
			$path = $this->getPluginPath();
			$basePath = $path . DIRECTORY_SEPARATOR . $this->_relativePagesPath;
			if (($this->_pagesPath = realpath($basePath)) === false || !is_dir($this->_pagesPath)) {
				$basePath = $path . DIRECTORY_SEPARATOR . strtolower($this->_relativePagesPath);
				if (($this->_pagesPath = realpath($basePath)) === false || !is_dir($this->_pagesPath)) {
					$this->_pagesPath = false;
				}
			}
		}
		return $this->_pagesPath;
	}

	/**
	 * @param string $path
	 */
	public function setPluginPagesPath($path)
	{
		$this->_pagesPath = TPropertyValue::ensureString($path);
		$this->_relativePagesPath = null;
	}

	/**
	 * @return string relative path from PluginPath
	 */
	public function getRelativePagesPath()
	{
		if ($this->_relativePagesPath === null) {
			if (stripos($this->_pagesPath, $this->getPluginPath()) === 0) {
				$this->_relativePagesPath = substr($this->_pagesPath, strlen($this->getPluginPath()) + 1);
			}
		}
		return $this->_relativePagesPath;
	}

	/**
	 * @param string $relativePagesPath relative path of PluginPagesPath from PluginPath
	 */
	public function setRelativePagesPath($relativePagesPath)
	{
		$this->_relativePagesPath = TPropertyValue::ensureString($relativePagesPath);
		$this->_pagesPath = null;
	}
}
