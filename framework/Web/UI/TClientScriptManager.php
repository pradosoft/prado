<?php
/**
 * TClientScriptManager and TClientSideOptions class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\THttpUtility;

/**
 * TClientScriptManager class.
 *
 * TClientScriptManager manages javascript and CSS stylesheets for a page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @package Prado\Web\UI
 * @since 3.0
 */
class TClientScriptManager extends \Prado\TApplicationComponent
{
	/**
	 * file containing javascript packages and their cross dependencies
	 */
	const PACKAGES_FILE = 'Web/Javascripts/packages.php';
	/**
	 * file containing css packages and their cross dependencies
	 */
	const CSS_PACKAGES_FILE = 'Web/Javascripts/css-packages.php';
	/**
	 * @var TPage page who owns this manager
	 */
	private $_page;
	/**
	 * @var array registered hidden fields, indexed by hidden field names
	 */
	private $_hiddenFields = [];
	/**
	 * @var array javascript blocks to be rendered at the beginning of the form
	 */
	private $_beginScripts = [];
	/**
	 * @var array javascript blocks to be rendered at the end of the form
	 */
	private $_endScripts = [];
	/**
	 * @var array javascript files to be rendered in the form
	 */
	private $_scriptFiles = [];
	/**
	 * @var array javascript files to be rendered in page head section
	 */
	private $_headScriptFiles = [];
	/**
	 * @var array javascript blocks to be rendered in page head section
	 */
	private $_headScripts = [];
	/**
	 * @var array CSS files
	 */
	private $_styleSheetFiles = [];
	/**
	 * @var array CSS declarations
	 */
	private $_styleSheets = [];
	/**
	 * @var array registered PRADO script libraries
	 */
	private $_registeredScripts = [];
	/**
	 * Client-side javascript library dependencies, loads from PACKAGES_FILE;
	 * @var array
	 */
	private static $_scripts;
	/**
	 * Client-side javascript library packages, loads from PACKAGES_FILE;
	 * @var array
	 */
	private static $_scriptsPackages;
	/**
	 * Client-side javascript library source folders, loads from PACKAGES_FILE;
	 * @var array
	 */
	private static $_scriptsFolders;
	/**
	 * @var array registered PRADO style libraries
	 */
	private $_registeredStyles = [];
	/**
	 * Client-side style library dependencies, loads from CSS_PACKAGES_FILE;
	 * @var array
	 */
	private static $_styles;
	/**
	 * Client-side style library packages, loads from CSS_PACKAGES_FILE;
	 * @var array
	 */
	private static $_stylesPackages;
	/**
	 * Client-side style library folders, loads from CSS_PACKAGES_FILE;
	 * @var array
	 */
	private static $_stylesFolders;

	private $_renderedHiddenFields;

	private $_renderedScriptFiles = [];

	private $_expandedScripts;
	private $_expandedStyles;

	/**
	 * Constructor.
	 * @param TPage $owner page that owns this client script manager
	 */
	public function __construct(TPage $owner)
	{
		$this->_page = $owner;
	}

	/**
	 * @return bool whether THead is required in order to render CSS and js within head
	 * @since 3.1.1
	 */
	public function getRequiresHead()
	{
		return count($this->_styleSheetFiles) || count($this->_styleSheets)
			|| count($this->_headScriptFiles) || count($this->_headScripts);
	}

	public static function getPradoPackages()
	{
		return self::$_scriptsPackages;
	}

	public static function getPradoScripts()
	{
		return self::$_scripts;
	}

	/**
	 * Registers Prado javascript by library name. See "Web/Javascripts/packages.php"
	 * for library names.
	 * @param string $name script library name.
	 */
	public function registerPradoScript($name)
	{
		$this->registerPradoScriptInternal($name);
		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerPradoScript', $params);
	}

