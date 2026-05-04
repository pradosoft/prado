<?php

/**
 * TClientScriptManager and TClientSideOptions class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TEventResults;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptAsset;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\THttpUtility;

/**
 * TClientScriptManager class.
 *
 * TClientScriptManager manages javascript and CSS stylesheets for a page.
 *
 * This class provides functionality for registering and rendering JavaScript files
 * and blocks, CSS stylesheets and blocks, hidden fields, and callback/postback
 * scripts. It integrates with the PRADO framework's asset management system
 * to publish and manage JavaScript libraries.
 *
 * ## JavaScript Package System
 *
 * PRADO uses a package-based JavaScript loading system defined in
 * {@see PACKAGES_FILE} (Web/Javascripts/packages.php). Each package consists of:
 * - **Folders**: Base folders for JavaScript libraries in PRADO namespace notation
 * - **Packages**: Named collections of JavaScript files
 * - **Dependencies**: Package dependency mappings determining load order
 *
 * Available default packages include: 'prado', 'jquery', 'ajax', 'validator',
 * 'datepicker', 'tabpanel', 'accordion', 'slider', 'keyboard', 'htmlarea',
 * 'htmlarea5', 'colorpicker', 'ratings', 'inlineeditor', 'activefileupload',
 * 'activedatepicker', 'logger', 'tinymce', 'highlightjs', 'clipboard', etc.
 *
 * ## Global Event fxLoadPradoJavascript
 *
 * When {@see ensurePradoJavascript} is called, it raises the global event
 * `fxLoadPradoJavascript` to allow plugins to extend the JavaScript package
 * system. This event uses the feed-forward pattern via
 * {@see \Prado\TEventResults::EVENT_RESULT_FEED_FORWARD}, allowing event
 * handlers to modify the folders, packages, and dependencies arrays.
 *
 * The event signature is:
 * ```php
 * function fxLoadPradoJavascript($sender, $foldersPackagesDependencies)
 * ```
 *
 * - `string $sender` The class name raising the event ('TClientScriptManager')
 * - `array $foldersPackagesDependencies` Array containing:
 *   - `[0] => array $folders` Map of package base folder keys to PRADO namespace paths
 *   - `[1] => array $packages` Map of package names to arrays of script file paths
 *   - `[2] => array $dependencies` Map of package names to arrays of dependency package names
 *
 * Handlers should return the modified `$foldersPackagesDependencies` array
 * to feed forward the updated data. Multiple handlers can progressively modify
 * the data. Returning null is no results, and is skipped in the feed forward.
 *
 * Example plugin implementation:
 * ```php
 * class TMyPlugin extends \Prado\Util\TBehavior
 * {
 *     public function fxLoadPradoJavascript($sender, $foldersPackagesDependencies)
 *     {
 *         [$folders, $packages, $dependencies] = $foldersPackagesDependencies;
 *
 *         // Add new JavaScript folder
 *         $folders['myplugin'] = 'MyPlugin\\Javascript';
 *
 *         // Add new package
 *         $packages['myplugin'] = ['myplugin/myplugin.js'];
 *
 *         // Add dependencies
 *         $dependencies['myplugin'] = ['jquery', 'prado'];
 *
 *         return [$folders, $packages, $dependencies];
 *     }
 * }
 * ```
 *
 * ## CSS Package System
 *
 * CSS stylesheets use a similar package system defined in
 * {@see CSS_PACKAGES_FILE} (Web/Javascripts/css-packages.php) with the same
 * folder, package, and dependency structure.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @since 3.0
 */
