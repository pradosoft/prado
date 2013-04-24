<?php
/**
 * TClientScriptManager and TClientSideOptions class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TClientScriptManager.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI
 */

/**
 * TClientScriptManager class.
 *
 * TClientScriptManager manages javascript and CSS stylesheets for a page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @version $Id: TClientScriptManager.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI
 * @since 3.0
 */
class TClientScriptManager extends TApplicationComponent
{
	/**
	 * directory containing Prado javascript files
	 */
	const SCRIPT_PATH='Web/Javascripts/source';
	/**
	 * file containing javascript packages and their cross dependencies
	 */
	const PACKAGES_FILE='Web/Javascripts/packages.php';
	/**
	 * @var TPage page who owns this manager
	 */
	private $_page;
	/**
	 * @var array registered hidden fields, indexed by hidden field names
	 */
	private $_hiddenFields=array();
	/**
	 * @var array javascript blocks to be rendered at the beginning of the form
	 */
	private $_beginScripts=array();
	/**
	 * @var array javascript blocks to be rendered at the end of the form
	 */
	private $_endScripts=array();
	/**
	 * @var array javascript files to be rendered in the form
	 */
	private $_scriptFiles=array();
	/**
	 * @var array javascript files to be rendered in page head section
	 */
	private $_headScriptFiles=array();
	/**
	 * @var array javascript blocks to be rendered in page head section
	 */
	private $_headScripts=array();
	/**
	 * @var array CSS files
	 */
	private $_styleSheetFiles=array();
	/**
	 * @var array CSS declarations
	 */
	private $_styleSheets=array();
	/**
	 * @var array registered PRADO script libraries
	 */
	private $_registeredPradoScripts=array();
	/**
	 * Client-side javascript library dependencies, loads from PACKAGES_FILE;
	 * @var array
	 */
	private static $_pradoScripts;
	/**
	 * Client-side javascript library packages, loads from PACKAGES_FILE;
	 * @var array
	 */
	private static $_pradoPackages;

	private $_renderedHiddenFields;

	private $_renderedScriptFiles=array();

	private $_expandedPradoScripts;

	/**
	 * Constructor.
	 * @param TPage page that owns this client script manager
	 */
	public function __construct(TPage $owner)
	{
		$this->_page=$owner;
	}

	/**
	 * @return boolean whether THead is required in order to render CSS and js within head
	 * @since 3.1.1
	 */
	public function getRequiresHead()
	{
		return count($this->_styleSheetFiles) || count($this->_styleSheets)
			|| count($this->_headScriptFiles) || count($this->_headScripts);
	}

	public static function getPradoPackages()
	{
		return self::$_pradoPackages;
	}

	public static function getPradoScripts()
	{
		return self::$_pradoScripts;
	}

	/**
	 * Registers Prado javascript by library name. See "Web/Javascripts/packages.php"
	 * for library names.
	 * @param string script library name.
	 */
	public function registerPradoScript($name)
	{
		$this->registerPradoScriptInternal($name);
		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerPradoScript',$params);
	}