	/**
	 * Registers a Prado javascript library to be loaded.
	 * @param mixed $name
	 */
	protected function registerPradoScriptInternal($name)
	{
		// $this->checkIfNotInRender();
		if (!isset($this->_registeredScripts[$name])) {
			if (self::$_scripts === null) {
				$packageFile = Prado::getFrameworkPath() . DIRECTORY_SEPARATOR . self::PACKAGES_FILE;
				[$folders, $packages, $deps] = include($packageFile);
				self::$_scriptsFolders = $folders;
				self::$_scripts = $deps;
				self::$_scriptsPackages = $packages;
			}

			if (isset(self::$_scripts[$name])) {
				$this->_registeredScripts[$name] = true;
			} else {
				throw new TInvalidOperationException('csmanager_pradoscript_invalid', $name);
			}

			if (($packages = array_keys($this->_registeredScripts)) !== []) {
				$packagesUrl = [];
				$isDebug = $this->getApplication()->getMode() === TApplicationMode::Debug;
				foreach ($packages as $p) {
					foreach (self::$_scripts[$p] as $dep) {
						foreach (self::$_scriptsPackages[$dep] as $script) {
							if (!isset($this->_expandedScripts[$script])) {
								[$base, $subPath] = $this->getScriptPackageFolder($script);
								[$path, $baseUrl] = $this->getPackagePathUrl($base);

								$this->_expandedScripts[$script] = true;
								if ($isDebug) {
									if (!in_array($url = $baseUrl . '/' . $subPath, $packagesUrl)) {
										$packagesUrl[] = $url;
									}
								} else {
									$minPath = preg_replace('/^(.*)(?<!\.min)\.js$/', "\\1.min.js", $subPath);
									if (!in_array($url = $baseUrl . '/' . $minPath, $packagesUrl)) {
										if (!is_file($filePath = $path . DIRECTORY_SEPARATOR . $minPath)) {
											file_put_contents($filePath, TJavaScript::JSMin(file_get_contents($base . '/' . $subPath)));
											chmod($filePath, PRADO_CHMOD);
										}
										$packagesUrl[] = $url;
									}
								}
							}
						}
					}
				}
				foreach ($packagesUrl as $url) {
					$this->registerScriptFile($url, $url);
				}
			}
		}
	}

	/**
	 * @param mixed $script
	 * @return string Prado javascript library base asset url.
	 */
	public function getPradoScriptAssetUrl($script = 'prado')
	{
		if (!isset(self::$_scriptsFolders[$script])) {
			$this->registerPradoScriptInternal($script);
		}

		$base = Prado::getPathOfNameSpace(self::$_scriptsFolders[$script]);
		$assets = Prado::getApplication()->getAssetManager();
		return $assets->getPublishedUrl($base);
	}

	/**
	 * @param mixed $script
	 * @return string Prado javascript library base asset path in local filesystem.
	 */
	public function getPradoScriptAssetPath($script = 'prado')
	{
		if (!isset(self::$_scriptsFolders[$script])) {
			$this->registerPradoScriptInternal($script);
		}

		$base = Prado::getPathOfNameSpace(self::$_scriptsFolders[$script]);
		$assets = Prado::getApplication()->getAssetManager();
		return $assets->getPublishedPath($base);
	}

	/**
	 * Returns the URLs of all script files referenced on the page
	 * @return array Combined list of all script urls used in the page
	 */
	public function getScriptUrls()
	{
		$scripts = array_values($this->_headScriptFiles);
		$scripts = array_merge($scripts, array_values($this->_scriptFiles));
		$scripts = array_unique($scripts);

		return $scripts;
	}

	/**
	 * @param string $base javascript or css package path.
	 * @return array tuple($path,$url).
	 */
	protected function getPackagePathUrl($base)
	{
		$assets = Prado::getApplication()->getAssetManager();
		if (strpos($base, $assets->getBaseUrl()) === false) {
			return [$assets->getPublishedPath($base), $assets->publishFilePath($base)];
		} else {
			return [$assets->getBasePath() . str_replace($assets->getBaseUrl(), '', $base), $base];
		}
	}

	/**
	 * @param string $script javascript package source folder path.
	 * @return array tuple($basepath,$subpath).
	 */
	protected function getScriptPackageFolder($script)
	{
		[$base, $subPath] = explode("/", $script, 2);

		if (!array_key_exists($base, self::$_scriptsFolders)) {
			throw new TInvalidOperationException('csmanager_pradostyle_invalid', $base);
		}

		$namespace = self::$_scriptsFolders[$base];
		if (($dir = Prado::getPathOfNameSpace($namespace)) !== null) {
			$namespace = $dir;
		}

		return [$namespace, $subPath];
	}