class TClientScriptManager extends \Prado\TApplicationComponent
{
	/**
	 * file containing javascript packages and their cross dependencies
	 */
	public const PACKAGES_FILE = 'Web/Javascripts/packages.php';
	/**
	 * file containing css packages and their cross dependencies
	 */
	public const CSS_PACKAGES_FILE = 'Web/Javascripts/css-packages.php';
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
	 * @var array registered PRADO style libraries, keyed by style name.
	 */
	private $_registeredStyles = [];
	/**
	 * Client-side style library dependencies, keyed by package name.
	 * Loaded from CSS_PACKAGES_FILE and fxLoadPradoJavascript.
	 * @var array
	 */
	private static $_styles;
	/**
	 * Client-side style library packages, keyed by package name.
	 * Loaded from CSS_PACKAGES_FILE and fxLoadPradoJavascript.
	 * @var array
	 */
	private static $_stylesPackages;
	/**
	 * Client-side style library folders, keyed by base folder key.
	 * Loaded from CSS_PACKAGES_FILE and fxLoadPradoJavascript.
	 * @var array
	 */
	private static $_stylesFolders;

	/**
	 * @var array Tracks which hidden fields have been rendered to prevent duplicate rendering.
	 */
	private $_renderedHiddenFields;

	/**
	 * @var array Map of script URLs that have been rendered.
	 */
	private $_renderedScriptFiles = [];

	/**
	 * @var array Map of expanded JavaScript scripts to prevent duplicate expansion.
	 */
	private $_expandedScripts;
	/**
	 * @var array Map of expanded CSS styles to prevent duplicate expansion.
	 */
	private $_expandedStyles;