	/**
	 * Registers a Prado javascript library to be loaded.
	 */
	protected function registerPradoScriptInternal($name)
	{
		// $this->checkIfNotInRender();
		if(!isset($this->_registeredPradoScripts[$name]))
		{
			if(self::$_pradoScripts === null)
			{
				$packageFile = Prado::getFrameworkPath().DIRECTORY_SEPARATOR.self::PACKAGES_FILE;
				list($packages,$deps)= include($packageFile);
				self::$_pradoScripts = $deps;
				self::$_pradoPackages = $packages;
			}

			if (isset(self::$_pradoScripts[$name]))
				$this->_registeredPradoScripts[$name]=true;
			else
				throw new TInvalidOperationException('csmanager_pradoscript_invalid',$name);
				
			if(($packages=array_keys($this->_registeredPradoScripts))!==array())
			{
				$base = Prado::getFrameworkPath().DIRECTORY_SEPARATOR.self::SCRIPT_PATH;
				list($path,$baseUrl)=$this->getPackagePathUrl($base);
				$packagesUrl=array();
				$isDebug=$this->getApplication()->getMode()===TApplicationMode::Debug;
				foreach ($packages as $p)
				{
					foreach (self::$_pradoScripts[$p] as $dep)
					{
						foreach (self::$_pradoPackages[$dep] as $script)
						if (!isset($this->_expandedPradoScripts[$script]))
						{
							$this->_expandedPradoScripts[$script] = true;
							if($isDebug)
							{
								if (!in_array($url=$baseUrl.'/'.$script,$packagesUrl))
									$packagesUrl[]=$url;
							} else {
								if (!in_array($url=$baseUrl.'/min/'.$script,$packagesUrl))
								{
									if(!is_file($filePath=$path.'/min/'.$script))
									{
										$dirPath=dirname($filePath);
										if(!is_dir($dirPath))
											mkdir($dirPath, PRADO_CHMOD, true);
										file_put_contents($filePath, TJavaScript::JSMin(file_get_contents($base.'/'.$script)));
										chmod($filePath, PRADO_CHMOD);
									}
									$packagesUrl[]=$url;
								}
							}
						}
					}
				}
				foreach($packagesUrl as $url)
					$this->registerScriptFile($url,$url);
			}
		}
	}