	/**
	 * @param string $script css package source folder path.
	 * @return array tuple($basepath,$subpath).
	 */
	protected function getStylePackageFolder($script)
	{
		[$base, $subPath] = explode("/", $script, 2);

		if (!array_key_exists($base, self::$_stylesFolders)) {
			throw new TInvalidOperationException('csmanager_pradostyle_invalid', $base);
		}

		$namespace = self::$_stylesFolders[$base];
		if (($dir = Prado::getPathOfNameSpace($namespace)) !== null) {
			$namespace = $dir;
		}

		return [$namespace, $subPath];
	}

	/**
	 * Returns javascript statement that create a new callback request object.
	 * @param ICallbackEventHandler $callbackHandler callback response handler
	 * @param null|array $options additional callback options
	 * @return string javascript statement that creates a new callback request.
	 */
	public function getCallbackReference(ICallbackEventHandler $callbackHandler, $options = null)
	{
		$options = !is_array($options) ? [] : $options;
		$class = new \ReflectionClass($callbackHandler);
		$clientSide = $callbackHandler->getActiveControl()->getClientSide();
		$options = array_merge($options, $clientSide->getOptions()->toArray());
		$optionString = TJavaScript::encode($options);
		$this->registerPradoScriptInternal('ajax');
		$id = $callbackHandler->getUniqueID();
		return "new Prado.CallbackRequest('{$id}',{$optionString})";
	}

	/**
	 * Registers callback javascript for a control.
	 * @param string $class javascript class responsible for the control being registered for callback
	 * @param array $options callback options
	 */
	public function registerCallbackControl($class, $options)
	{
		$optionString = TJavaScript::encode($options);
		$code = "new {$class}({$optionString});";
		$this->_endScripts[sprintf('%08X', crc32($code))] = $code;
		$this->registerPradoScriptInternal('ajax');

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerCallbackControl', $params);
	}

	/**
	 * Registers postback javascript for a control. A null class parameter will prevent
	 * the javascript code registration.
	 * @param string $class javascript class responsible for the control being registered for postback
	 * @param array $options postback options
	 */
	public function registerPostBackControl($class, $options)
	{
		if ($class === null) {
			return;
		}
		if (!isset($options['FormID']) && ($form = $this->_page->getForm()) !== null) {
			$options['FormID'] = $form->getClientID();
		}
		$optionString = TJavaScript::encode($options);
		$code = "new {$class}({$optionString});";

		$this->_endScripts[sprintf('%08X', crc32($code))] = $code;
		$this->registerPradoScriptInternal('prado');

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerPostBackControl', $params);
	}

	/**
	 * Register a default button to panel. When the $panel is in focus and
	 * the 'enter' key is pressed, the $button will be clicked.
	 * @param string|TControl $panel panel (or its unique ID) to register the default button action
	 * @param string|TControl $button button (or its unique ID) to trigger a postback
	 */
	public function registerDefaultButton($panel, $button)
	{
		$panelID = is_string($panel) ? $panel : $panel->getUniqueID();

		if (is_string($button)) {
			$buttonID = $button;
		} else {
			$button->setIsDefaultButton(true);
			$buttonID = $button->getUniqueID();
		}
		$options = TJavaScript::encode($this->getDefaultButtonOptions($panelID, $buttonID));
		$code = "new Prado.WebUI.DefaultButton($options);";

		$this->_endScripts['prado:' . $panelID] = $code;
		$this->registerPradoScriptInternal('prado');

		$params = [$panelID, $buttonID];
		$this->_page->registerCachingAction('Page.ClientScript', 'registerDefaultButton', $params);
	}

	/**
	 * @param string $panelID the unique ID of the container control
	 * @param string $buttonID the unique ID of the button control
	 * @return array default button options.
	 */
	protected function getDefaultButtonOptions($panelID, $buttonID)
	{
		$options['ID'] = TControl::convertUniqueIdToClientId($panelID);
		$options['Panel'] = TControl::convertUniqueIdToClientId($panelID);
		$options['Target'] = TControl::convertUniqueIdToClientId($buttonID);
		$options['EventTarget'] = $buttonID;
		$options['Event'] = 'click';
		return $options;
	}

