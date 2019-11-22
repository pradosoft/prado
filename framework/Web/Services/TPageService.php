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

use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Web\UI\TTemplateManager;
use Prado\Web\UI\TThemeManager;

/**
 * TPageService class.
 *
 * TPageService implements the service for serving user page requests.
 *
 * Pages that are available to client users are stored under a directory specified by
 * {@link setBasePath BasePath}. The directory may contain subdirectories.
 * Pages serving for a similar goal are usually placed under the same directory.
 * A directory may contain a configuration file <b>config.xml</b> whose content
 * is similar to that of application configuration file.
 *
 * A page is requested via page path, which is a dot-connected directory names
 * appended by the page name. Assume '<BasePath>/Users/Admin' is the directory
 * containing the page 'Update'. Then the page can be requested via 'Users.Admin.Update'.
 * By default, the {@link setBasePath BasePath} of the page service is the "pages"
 * directory under the application base path. You may change this default
 * by setting {@link setBasePath BasePath} with a different path you prefer.
 *
 * Page name refers to the file name (without extension) of the page template.
 * In order to differentiate from the common control template files, the extension
 * name of the page template files must be '.page'. If there is a PHP file with
 * the same page name under the same directory as the template file, that file
 * will be considered as the page class file and the file name is the page class name.
 * If such a file is not found, the page class is assumed as {@link TPage}.
 *
 * Modules can be configured and loaded in page directory configurations.
 * Configuration of a module in a subdirectory will overwrite its parent
 * directory's configuration, if both configurations refer to the same module.
 *
 * By default, TPageService will automatically load two modules:
 * - {@link TTemplateManager} : manages page and control templates
 * - {@link TThemeManager} : manages themes used in a Prado application
 *
 * In page directory configurations, static authorization rules can also be specified,
 * which governs who and which roles can access particular pages.
 * Refer to {@link TAuthorizationRule} for more details about authorization rules.
 * Page authorization rules can be configured within an <authorization> tag in
 * each page directory configuration as follows,
 * <authorization>
 *   <deny pages="Update" users="?" />
 *   <allow pages="Admin" roles="administrator" />
 *   <deny pages="Admin" users="*" />
 * </authorization>
 * where the 'pages' attribute may be filled with a sequence of comma-separated
 * page IDs. If 'pages' attribute does not appear in a rule, the rule will be
 * applied to all pages in this directory and all subdirectories (recursively).
 * Application of authorization rules are in a bottom-up fashion, starting from
 * the directory containing the requested page up to all parent directories.
 * The first matching rule will be used. The last rule always allows all users
 * accessing to any resources.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Web\Services
 * @since 3.0
 */
class TPageService extends \Prado\TService
{
	/**
	 * Configuration file name
	 */
	const CONFIG_FILE_XML = 'config.xml';
	/**
	 * Configuration file name
	 */
	const CONFIG_FILE_PHP = 'config.php';
	/**
	 * Default base path
	 */
	const DEFAULT_BASEPATH = 'Pages';
	/**
	 * Fallback base path - used to be the default up to Prado < 3.2
	 */
	const FALLBACK_BASEPATH = 'pages';
	/**
	 * Prefix of ID used for storing parsed configuration in cache
	 */
	const CONFIG_CACHE_PREFIX = 'prado:pageservice:';
	/**
	 * Page template file extension
	 */
	const PAGE_FILE_EXT = '.page';
	/**
	 * Prefix of Pages used for instantiating new pages
	 */
	const PAGE_NAMESPACE_PREFIX = 'Application\\Pages\\';
	/**
	 * @var string root path of pages
	 */
	private $_basePath;
	/**
	 * @var string base path class in namespace format
	 */
	private $_basePageClass = '\Prado\Web\UI\TPage';
	/**
	 * @var string clientscript manager class in namespace format
	 * @since 3.1.7
	 */
	private $_clientScriptManagerClass = '\Prado\Web\UI\TClientScriptManager';
	/**
	 * @var string default page
	 */
	private $_defaultPage = 'Home';
	/**
	 * @var string requested page (path)
	 */
	private $_pagePath;
	/**
	 * @var TPage the requested page
	 */
	private $_page;
	/**
	 * @var array list of initial page property values
	 */
	private $_properties = [];
	/**
	 * @var bool whether service is initialized
	 */
	private $_initialized = false;
	/**
	 * @var TThemeManager theme manager
	 */
	private $_themeManager;
	/**
	 * @var TTemplateManager template manager
	 */
	private $_templateManager;

