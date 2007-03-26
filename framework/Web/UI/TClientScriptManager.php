<?php
/**
 * TClientScriptManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI
 */

/**
 * TClientScriptManager class.
 *
 * TClientScriptManager manages javascript and CSS stylesheets for a page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI
 * @since 3.0
 */
class TClientScriptManager extends TApplicationComponent
{
	/**
	 * directory containing Prado javascript files
	 */
	const SCRIPT_PATH='Web/Javascripts/js';
	/**
	 * the PHP script for loading Prado javascript files
	 */
	const SCRIPT_LOADER='clientscripts.php';

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
	 * @var array javascript files to be renderd in page head section
	 */
	private $_headScriptFiles=array();
	/**
	 * @var array javascript blocks to be renderd in page head section
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
	 * @var array registered PRADO script files
	 */
	private $_registeredPradoFiles=array();
	/**
	 * Client-side javascript library dependencies
	 * @var array
	 */
	private static $_pradoScripts=array(
		'prado'			=> array('prado'),
		'effects'		=> array('prado', 'effects'),
		'validator'		=> array('prado', 'validator'),
		'logger'		=> array('prado', 'logger'),
		'datepicker'	=> array('prado', 'datepicker'),
		'colorpicker'	=> array('prado', 'colorpicker')
		);

	/**
	 * Constructor.
	 * @param TPage page that owns this client script manager
	 */
	public function __construct(TPage $owner)
	{
		$this->_page=$owner;
	}

	/**
	 * Registers Prado scripts by library name.
	 * Each library may include one or several script files.
	 * Currently, the following libraries are available:
	 * - prado : basic prado js framework
	 * - effects :
	 * - ajax : ajax related js
	 * - validator : validator js
	 * - logger : js logger
	 * - datepicker : datepicker js
	 * - rico :
	 * - colorpicker : colorpicker js
	 * The script files registered will be published.
	 * @param string script library name.
	 */
	public function registerPradoScript($name)
	{
		if(!isset($this->_registeredPradoScripts[$name]))
		{
			$this->_registeredPradoScripts[$name]=true;
			if(!isset(self::$_pradoScripts[$name]))
				throw new TInvalidOperationException('csmanager_pradoscript_invalid',$name);
			$basePath=$this->getPradoScriptBasePath();
			foreach(self::$_pradoScripts[$name] as $script)
			{
				if(!isset($this->_registeredPradoFiles[$script]))
				{
					$this->publishFilePath($basePath.'/'.$script.'.js');
					$this->_registeredPradoFiles[$script]=false;
				}
			}
		}
	}

	/**
	 * @return string the directory containing the PRADO js script files
	 */
	protected function getPradoScriptBasePath()
	{
		$basePath = Prado::getFrameworkPath().'/'.self::SCRIPT_PATH;
		if($this->getApplication()->getMode()===TApplicationMode::Debug)
			return $basePath.'/debug';
		else
			return $basePath.'/compressed';
	}

	/**
	 * Renders the HTML tags for PRADO js files
	 * @param THtmlWriter writer
	 */
	protected function renderPradoScripts($writer)
	{
		$files='';
		foreach($this->_registeredPradoFiles as $file=>$rendered)
		{
			if(!$rendered)
			{
				$files.=','.$file;
				$this->_registeredPradoFiles[$file]=true;
			}
		}
		if($files!=='')
		{
			$basePath=$this->getPradoScriptBasePath();
			$scriptLoader=$basePath.'/'.self::SCRIPT_LOADER;
			$url=$this->publishFilePath($scriptLoader).'?js='.trim($files,',');
			if($this->getApplication()->getMode()===TApplicationMode::Debug)
				$url.='&amp;mode=debug';
			$writer->write(TJavaScript::renderScriptFile($url));
		}
	}

	/**
	 * Registers postback javascript for a control.
	 * @param string javascript class responsible for the control being registered for postback
	 * @param array postback options
	 */
	public function registerPostBackControl($jsClass,$options)
	{
		if(!isset($options['FormID']))
			$options['FormID']=$this->_page->getForm()->getClientID();
		$optionString=TJavaScript::encode($options);
		$code="new $jsClass($optionString);";
		$this->registerEndScript(sprintf('%08X', crc32($code)), $code);

		$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
		$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');

		$this->registerPradoScript('prado');
	}

	/**
	 * Register a default button to panel. When the $panel is in focus and
	 * the 'enter' key is pressed, the $button will be clicked.
	 * @param TControl panel to register the default button action
	 * @param TControl button to trigger a postback
	 */
	public function registerDefaultButton($panel, $button)
	{
		$options = TJavaScript::encode($this->getDefaultButtonOptions($panel, $button));
		$code = "new Prado.WebUI.DefaultButton($options);";
		$this->registerEndScript("prado:".$panel->getClientID(), $code);
		$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
		$this->registerPradoScript('prado');
	}