	/**
	 * Registers the control to receive default focus.
	 * @param string $target the client ID of the control to receive default focus
	 */
	public function registerFocusControl($target)
	{
		$this->registerPradoScriptInternal('jquery');
		if ($target instanceof TControl) {
			$target = $target->getClientID();
		}
		$this->_endScripts['prado:focus'] = 'jQuery(\'#' . $target . '\').focus();';

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerFocusControl', $params);
	}

	/**
	 * Registers Prado style by library name. See "Web/Javascripts/packages.php"
	 * for library names.
	 * @param string $name style library name.
	 */
	public function registerPradoStyle($name)
	{
		$this->registerPradoStyleInternal($name);
		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerPradoStyle', $params);
	}

	/**
	 * Registers a Prado style library to be loaded.
	 * @param mixed $name
	 */
	protected function registerPradoStyleInternal($name)
	{
		// $this->checkIfNotInRender();
		if (!isset($this->_registeredStyles[$name])) {
			if (self::$_styles === null) {
				$packageFile = Prado::getFrameworkPath() . DIRECTORY_SEPARATOR . self::CSS_PACKAGES_FILE;
				[$folders, $packages, $deps] = include($packageFile);
				self::$_stylesFolders = $folders;
				self::$_styles = $deps;
				self::$_stylesPackages = $packages;
			}

			if (isset(self::$_styles[$name])) {
				$this->_registeredStyles[$name] = true;
			} else {
				throw new TInvalidOperationException('csmanager_pradostyle_invalid', $name);
			}

			if (($packages = array_keys($this->_registeredStyles)) !== []) {
				$packagesUrl = [];
				$isDebug = $this->getApplication()->getMode() === TApplicationMode::Debug;
				foreach ($packages as $p) {
					foreach (self::$_styles[$p] as $dep) {
						foreach (self::$_stylesPackages[$dep] as $style) {
							if (!isset($this->_expandedStyles[$style])) {
								[$base, $subPath] = $this->getStylePackageFolder($style);
								[$path, $baseUrl] = $this->getPackagePathUrl($base);

								$this->_expandedStyles[$style] = true;
								// TODO minify css?
								if (!in_array($url = $baseUrl . '/' . $subPath, $packagesUrl)) {
									$packagesUrl[] = $url;
								}
							}
						}
					}
				}
				foreach ($packagesUrl as $url) {
					$this->registerStyleSheetFile($url, $url);
				}
			}
		}
	}

	/**
	 * Registers a CSS file to be rendered in the page head
	 *
	 * The CSS files in themes are registered in {@link OnPreRenderComplete onPreRenderComplete} if you want to override
	 * CSS styles in themes you need to register it after this event is completed.
	 *
	 * Example:
	 * <code>
	 * <?php
	 * class BasePage extends TPage {
	 *   public function onPreRenderComplete($param) {
	 *     parent::onPreRenderComplete($param);
	 *     $url = 'path/to/your/stylesheet.css';
	 *     $this->Page->ClientScript->registerStyleSheetFile($url, $url);
	 *   }
	 * }
	 * </code>
	 *
	 * @param string $key a unique key identifying the file
	 * @param string $url URL to the CSS file
	 * @param string $media media type of the CSS (such as 'print', 'screen', etc.). Defaults to empty, meaning the CSS applies to all media types.
	 */
	public function registerStyleSheetFile($key, $url, $media = '')
	{
		if ($media === '') {
			$this->_styleSheetFiles[$key] = $url;
		} else {
			$this->_styleSheetFiles[$key] = [$url, $media];
		}

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerStyleSheetFile', $params);
	}

	/**
	 * Registers a CSS block to be rendered in the page head
	 * @param string $key a unique key identifying the CSS block
	 * @param string $css CSS block
	 * @param string $media media type of the CSS (such as 'print', 'screen', etc.). Defaults to empty, meaning the CSS applies to all media types.
	 */
	public function registerStyleSheet($key, $css, $media = '')
	{
		$this->_styleSheets[$key] = $css;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerStyleSheet', $params);
	}