	/**
	 * Initializes the service.
	 * This method is required by IService interface and is invoked by application.
	 * @param TXmlElement $config service configuration
	 */
	public function init($config)
	{
		Prado::trace("Initializing TPageService", '\Prado\Web\Services\TPageService');

		$pageConfig = $this->loadPageConfig($config);

		$this->initPageContext($pageConfig);

		$this->_initialized = true;
	}

	/**
	 * Initializes page context.
	 * Page context includes path alias settings, namespace usages,
	 * parameter initialization, module loadings, page initial properties
	 * and authorization rules.
	 * @param TPageConfiguration $pageConfig
	 */
	protected function initPageContext($pageConfig)
	{
		$application = $this->getApplication();
		foreach ($pageConfig->getApplicationConfigurations() as $appConfig) {
			$application->applyConfiguration($appConfig);
		}

		$this->applyConfiguration($pageConfig);
	}

	/**
	 * Applies a page configuration.
	 * @param TPageConfiguration $config the configuration
	 */
	protected function applyConfiguration($config)
	{
		// initial page properties (to be set when page runs)
		$this->_properties = array_merge($this->_properties, $config->getProperties());
		$this->getApplication()->getAuthorizationRules()->mergeWith($config->getRules());
		$pagePath = $this->getRequestedPagePath();
		// external configurations
		foreach ($config->getExternalConfigurations() as $filePath => $params) {
			[$configPagePath, $condition] = $params;
			if ($condition !== true) {
				$condition = $this->evaluateExpression($condition);
			}
			if ($condition) {
				if (($path = Prado::getPathOfNamespace($filePath, Prado::getApplication()->getConfigurationFileExt())) === null || !is_file($path)) {
					throw new TConfigurationException('pageservice_includefile_invalid', $filePath);
				}
				$c = new TPageConfiguration($pagePath);
				$c->loadFromFile($path, $configPagePath);
				$this->applyConfiguration($c);
			}
		}
	}

	/**
	 * Determines the requested page path.
	 * @return string page path requested
	 */
	protected function determineRequestedPagePath()
	{
		$pagePath = $this->getRequest()->getServiceParameter();
		if (empty($pagePath)) {
			$pagePath = $this->getDefaultPage();
		}
		return $pagePath;
	}

	/**
	 * Collects configuration for a page.
	 * @param TXmlElement $config additional configuration specified in the application configuration
	 * @return TPageConfiguration
	 */
	protected function loadPageConfig($config)
	{
		$application = $this->getApplication();
		$pagePath = $this->getRequestedPagePath();
		if (($cache = $application->getCache()) === null) {
			$pageConfig = new TPageConfiguration($pagePath);
			if ($config !== null) {
				if ($application->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
					$pageConfig->loadPageConfigurationFromPhp($config, $application->getBasePath(), '');
				} else {
					$pageConfig->loadPageConfigurationFromXml($config, $application->getBasePath(), '');
				}
			}
			$pageConfig->loadFromFiles($this->getBasePath());
		} else {
			$configCached = true;
			$currentTimestamp = [];
			$arr = $cache->get(self::CONFIG_CACHE_PREFIX . $this->getID() . $pagePath);
			if (is_array($arr)) {
				[$pageConfig, $timestamps] = $arr;
				if ($application->getMode() !== TApplicationMode::Performance) {
					foreach ($timestamps as $fileName => $timestamp) {
						if ($fileName === 0) { // application config file
							$appConfigFile = $application->getConfigurationFile();
							$currentTimestamp[0] = $appConfigFile === null ? 0 : @filemtime($appConfigFile);
							if ($currentTimestamp[0] > $timestamp || ($timestamp > 0 && !$currentTimestamp[0])) {
								$configCached = false;
							}
						} else {
							$currentTimestamp[$fileName] = @filemtime($fileName);
							if ($currentTimestamp[$fileName] > $timestamp || ($timestamp > 0 && !$currentTimestamp[$fileName])) {
								$configCached = false;
							}
						}
					}
				}
			} else {
				$configCached = false;
				$paths = explode('.', $pagePath);
				$configPath = $this->getBasePath();
				$fileName = $this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP
					? self::CONFIG_FILE_PHP
					: self::CONFIG_FILE_XML;
				foreach ($paths as $path) {
					$configFile = $configPath . DIRECTORY_SEPARATOR . $fileName;
					$currentTimestamp[$configFile] = @filemtime($configFile);
					$configPath .= DIRECTORY_SEPARATOR . $path;
				}
				$appConfigFile = $application->getConfigurationFile();
				$currentTimestamp[0] = $appConfigFile === null ? 0 : @filemtime($appConfigFile);
			}
			if (!$configCached) {
				$pageConfig = new TPageConfiguration($pagePath);
				if ($config !== null) {
					if ($application->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
						$pageConfig->loadPageConfigurationFromPhp($config, $application->getBasePath(), '');
					} else {
						$pageConfig->loadPageConfigurationFromXml($config, $application->getBasePath(), '');
					}
				}
				$pageConfig->loadFromFiles($this->getBasePath());
				$cache->set(self::CONFIG_CACHE_PREFIX . $this->getID() . $pagePath, [$pageConfig, $currentTimestamp]);
			}
		}
		return $pageConfig;
	}