	/**
	 * @return string Prado javascript library base asset url.
	 */
	public function getPradoScriptAssetUrl()
	{
		$base = Prado::getFrameworkPath().DIRECTORY_SEPARATOR.self::SCRIPT_PATH;
		$assets = Prado::getApplication()->getAssetManager();
		return $assets->getPublishedUrl($base);
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
	 * @param string javascript package path.
	 * @return array tuple($path,$url).
	 */
	protected function getPackagePathUrl($base)
	{
		$assets = Prado::getApplication()->getAssetManager();
		if(strpos($base, $assets->getBaseUrl())===false)
		{
			if(($dir = Prado::getPathOfNameSpace($base)) !== null) {
				$base = $dir;
			}
			return array($assets->getPublishedPath($base), $assets->publishFilePath($base));
		}
		else
		{
			return array($assets->getBasePath().str_replace($assets->getBaseUrl(),'',$base), $base);
		}
	}

	/**
	 * Returns javascript statement that create a new callback request object.
	 * @param ICallbackEventHandler callback response handler
	 * @param array additional callback options
	 * @return string javascript statement that creates a new callback request.
	 */
	public function getCallbackReference(ICallbackEventHandler $callbackHandler, $options=null)
	{
		$options = !is_array($options) ? array() : $options;
		$class = new ReflectionClass($callbackHandler);
		$clientSide = $callbackHandler->getActiveControl()->getClientSide();
		$options = array_merge($options, $clientSide->getOptions()->toArray());
		$optionString = TJavaScript::encode($options);
		$this->registerPradoScriptInternal('ajax');
		$id = $callbackHandler->getUniqueID();
		return "new Prado.CallbackRequest('{$id}',{$optionString})";
	}

	/**
	 * Registers callback javascript for a control.
	 * @param string javascript class responsible for the control being registered for callback
	 * @param array callback options
	 */
	public function registerCallbackControl($class, $options)
	{
		$optionString=TJavaScript::encode($options);
		$code="new {$class}({$optionString});";
		$this->_endScripts[sprintf('%08X', crc32($code))]=$code;
		$this->registerPradoScriptInternal('ajax');

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerCallbackControl',$params);
	}

	/**
	 * Registers postback javascript for a control. A null class parameter will prevent
	 * the javascript code registration.
	 * @param string javascript class responsible for the control being registered for postback
	 * @param array postback options
	 */
	public function registerPostBackControl($class,$options)
	{
		if($class === null) {
			return;
		}
		if(!isset($options['FormID']) && ($form=$this->_page->getForm())!==null)
			$options['FormID']=$form->getClientID();
		$optionString=TJavaScript::encode($options);
		$code="new {$class}({$optionString});";

		$this->_endScripts[sprintf('%08X', crc32($code))]=$code;
		$this->_hiddenFields[TPage::FIELD_POSTBACK_TARGET]='';
		$this->_hiddenFields[TPage::FIELD_POSTBACK_PARAMETER]='';
		$this->registerPradoScriptInternal('prado');

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerPostBackControl',$params);
	}

	/**
	 * Register a default button to panel. When the $panel is in focus and
	 * the 'enter' key is pressed, the $button will be clicked.
	 * @param TControl|string panel (or its unique ID) to register the default button action
	 * @param TControl|string button (or its unique ID) to trigger a postback
	 */
	public function registerDefaultButton($panel, $button)
	{
		$panelID=is_string($panel)?$panel:$panel->getUniqueID();

		if(is_string($button))
			$buttonID=$button;
		else
		{
			$button->setIsDefaultButton(true);
			$buttonID=$button->getUniqueID();
		}
		$options = TJavaScript::encode($this->getDefaultButtonOptions($panelID, $buttonID));
		$code = "new Prado.WebUI.DefaultButton($options);";

		$this->_endScripts['prado:'.$panelID]=$code;
		$this->_hiddenFields[TPage::FIELD_POSTBACK_TARGET]='';
		$this->registerPradoScriptInternal('prado');

		$params=array($panelID,$buttonID);
		$this->_page->registerCachingAction('Page.ClientScript','registerDefaultButton',$params);
	}

	/**
	 * @param string the unique ID of the container control
	 * @param string the unique ID of the button control
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
	 * @param string the client ID of the control to receive default focus
	 */
	public function registerFocusControl($target)
	{
		$this->registerPradoScriptInternal('effects');
		if($target instanceof TControl)
			$target=$target->getClientID();
		$id = TJavaScript::quoteString($target);
		$this->_endScripts['prado:focus'] = 'new Effect.ScrollTo('.$id.'); Prado.Element.focus('.$id.');';

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerFocusControl',$params);
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
	 * ?>
	 * </code>
	 *
	 * @param string a unique key identifying the file
	 * @param string URL to the CSS file
	 * @param string media type of the CSS (such as 'print', 'screen', etc.). Defaults to empty, meaning the CSS applies to all media types.
	 */
	public function registerStyleSheetFile($key,$url,$media='')
	{
		if($media==='')
			$this->_styleSheetFiles[$key]=$url;
		else
			$this->_styleSheetFiles[$key]=array($url,$media);

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerStyleSheetFile',$params);
	}

	/**
	 * Registers a CSS block to be rendered in the page head
	 * @param string a unique key identifying the CSS block
	 * @param string CSS block
	 */
	public function registerStyleSheet($key,$css,$media='')
	{
		$this->_styleSheets[$key]=$css;

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerStyleSheet',$params);
	}

	/**
	 * Returns the URLs of all stylesheet files referenced on the page
	 * @return array Combined list of all stylesheet urls used in the page
	 */
	public function getStyleSheetUrls()
	{

		$stylesheets = array_values(array_merge($this->_styleSheetFiles, $this->_styleSheets));

		foreach(Prado::getApplication()->getAssetManager()->getPublished() as $path=>$url)
			if (substr($url,strlen($url)-4)=='.css')
				$stylesheets[] = $url;

		$stylesheets = array_unique($stylesheets);

		return $stylesheets;
	}

	/**
	 * Registers a javascript file in the page head
	 * @param string a unique key identifying the file
	 * @param string URL to the javascript file
	 */
	public function registerHeadScriptFile($key,$url)
	{
		$this->checkIfNotInRender();
		$this->_headScriptFiles[$key]=$url;

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerHeadScriptFile',$params);
	}

	/**
	 * Registers a javascript block in the page head.
	 * @param string a unique key identifying the script block
	 * @param string javascript block
	 */
	public function registerHeadScript($key,$script)
	{
		$this->checkIfNotInRender();
		$this->_headScripts[$key]=$script;

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerHeadScript',$params);
	}

	/**
	 * Registers a javascript file to be rendered within the form
	 * @param string a unique key identifying the file
	 * @param string URL to the javascript file to be rendered
	 */
	public function registerScriptFile($key, $url)
	{
		$this->_scriptFiles[$key]=$url;
		
		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerScriptFile',$params);
	}

	/**
	 * Registers a javascript script block at the beginning of the form
	 * @param string a unique key identifying the script block
	 * @param string javascript block
	 */
	public function registerBeginScript($key,$script)
	{
		$this->checkIfNotInRender();
		$this->_beginScripts[$key]=$script;

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerBeginScript',$params);
	}

	/**
	 * Registers a javascript script block at the end of the form
	 * @param string a unique key identifying the script block
	 * @param string javascript block
	 */
	public function registerEndScript($key,$script)
	{
		$this->_endScripts[$key]=$script;

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerEndScript',$params);
	}

	/**
	 * Registers a hidden field to be rendered in the form.
	 * @param string a unique key identifying the hidden field
	 * @param string|array hidden field value, if the value is an array, every element
	 * in the array will be rendered as a hidden field value.
	 */
	public function registerHiddenField($name,$value)
	{
		$this->_hiddenFields[$name]=$value;

		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','registerHiddenField',$params);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a CSS file registered with the specified key
	 */
	public function isStyleSheetFileRegistered($key)
	{
		return isset($this->_styleSheetFiles[$key]);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a CSS block registered with the specified key
	 */
	public function isStyleSheetRegistered($key)
	{
		return isset($this->_styleSheets[$key]);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a head javascript file registered with the specified key
	 */
	public function isHeadScriptFileRegistered($key)
	{
		return isset($this->_headScriptFiles[$key]);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a head javascript block registered with the specified key
	 */
	public function isHeadScriptRegistered($key)
	{
		return isset($this->_headScripts[$key]);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a javascript file registered with the specified key
	 */
	public function isScriptFileRegistered($key)
	{
		return isset($this->_scriptFiles[$key]);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a beginning javascript block registered with the specified key
	 */
	public function isBeginScriptRegistered($key)
	{
		return isset($this->_beginScripts[$key]);
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is an ending javascript block registered with the specified key
	 */
	public function isEndScriptRegistered($key)
	{
		return isset($this->_endScripts[$key]);
	}

	/**
	 * @return boolean true if any end scripts are registered.
	 */
	public function hasEndScripts()
	{
		return count($this->_endScripts) > 0;
	}

	/**
	 * @return boolean true if any begin scripts are registered.
	 */
	public function hasBeginScripts()
	{
		return count($this->_beginScripts) > 0;
	}

	/**
	 * @param string a unique key
	 * @return boolean whether there is a hidden field registered with the specified key
	 */
	public function isHiddenFieldRegistered($key)
	{
		return isset($this->_hiddenFields[$key]);
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderStyleSheetFiles($writer)
	{
		$str='';
		foreach($this->_styleSheetFiles as $url)
		{
			if(is_array($url))
				$str.="<link rel=\"stylesheet\" type=\"text/css\" media=\"{$url[1]}\" href=\"".THttpUtility::htmlEncode($url[0])."\" />\n";
			else
				$str.="<link rel=\"stylesheet\" type=\"text/css\" href=\"".THttpUtility::htmlEncode($url)."\" />\n";
		}
		$writer->write($str);
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderStyleSheets($writer)
	{
		if(count($this->_styleSheets))
			$writer->write("<style type=\"text/css\">\n/*<![CDATA[*/\n".implode("\n",$this->_styleSheets)."\n/*]]>*/\n</style>\n");
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderHeadScriptFiles($writer)
	{
		$this->renderScriptFiles($writer,$this->_headScriptFiles);
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
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
		$params=func_get_args();
		$this->_page->registerCachingAction('Page.ClientScript','markScriptFileAsRendered',$params);
	}

	protected function renderScriptFiles($writer, Array $scripts)
	{
		foreach($scripts as $script)
		{
			$writer->write(TJavaScript::renderScriptFile($script));
			$this->markScriptFileAsRendered($script);
		}
	}

	protected function getRenderedScriptFiles()
	{
		return $this->_renderedScriptFiles;
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderAllPendingScriptFiles($writer)
	{
		if(!empty($this->_scriptFiles))
		{
			$addedScripts = array_diff($this->_scriptFiles,$this->getRenderedScriptFiles());
			$this->renderScriptFiles($writer,$addedScripts);
		}
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderBeginScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_beginScripts));
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderEndScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_endScripts));
	}

	public function renderHiddenFieldsBegin($writer)
	{
		$this->renderHiddenFieldsInt($writer,true);
	}

	public function renderHiddenFieldsEnd($writer)
	{
		$this->renderHiddenFieldsInt($writer,false);
	}

	/**
	 * Flushes all pending script registrations
	 * @param THtmlWriter writer for the rendering purpose
	 * @param TControl the control forcing the flush (used only in error messages)
	 */
	public function flushScriptFiles($writer, $control=null)
	{
		$this->_page->ensureRenderInForm($control);
		$this->renderAllPendingScriptFiles($writer);
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	protected function renderHiddenFieldsInt($writer, $initial)
 	{
		if ($initial) $this->_renderedHiddenFields = array();
		$str='';
		foreach($this->_hiddenFields as $name=>$value)
		{
			if (in_array($name,$this->_renderedHiddenFields)) continue;
			$id=strtr($name,':','_');
			if(is_array($value))
			{
				foreach($value as $v)
					$str.='<input type="hidden" name="'.$name.'[]" id="'.$id.'" value="'.THttpUtility::htmlEncode($value)."\" />\n";
			}
			else
			{
				$str.='<input type="hidden" name="'.$name.'" id="'.$id.'" value="'.THttpUtility::htmlEncode($value)."\" />\n";
			}
			$this->_renderedHiddenFields[] = $name;
		}
		if($str!=='')
			$writer->write("<div style=\"visibility:hidden;\">\n".$str."</div>\n");
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
		if ($form = $this->_page->InFormRender)
			throw new Exception('Operation invalid when page is already rendering');
	}
}

/**
 * TClientSideOptions abstract class.
 *
 * TClientSideOptions manages client-side options for components that have
 * common client-side javascript behaviours and client-side events such as
 * between ActiveControls and validators.
 *
 * @author <weizhuo[at]gmail[dot]com>
 * @version $Id: TClientScriptManager.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI
 * @since 3.0
 */
abstract class TClientSideOptions extends TComponent
{
	/**
	 * @var TMap list of client-side options.
	 */
	private $_options;

	/**
	 * Constructor, initialize the options list.
	 */
	public function __construct()
	{
		$this->_options = Prado::createComponent('System.Collections.TMap');
	}

	/**
	 * Adds on client-side event handler by wrapping the code within a
	 * javascript function block. If the code begins with "javascript:", the
	 * code is assumed to be a javascript function block rather than arbiturary
	 * javascript statements.
	 * @param string option name
	 * @param string javascript statements.
	 */
	protected function setFunction($name, $code)
	{
		if(!TJavaScript::isJsLiteral($code))
			$code = TJavaScript::quoteJsLiteral($this->ensureFunction($code));
		$this->setOption($name, $code);
	}

	/**
	 * @return string gets a particular option, null if not set.
	 */
	protected function getOption($name)
	{
		return $this->_options->itemAt($name);
	}

	/**
	 * @param string option name
	 * @param mixed option value.
	 */
	protected function setOption($name, $value)
	{
		$this->_options->add($name, $value);
	}

	/**
	 * @return TMap gets the list of options as TMap
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Ensure that the javascript statements are wrapped in a javascript
	 * function block as <code>function(sender, parameter){ //code }</code>.
	 */
	protected function ensureFunction($javascript)
	{
		return "function(sender, parameter){ {$javascript} }";
	}
}