	/**
	 * Returns the URLs of all stylesheet files referenced on the page
	 * @return array List of all stylesheet urls used in the page
	 */
	public function getStyleSheetUrls()
	{
		$stylesheets = array_values(
			array_map(function ($e) {
				return is_array($e) ? $e[0] : $e;
			}, $this->_styleSheetFiles)
		);

		foreach (Prado::getApplication()->getAssetManager()->getPublished() as $path => $url) {
			if (substr($url, strlen($url) - 4) == '.css') {
				$stylesheets[] = $url;
			}
		}

		$stylesheets = array_unique($stylesheets);

		return $stylesheets;
	}

	/**
	 * Returns all the stylesheet code snippets referenced on the page
	 * @return array List of all stylesheet snippets used in the page
	 */
	public function getStyleSheetCodes()
	{
		return array_unique(array_values($this->_styleSheets));
	}

	/**
	 * Registers a javascript file in the page head
	 * @param string $key a unique key identifying the file
	 * @param string $url URL to the javascript file
	 */
	public function registerHeadScriptFile($key, $url)
	{
		$this->checkIfNotInRender();
		$this->_headScriptFiles[$key] = $url;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerHeadScriptFile', $params);
	}

	/**
	 * Registers a javascript block in the page head.
	 * @param string $key a unique key identifying the script block
	 * @param string $script javascript block
	 */
	public function registerHeadScript($key, $script)
	{
		$this->checkIfNotInRender();
		$this->_headScripts[$key] = $script;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerHeadScript', $params);
	}

	/**
	 * Registers a javascript file to be rendered within the form
	 * @param string $key a unique key identifying the file
	 * @param string $url URL to the javascript file to be rendered
	 */
	public function registerScriptFile($key, $url)
	{
		$this->_scriptFiles[$key] = $url;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerScriptFile', $params);
	}

	/**
	 * Registers a javascript script block at the beginning of the form
	 * @param string $key a unique key identifying the script block
	 * @param string $script javascript block
	 */
	public function registerBeginScript($key, $script)
	{
		$this->checkIfNotInRender();
		$this->_beginScripts[$key] = $script;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerBeginScript', $params);
	}

	/**
	 * Registers a javascript script block at the end of the form
	 * @param string $key a unique key identifying the script block
	 * @param string $script javascript block
	 */
	public function registerEndScript($key, $script)
	{
		$this->_endScripts[$key] = $script;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerEndScript', $params);
	}