	/**
	 * @return TTemplateManager template manager
	 */
	public function getTemplateManager()
	{
		if (!$this->_templateManager) {
			$this->_templateManager = new TTemplateManager;
			$this->_templateManager->init(null);
		}
		return $this->_templateManager;
	}

	/**
	 * @param TTemplateManager $value template manager
	 */
	public function setTemplateManager(TTemplateManager $value)
	{
		$this->_templateManager = $value;
	}

	/**
	 * @return TThemeManager theme manager
	 */
	public function getThemeManager()
	{
		if (!$this->_themeManager) {
			$this->_themeManager = new TThemeManager;
			$this->_themeManager->init(null);
		}
		return $this->_themeManager;
	}

	/**
	 * @param TThemeManager $value theme manager
	 */
	public function setThemeManager(TThemeManager $value)
	{
		$this->_themeManager = $value;
	}

	/**
	 * @return string the requested page path
	 */
	public function getRequestedPagePath()
	{
		if ($this->_pagePath === null) {
			$this->_pagePath = strtr($this->determineRequestedPagePath(), '/\\', '..');
			if (empty($this->_pagePath)) {
				throw new THttpException(404, 'pageservice_page_required');
			}
		}
		return $this->_pagePath;
	}

	/**
	 * @return TPage the requested page
	 */
	public function getRequestedPage()
	{
		return $this->_page;
	}

	/**
	 * @return string default page path to be served if no explicit page is request. Defaults to 'Home'.
	 */
	public function getDefaultPage()
	{
		return $this->_defaultPage;
	}