	/**
	 * Registers the control to receive default focus.
	 * @param TControl|string the control or the client ID of the HTML element to receive default focus
	 */
	public function registerFocusControl($target)
	{
		$this->registerPradoScript('effects');
		if($target instanceof TControl)
			$target=$target->getClientID();
		$id = TJavaScript::quoteString($target);
		$script = 'new Effect.ScrollTo("'.$id.'"); Prado.Element.focus("'.$id.'");';
		$this->registerEndScript('prado:focus',$script);
	}

	/**
	 * @return array default button options.
	 */
	protected function getDefaultButtonOptions($panel, $button)
	{
		$options['Panel'] = $panel->getClientID();
		$options['Target'] = $button->getClientID();
		$options['EventTarget'] = $button->getUniqueID();
		$options['Event'] = 'click';
		return $options;
	}

	/**
	 * Registers a CSS file to be rendered in the page head
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
	}

	/**
	 * Registers a CSS block to be rendered in the page head
	 * @param string a unique key identifying the CSS block
	 * @param string CSS block
	 */
	public function registerStyleSheet($key,$css,$media='')
	{
		$this->_styleSheets[$key]=$css;
	}

	/**
	 * Registers a javascript file in the page head
	 * @param string a unique key identifying the file
	 * @param string URL to the javascript file
	 */
	public function registerHeadScriptFile($key,$url)
	{
		$this->_headScriptFiles[$key]=$url;
	}

	/**
	 * Registers a javascript block in the page head.
	 * @param string a unique key identifying the script block
	 * @param string javascript block
	 */
	public function registerHeadScript($key,$script)
	{
		$this->_headScripts[$key]=$script;
	}

	/**
	 * Registers a javascript file to be rendered within the form
	 * @param string a unique key identifying the file
	 * @param string URL to the javascript file to be rendered
	 */
	public function registerScriptFile($key,$url)
	{
		if(!isset($this->_scriptFiles[$key]))
			$this->_scriptFiles[$key]=$url;
	}

	/**
	 * Registers a javascript script block at the beginning of the form
	 * @param string a unique key identifying the script block
	 * @param string javascript block
	 */
	public function registerBeginScript($key,$script)
	{
		$this->_beginScripts[$key]=$script;
	}

	/**
	 * Registers a javascript script block at the end of the form
	 * @param string a unique key identifying the script block
	 * @param string javascript block
	 */
	public function registerEndScript($key,$script)
	{
		$this->_endScripts[$key]=$script;
	}

	/**
	 * Registers a hidden field to be rendered in the form.
	 * @param string a unique key identifying the hidden field
	 * @param string|array hidden field value, if the value is an array, every element
	 * in the array will be rendered as a hidden field value.
	 */
	public function registerHiddenField($name,$value)
	{
		if(!isset($this->_hiddenFields[$name]))
			$this->_hiddenFields[$name]=$value;
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
		$writer->write(TJavaScript::renderScriptFiles($this->_headScriptFiles));
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderHeadScripts($writer)
	{
		$writer->write(TJavaScript::renderScriptBlocks($this->_headScripts));
	}

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderScriptFiles($writer)
	{
		$this->renderPradoScripts($writer);
		$files=array();
		foreach($this->_scriptFiles as $key=>$file)
		{
			if($file!==true)
			{
				$files[]=$file;
				$this->_scriptFiles[$key]=true;
			}
		}
		if(!empty($files))
			$writer->write(TJavaScript::renderScriptFiles($files));
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

	/**
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderHiddenFields($writer)
	{
		$str='';
		foreach($this->_hiddenFields as $name=>$value)
		{
			if($value!==true)
			{
				if(is_array($value))
				{
					foreach($value as $v)
						$str.='<input type="hidden" name="'.$name.'[]" id="'.$name.'" value="'.THttpUtility::htmlEncode($value)."\" />\n";
				}
				else
				{
					$str.='<input type="hidden" name="'.$name.'" id="'.$name.'" value="'.THttpUtility::htmlEncode($value)."\" />\n";
				}
				// set hidden field value to true to indicate this field is rendered
				// Note, hidden field rendering is invoked twice (at the beginning and ending of TForm)
				$this->_hiddenFields[$name]=true;
			}
		}
		if($str!=='')
			$writer->write("<div>\n".$str."</div>\n");
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
 * @version $Id$
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
		if(!TJavascript::isFunction($code))
			$code = TJavascript::quoteFunction($this->ensureFunction($code));
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

?>