	/**
	 * Registers a hidden field to be rendered in the form.
	 * @param string $name a unique key identifying the hidden field
	 * @param array|string $value hidden field value, if the value is an array, every element
	 * in the array will be rendered as a hidden field value.
	 */
	public function registerHiddenField($name, $value)
	{
		$this->_hiddenFields[$name] = $value;

		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerHiddenField', $params);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a CSS file registered with the specified key
	 */
	public function isStyleSheetFileRegistered($key)
	{
		return isset($this->_styleSheetFiles[$key]);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a CSS block registered with the specified key
	 */
	public function isStyleSheetRegistered($key)
	{
		return isset($this->_styleSheets[$key]);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a head javascript file registered with the specified key
	 */
	public function isHeadScriptFileRegistered($key)
	{
		return isset($this->_headScriptFiles[$key]);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a head javascript block registered with the specified key
	 */
	public function isHeadScriptRegistered($key)
	{
		return isset($this->_headScripts[$key]);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a javascript file registered with the specified key
	 */
	public function isScriptFileRegistered($key)
	{
		return isset($this->_scriptFiles[$key]);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a beginning javascript block registered with the specified key
	 */
	public function isBeginScriptRegistered($key)
	{
		return isset($this->_beginScripts[$key]);
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is an ending javascript block registered with the specified key
	 */
	public function isEndScriptRegistered($key)
	{
		return isset($this->_endScripts[$key]);
	}

	/**
	 * @return bool true if any end scripts are registered.
	 */
	public function hasEndScripts()
	{
		return count($this->_endScripts) > 0;
	}

	/**
	 * @return bool true if any begin scripts are registered.
	 */
	public function hasBeginScripts()
	{
		return count($this->_beginScripts) > 0;
	}

	/**
	 * @param string $key a unique key
	 * @return bool whether there is a hidden field registered with the specified key
	 */
	public function isHiddenFieldRegistered($key)
	{
		return isset($this->_hiddenFields[$key]);
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderStyleSheetFiles($writer)
	{
		$str = '';
		foreach ($this->_styleSheetFiles as $url) {
			if (is_array($url)) {
				$str .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"{$url[1]}\" href=\"" . THttpUtility::htmlEncode($url[0]) . "\" />\n";
			} else {
				$str .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . THttpUtility::htmlEncode($url) . "\" />\n";
			}
		}
		$writer->write($str);
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderStyleSheets($writer)
	{
		if (count($this->_styleSheets)) {
			$writer->write("<style type=\"text/css\">\n/*<![CDATA[*/\n" . implode("\n", $this->_styleSheets) . "\n/*]]>*/\n</style>\n");
		}
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderHeadScriptFiles($writer)
	{
		$this->renderScriptFiles($writer, $this->_headScriptFiles);
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderHeadScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_headScripts));
	}

	public function renderScriptFilesBegin($writer)
	{
		$this->renderAllPendingScriptFiles($writer);
	}

	public function renderScriptFilesEnd($writer)
	{
		$this->renderAllPendingScriptFiles($writer);
	}

	public function markScriptFileAsRendered($url)
	{
		$this->_renderedScriptFiles[$url] = $url;
		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'markScriptFileAsRendered', $params);
	}

	protected function renderScriptFiles($writer, array $scripts)
	{
		foreach ($scripts as $script) {
			$writer->write(TJavaScript::renderScriptFile($script));
			$this->markScriptFileAsRendered($script);
		}
	}

	protected function getRenderedScriptFiles()
	{
		return $this->_renderedScriptFiles;
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderAllPendingScriptFiles($writer)
	{
		if (!empty($this->_scriptFiles)) {
			$addedScripts = array_diff($this->_scriptFiles, $this->getRenderedScriptFiles());
			$this->renderScriptFiles($writer, $addedScripts);
		}
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderBeginScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_beginScripts));
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderEndScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_endScripts));
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderBeginScriptsCallback($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocksCallback($this->_beginScripts));
	}

	/**
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderEndScriptsCallback($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocksCallback($this->_endScripts));
	}

	public function renderHiddenFieldsBegin($writer)
	{
		$this->renderHiddenFieldsInt($writer, true);
	}

	public function renderHiddenFieldsEnd($writer)
	{
		$this->renderHiddenFieldsInt($writer, false);
	}

	/**
	 * Flushes all pending script registrations
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param null|TControl $control the control forcing the flush (used only in error messages)
	 */
	public function flushScriptFiles($writer, $control = null)
	{
		if (!$this->_page->getIsCallback()) {
			$this->_page->ensureRenderInForm($control);
			$this->renderAllPendingScriptFiles($writer);
		}
	}

	/**
	 * Renders hidden fields. To avoid browsers from trying to restore the previous
	 * state of the fields after a page reload, the autocomplete="off" attribute is used.
	 * Unfortunately this attribute is invalid for hidden fields, so text fields are
	 * rendered instead (#642).
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param mixed $initial
	 */

	protected function renderHiddenFieldsInt($writer, $initial)
	{
		if ($initial) {
			$this->_renderedHiddenFields = [];
		}
		$str = '';
		foreach ($this->_hiddenFields as $name => $value) {
			if (in_array($name, $this->_renderedHiddenFields)) {
				continue;
			}
			$id = strtr($name, ':', '_');
			if (is_array($value)) {
				foreach ($value as $v) {
					$str .= '<input type="text" style="display:none" autocomplete="off" name="' . $name . '[]" id="' . $id . '" value="' . THttpUtility::htmlEncode($value) . "\" />\n";
				}
			} else {
				$str .= '<input type="text" style="display:none" autocomplete="off" name="' . $name . '" id="' . $id . '" value="' . THttpUtility::htmlEncode($value) . "\" />\n";
			}
			$this->_renderedHiddenFields[] = $name;
		}
		if ($str !== '') {
			$writer->write("<div style=\"visibility:hidden;\">\n" . $str . "</div>\n");
		}
	}

	public function getHiddenFields()
	{
		return $this->_hiddenFields;
	}

	/**
	 * Checks whether page rendering has not begun yet
	 */
	protected function checkIfNotInRender()
	{
		if ($form = $this->_page->InFormRender) {
			throw new \Exception('Operation invalid when page is already rendering');
		}
	}
}