	/**
	 * @param string $value default page path to be served if no explicit page is request
	 * @throws TInvalidOperationException if the page service is initialized
	 */
	public function setDefaultPage($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('pageservice_defaultpage_unchangeable');
		} else {
			$this->_defaultPage = $value;
		}
	}

	/**
	 * @return string the URL for the default page
	 */
	public function getDefaultPageUrl()
	{
		return $this->constructUrl($this->getDefaultPage());
	}

	/**
	 * @return string the root directory for storing pages. Defaults to the 'pages' directory under the application base path.
	 */
	public function getBasePath()
	{
		if ($this->_basePath === null) {
			$basePath = $this->getApplication()->getBasePath() . DIRECTORY_SEPARATOR . self::DEFAULT_BASEPATH;
			if (($this->_basePath = realpath($basePath)) === false || !is_dir($this->_basePath)) {
				$basePath = $this->getApplication()->getBasePath() . DIRECTORY_SEPARATOR . self::FALLBACK_BASEPATH;
				if (($this->_basePath = realpath($basePath)) === false || !is_dir($this->_basePath)) {
					throw new TConfigurationException('pageservice_basepath_invalid', $basePath);
				}
			}
		}
		return $this->_basePath;
	}

	/**
	 * @param string $value root directory (in namespace form) storing pages
	 * @throws TInvalidOperationException if the service is initialized already or basepath is invalid
	 */
	public function setBasePath($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('pageservice_basepath_unchangeable');
		} elseif (($path = Prado::getPathOfNamespace($value)) === null || !is_dir($path)) {
			throw new TConfigurationException('pageservice_basepath_invalid', $value);
		}
		$this->_basePath = realpath($path);
	}

	/**
	 * Sets the base page class name (in namespace format).
	 * If a page only has a template file without page class file,
	 * this base page class will be instantiated.
	 * @param string $value class name
	 */
	public function setBasePageClass($value)
	{
		$this->_basePageClass = $value;
	}

	/**
	 * @return string base page class name in namespace format. Defaults to 'TPage'.
	 */
	public function getBasePageClass()
	{
		return $this->_basePageClass;
	}

	/**
	 * Sets the clientscript manager class (in namespace format).
	 * @param string $value class name
	 * @since 3.1.7
	 */
	public function setClientScriptManagerClass($value)
	{
		$this->_clientScriptManagerClass = $value;
	}

	/**
	 * @return string clientscript manager class in namespace format. Defaults to 'Prado\Web\UI\TClientScriptManager'.
	 * @since 3.1.7
	 */
	public function getClientScriptManagerClass()
	{
		return $this->_clientScriptManagerClass;
	}

	/**
	 * Runs the service.
	 * This will create the requested page, initializes it with the property values
	 * specified in the configuration, and executes the page.
	 */
	public function run()
	{
		Prado::trace("Running page service", 'Prado\Web\Services\TPageService');
		$this->_page = $this->createPage($this->getRequestedPagePath());
		$this->runPage($this->_page, $this->_properties);
	}

	/**
	 * Creates a page instance based on requested page path.
	 * @param string $pagePath requested page path
	 * @throws THttpException if requested page path is invalid
	 * @throws TConfigurationException if the page class cannot be found
	 * @return TPage the requested page instance
	 */
	protected function createPage($pagePath)
	{
		$path = $this->getBasePath() . DIRECTORY_SEPARATOR . strtr($pagePath, '.', DIRECTORY_SEPARATOR);
		$hasTemplateFile = is_file($path . self::PAGE_FILE_EXT);
		$hasClassFile = is_file($path . Prado::CLASS_FILE_EXT);

		if (!$hasTemplateFile && !$hasClassFile) {
			throw new THttpException(404, 'pageservice_page_unknown', $pagePath);
		}

		if ($hasClassFile) {
			$className = basename($path);
			$namespacedClassName = static::PAGE_NAMESPACE_PREFIX . str_replace('.', '\\', $pagePath);

			if (!class_exists($className, false) && !class_exists($namespacedClassName, false)) {
				include_once($path . Prado::CLASS_FILE_EXT);
			}

			if (!class_exists($className, false)) {
				$className = $namespacedClassName;
			}
		} else {
			$className = $this->getBasePageClass();
			Prado::using($className);
			if (($pos = strrpos($className, '.')) !== false) {
				$className = substr($className, $pos + 1);
			}
		}

		if ($className !== '\Prado\Web\UI\TPage' && !is_subclass_of($className, '\Prado\Web\UI\TPage')) {
			throw new THttpException(404, 'pageservice_page_unknown', $pagePath);
		}

		$page = Prado::createComponent($className);
		$page->setPagePath($pagePath);

		if ($hasTemplateFile) {
			$page->setTemplate($this->getTemplateManager()->getTemplateByFileName($path . self::PAGE_FILE_EXT));
		}

		return $page;
	}

	/**
	 * Executes a page.
	 * @param TPage $page the page instance to be run
	 * @param array $properties list of initial page properties
	 */
	protected function runPage($page, $properties)
	{
		foreach ($properties as $name => $value) {
			$page->setSubProperty($name, $value);
		}
		$page->run($this->getResponse()->createHtmlWriter());
	}

	/**
	 * Constructs a URL with specified page path and GET parameters.
	 * @param string $pagePath page path
	 * @param array $getParams list of GET parameters, null if no GET parameters required
	 * @param bool $encodeAmpersand whether to encode the ampersand in URL, defaults to true.
	 * @param bool $encodeGetItems whether to encode the GET parameters (their names and values), defaults to true.
	 * @return string URL for the page and GET parameters
	 */
	public function constructUrl($pagePath, $getParams = null, $encodeAmpersand = true, $encodeGetItems = true)
	{
		return $this->getRequest()->constructUrl($this->getID(), $pagePath, $getParams, $encodeAmpersand, $encodeGetItems);
	}
}