	/**
	 * Constructor.
	 * @param TPage $owner page that owns this client script manager
	 */
	public function __construct(TPage $owner)
	{
		$this->_page = $owner;
		parent::__construct();
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

	/**
	 * Returns the PRADO JavaScript packages definitions.
	 *
	 * Packages define named collections of JavaScript files and their dependencies.
	 * Ensures JavaScript packages are loaded via {@see ensurePradoJavascript}.
	 *
	 * @return array Map of package names to arrays of script file paths relative to their package folder.
	 */
	public static function getPradoPackages()
	{
		static::ensurePradoJavascript();
		return self::$_scriptsPackages;
	}

	/**
	 * Returns the PRADO JavaScript package dependencies.
	 *
	 * Dependencies map each package name to an array of its dependency package names.
	 * The dependencies determine the load order when registering scripts.
	 * Ensures JavaScript packages are loaded via {@see ensurePradoJavascript}.
	 *
	 * @return array Map of package names to arrays of dependency package names.
	 */
	public static function getPradoScripts()
	{
		static::ensurePradoJavascript();
		return self::$_scripts;
	}

	/**
	 * Returns the PRADO JavaScript source folder mappings.
	 *
	 * Folders map package base keys (e.g., 'prado', 'jquery') to PRADO namespace
	 * paths where the JavaScript files are located.
	 * Ensures JavaScript packages are loaded via {@see ensurePradoJavascript}.
	 *
	 * @return array Map of package base keys to PRADO namespace paths.
	 * @since 4.3.3
	 */
	public static function getPradoScriptsFolders()
	{
		static::ensurePradoJavascript();
		return self::$_scriptsFolders;
	}

	/**
	 * Ensures the PRADO JavaScript packages are loaded.
	 *
	 * This method loads the JavaScript package definitions from {@see PACKAGES_FILE}
	 * and raises the global event `fxLoadPradoJavascript` to allow plugins to extend
	 * the package system with custom JavaScript libraries.
	 *
	 * The event uses {@see \Prado\TEventResults::EVENT_RESULT_FEED_FORWARD}, meaning
	 * handlers can modify and return the folders/packages/dependencies array to
	 * feed forward their changes. See the class docblock for the full event signature
	 * and example implementation.
	 *
	 * {@see TPage::getClientScript()} calls {@see TPageService::getClientScriptManagerClass()}
	 * to instantiate its own client script manager.
	 *
	 * @since 4.3.3
	 * @see loadPradoJavascript
	 */
	public static function ensurePradoJavascript()
	{
		if (self::$_scripts === null) {
			$foldersPackagesDependencies = static::loadPradoJavascript();

			// Connect Plugins
			$results = Prado::getApplication()->raiseEvent('fxLoadPradoJavascript', static::class, $foldersPackagesDependencies, TEventResults::EVENT_RESULT_FEED_FORWARD);
			if (!empty($results)) {
				$foldersPackagesDependencies = array_pop($results);
			}

			[$folders, $packages, $deps] = $foldersPackagesDependencies;

			self::$_scriptsFolders = $folders;
			self::$_scriptsPackages = $packages;
			self::$_scripts = $deps;
		}
	}

	/**
	 * This loads the Prado Javascript Folders, Packages, and Dependencies.
	 * @return array[3] Script Folders, Scripts, and Script Packages.
	 * @since 4.3.3
	 */
	public static function loadPradoJavascript()
	{
		$packageFile = Prado::getFrameworkPath() . DIRECTORY_SEPARATOR . self::PACKAGES_FILE;
		return include($packageFile);
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
			static::ensurePradoJavascript();

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
											chmod($filePath, Prado::getDefaultFilePermissions());
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
	 * Returns the published asset URL for a PRADO JavaScript library.
	 *
	 * This method ensures the JavaScript packages are loaded, registers the script
	 * if not already registered, and returns the published asset URL through the
	 * application's asset manager.
	 *
	 * @param string $script The script package key (e.g., 'prado', 'jquery', 'ajax').
	 *                       Defaults to 'prado'.
	 * @return string The published asset URL for the script library.
	 */
	public function getPradoScriptAssetUrl($script = 'prado')
	{
		static::ensurePradoJavascript();

		if (!isset(self::$_scriptsFolders[$script])) {
			$this->registerPradoScriptInternal($script);
		}

		$base = Prado::getPathOfNameSpace(self::$_scriptsFolders[$script]);
		$assets = $this->getApplication()->getAssetManager();
		return $assets->getPublishedUrl($base);
	}

	/**
	 * Returns the local filesystem path for a PRADO JavaScript library asset.
	 *
	 * This method ensures the JavaScript packages are loaded, registers the script
	 * if not already registered, and returns the published filesystem path through
	 * the application's asset manager.
	 *
	 * @param string $script The script package key (e.g., 'prado', 'jquery', 'ajax').
	 *                       Defaults to 'prado'.
	 * @return string The published filesystem path for the script library.
	 */
	public function getPradoScriptAssetPath($script = 'prado')
	{
		static::ensurePradoJavascript();

		if (!isset(self::$_scriptsFolders[$script])) {
			$this->registerPradoScriptInternal($script);
		}

		$base = Prado::getPathOfNameSpace(self::$_scriptsFolders[$script]);
		$assets = $this->getApplication()->getAssetManager();
		return $assets->getPublishedPath($base);
	}

	/**
	 * Returns all registered JavaScript file URLs.
	 *
	 * This includes both head section scripts (registered via {@see registerHeadScriptFile})
	 * and form-body scripts (registered via {@see registerScriptFile}). URLs are returned
	 * in registration order with duplicates removed.
	 *
	 * @return array Combined list of all unique script URLs used in the page.
	 */
	public function getScriptUrls()
	{
		static::ensurePradoJavascript();

		$scripts = array_values(array_map(function ($v) {
			return $v->getUrl();
		}, $this->_headScriptFiles));
		$scripts = array_merge($scripts, array_values($this->_scriptFiles));
		$scripts = array_unique($scripts);

		return $scripts;
	}

	/**
	 * Resolves the filesystem path and URL for a package base folder.
	 *
	 * If the base path is not already published via the asset manager,
	 * it will be published. Returns both the local filesystem path and
	 * the corresponding URL for the published asset.
	 *
	 * @param string $base The base folder path (javascript or css package path).
	 * @return array Tuple containing `[$path, $url]` where:
	 *   - `string $path` Local filesystem path to the published asset
	 *   - `string $url` URL to access the published asset
	 */
	protected function getPackagePathUrl($base)
	{
		$assets = $this->getApplication()->getAssetManager();
		if (strpos($base, $assets->getBaseUrl()) === false) {
			return [$assets->getPublishedPath($base), $assets->publishFilePath($base)];
		} else {
			return [$assets->getBasePath() . str_replace($assets->getBaseUrl(), '', $base), $base];
		}
	}

	/**
	 * Resolves the base folder and relative path for a JavaScript package script.
	 *
	 * Given a script path like 'prado/prado.js', this splits it into the package
	 * base folder (e.g., 'prado') and the relative script path (e.g., 'prado.js').
	 * The base folder is resolved from the namespace to a filesystem directory.
	 *
	 * @param string $script JavaScript package source path (e.g., 'prado/prado.js').
	 * @throws TInvalidOperationException if the package base folder is not registered
	 * @return array Tuple containing `[$basepath, $subpath]` where:
	 *   - `string $basepath` Resolved filesystem path to the package base folder
	 *   - `string $subpath` Relative path to the script within the package
	 */
	protected function getScriptPackageFolder($script)
	{
		static::ensurePradoJavascript();

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
	 * Resolves the base folder and relative path for a CSS package stylesheet.
	 *
	 * Given a style path like 'jquery-ui/jquery-ui.css', this splits it into the package
	 * base folder (e.g., 'jquery-ui') and the relative stylesheet path (e.g., 'jquery-ui.css').
	 * The base folder is resolved from the namespace to a filesystem directory.
	 *
	 * @param string $script CSS package source path (e.g., 'jquery-ui/jquery-ui.css').
	 * @throws TInvalidOperationException if the package base folder is not registered
	 * @return array Tuple containing `[$basepath, $subpath]` where:
	 *   - `string $basepath` Resolved filesystem path to the package base folder
	 *   - `string $subpath` Relative path to the stylesheet within the package
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
	 * @param ICallbackEventHandler&\Prado\Web\UI\TControl $callbackHandler callback response handler
	 * @param null|array $options additional callback options
	 * @return string javascript statement that creates a new callback request.
	 */
	public function getCallbackReference(ICallbackEventHandler $callbackHandler, $options = null)
	{
		$options = !is_array($options) ? [] : $options;
		$clientSide = $callbackHandler->getActiveControl()->getClientSide();
		$options = array_merge($options, $clientSide->getOptions()->toArray());
		$optionString = TJavaScript::encode($options);
		$this->registerPradoScriptInternal('ajax');
		$id = $callbackHandler->getUniqueID();
		return "new Prado.CallbackRequest('{$id}',{$optionString})";
	}

	/**
	 * Registers a JavaScript callback control.
	 *
	 * This registers a JavaScript class that handles callback requests from a control.
	 * The callback script will be rendered at the end of the form. This method also
	 * automatically registers the 'ajax' Prado script package which is required
	 * for callback functionality.
	 *
	 * @param string $class JavaScript class name responsible for handling the callback
	 *                      (e.g., 'Prado.WebUI.CallbackControl').
	 * @param array $options Callback options that will be passed to the JavaScript class constructor.
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
	 * Registers a JavaScript postback control.
	 *
	 * This registers a JavaScript class that handles postback requests from a control.
	 * The postback script will be rendered at the end of the form. This method also
	 * automatically registers the 'prado' Prado script package which is required
	 * for postback functionality.
	 *
	 * If $class is null, no JavaScript code will be registered.
	 *
	 * @param null|string $class JavaScript class name responsible for handling the postback
	 *                           (e.g., 'Prado.WebUI.PostBackControl'), or null to skip registration.
	 * @param array $options Postback options that will be passed to the JavaScript class constructor.
	 *                       If 'FormID' is not provided, it will be auto-detected from the page form.
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
		} elseif ($button instanceof \Prado\Web\UI\IButtonControl) {
			$button->setIsDefaultButton(true);
			$buttonID = $button->getUniqueID();
		} else {
			return;
		}
		$options = TJavaScript::encode($this->getDefaultButtonOptions($panelID, $buttonID));
		$code = "new Prado.WebUI.DefaultButton($options);";

		$this->_endScripts['prado:' . $panelID] = $code;
		$this->registerPradoScriptInternal('prado');

		$params = [$panelID, $buttonID];
		$this->_page->registerCachingAction('Page.ClientScript', 'registerDefaultButton', $params);
	}

	/**
	 * Builds the JavaScript options for a default button registration.
	 *
	 * These options are used by the Prado.WebUI.DefaultButton JavaScript class
	 * to handle the default button behavior (pressing Enter in a panel triggers
	 * the button click).
	 *
	 * @param string $panelID The unique ID of the container panel control.
	 * @param string $buttonID The unique ID of the button control to trigger.
	 * @return array Default button options containing:
	 *   - `ID`: Client-side ID of the panel
	 *   - `Panel`: Client-side ID of the panel (same as ID)
	 *   - `Target`: Client-side ID of the button
	 *   - `EventTarget`: Server-side unique ID of the button
	 *   - `Event`: Event name ('click')
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
	 * Registers a PRADO CSS style library to be loaded.
	 *
	 * Style libraries are defined in {@see CSS_PACKAGES_FILE} and include
	 * packages like 'jquery-ui', etc. The registered styles will be
	 * automatically expanded to include their dependencies.
	 *
	 * @param string $name The CSS library name (e.g., 'jquery-ui').
	 * @throws TInvalidOperationException if the style name is not a valid registered package.
	 */
	public function registerPradoStyle($name)
	{
		$this->registerPradoStyleInternal($name);
		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'registerPradoStyle', $params);
	}

	/**
	 * Internal method to register a Prado CSS style library.
	 *
	 * This method handles the actual registration logic including loading the CSS
	 * package definitions, validating the style name, expanding dependencies,
	 * and registering the stylesheet files. Style registrations are cached.
	 *
	 * @param string $name The CSS library name (e.g., 'jquery-ui').
	 * @throws TInvalidOperationException if the style name is not a valid registered package.
	 * @see CSS_PACKAGES_FILE
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
	 * The CSS files in themes are registered in {@see OnPreRenderComplete onPreRenderComplete} if you want to override
	 * CSS styles in themes you need to register it after this event is completed.
	 *
	 * Example:
	 * ```php
	 * <?php
	 * class BasePage extends TPage {
	 *   public function onPreRenderComplete($param) {
	 *     parent::onPreRenderComplete($param);
	 *     $url = 'path/to/your/stylesheet.css';
	 *     $this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	 *   }
	 * }
	 * ```
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

		foreach ($this->getApplication()->getAssetManager()->getPublished() as $path => $url) {
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
	 * @param bool $async load the javascript file asynchronously, default false
	 */
	public function registerHeadScriptFile($key, $url, $async = false)
	{
		$this->checkIfNotInRender();
		$this->_headScriptFiles[$key] = new TJavaScriptAsset($url, $async);

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
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
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
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderStyleSheets($writer)
	{
		if (count($this->_styleSheets)) {
			$writer->write("<style type=\"text/css\">\n/*<![CDATA[*/\n" . implode("\n", $this->_styleSheets) . "\n/*]]>*/\n</style>\n");
		}
	}

	/**
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderHeadScriptFiles($writer)
	{
		$this->renderScriptFiles($writer, $this->_headScriptFiles);
	}

	/**
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderHeadScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_headScripts));
	}

	/**
	 * Renders pending script files at the beginning of the form.
	 *
	 * This is typically called during form rendering to output script file tags.
	 * Scripts marked as already rendered will be skipped.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 */
	public function renderScriptFilesBegin($writer)
	{
		$this->renderAllPendingScriptFiles($writer);
	}

	/**
	 * Renders pending script files at the end of the form.
	 *
	 * This is typically called during form rendering to output script file tags.
	 * Scripts marked as already rendered will be skipped.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 */
	public function renderScriptFilesEnd($writer)
	{
		$this->renderAllPendingScriptFiles($writer);
	}

	/**
	 * Marks a script file as rendered to prevent duplicate rendering.
	 *
	 * Call this method to indicate that a script file has already been output
	 * and should not be rendered again by subsequent render calls.
	 *
	 * @param string|TJavaScriptAsset $url The URL of the script file to mark as rendered,
	 *                                      or a TJavaScriptAsset object.
	 */
	public function markScriptFileAsRendered($url)
	{
		$url = (is_object($url) && ($url instanceof TJavaScriptAsset)) ? $url->getUrl() : $url;
		$this->_renderedScriptFiles[$url] = $url;
		$params = func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript', 'markScriptFileAsRendered', $params);
	}

	/**
	 * Renders a collection of script files to the writer.
	 *
	 * Each script is output using {@see TJavaScript::renderScriptFile} and
	 * marked as rendered.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 * @param array $scripts Array of script file URLs or TJavaScriptAsset objects.
	 */
	protected function renderScriptFiles($writer, array $scripts)
	{
		foreach ($scripts as $script) {
			$writer->write(TJavaScript::renderScriptFile($script));
			$this->markScriptFileAsRendered($script);
		}
	}

	/**
	 * Returns the list of script files that have been marked as rendered.
	 *
	 * @return array Map of rendered script URLs to themselves.
	 */
	protected function getRenderedScriptFiles()
	{
		return $this->_renderedScriptFiles;
	}

	/**
	 * Renders all pending (not yet rendered) script files.
	 *
	 * Only scripts that have not been marked as rendered will be output.
	 * After rendering, scripts are marked as rendered to prevent duplicates.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 */
	public function renderAllPendingScriptFiles($writer)
	{
		if (!empty($this->_scriptFiles)) {
			$addedScripts = array_diff($this->_scriptFiles, $this->getRenderedScriptFiles());
			$this->renderScriptFiles($writer, $addedScripts);
		}
	}

	/**
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderBeginScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_beginScripts));
	}

	/**
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderEndScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_endScripts));
	}

	/**
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderBeginScriptsCallback($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocksCallback($this->_beginScripts));
	}

	/**
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderEndScriptsCallback($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocksCallback($this->_endScripts));
	}

	/**
	 * Renders hidden fields at the beginning of the form.
	 *
	 * Hidden fields are rendered as text input elements with autocomplete="off"
	 * to prevent browser restoration of values on page reload.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 */
	public function renderHiddenFieldsBegin($writer)
	{
		$this->renderHiddenFieldsInt($writer, true);
	}

	/**
	 * Renders hidden fields at the end of the form.
	 *
	 * Hidden fields that were already rendered in begin will be skipped.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 */
	public function renderHiddenFieldsEnd($writer)
	{
		$this->renderHiddenFieldsInt($writer, false);
	}

	/**
	 * Flushes all pending script file registrations.
	 *
	 * This forces any pending script files to be rendered immediately.
	 * This is useful when a control needs to ensure its scripts are included
	 * before rendering completes. On callback requests, this does nothing
	 * as scripts are handled differently for callbacks.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer The HTML writer for output.
	 * @param null|TControl $control The control forcing the flush (used only in error messages).
	 * @see renderAllPendingScriptFiles
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
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
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

	/**
	 * Returns all registered hidden fields.
	 *
	 * @return array Map of hidden field names to their values.
	 *                If a value is an array, multiple hidden fields will be rendered.
	 */
	public function getHiddenFields()
	{
		return $this->_hiddenFields;
	}

	/**
	 * Checks whether page rendering has not begun yet
	 * @throws \Exception
	 */
	protected function checkIfNotInRender()
	{
		if ($form = $this->_page->getInFormRender()) {
			throw new \Exception('Operation invalid when page is already rendering');
		}
	}
}
