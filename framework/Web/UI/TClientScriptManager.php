<?php
/**
 * TClientScriptManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TClientScriptManager class.
 *
 * TClientScriptManager manages javascript and CSS stylesheets for a page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
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
		'ajax'			=> array('prado', 'effects', 'ajax'),
		'validator'		=> array('prado', 'validator'),
		'logger'		=> array('prado', 'logger'),
		'datepicker'	=> array('prado', 'datepicker'),
		'rico'			=> array('prado', 'effects', 'ajax', 'rico'),
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
			$basePath=Prado::getFrameworkPath().'/'.self::SCRIPT_PATH;
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
			$basePath=Prado::getFrameworkPath().'/'.self::SCRIPT_PATH;
			$scriptLoader=$basePath.'/'.self::SCRIPT_LOADER;
			$url=$this->publishFilePath($scriptLoader).'?js='.trim($files,',');
			if($this->getApplication()->getMode()===TApplication::STATE_DEBUG)
				$url.='&__nocache';
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
		$this->registerPradoScript('prado');
	}

	/**
	 * Registers the control to receive default focus.
	 * @param TControl|string the control or the client ID of the HTML element to receive default focus
	 */
	public function registerFocusControl($target)
	{
		$this->registerPradoScript('prado');
		if($target instanceof TControl)
			$target=$target->getClientID();
		$this->registerEndScript('prado:focus','Prado.Focus.setFocus("'.TJavaScript::quoteString($target).'");');
	}

	/**
	 * @return array default button options.
	 */
	protected function getDefaultButtonOptions($panel, $button)
	{
		$options['Panel'] = $panel->getClientID();
		$options['Target'] = $button->getClientID();
		$options['Event'] = 'click';
		return $options;
	}

	/**
	 * Registers a CSS file to be rendered in the page head
	 * @param string a unique key identifying the file
	 * @param string URL to the CSS file
	 */
	public function registerStyleSheetFile($key,$url)
	{
		$this->_styleSheetFiles[$key]=$url;
	}

	/**
	 * Registers a CSS block to be rendered in the page head
	 * @param string a unique key identifying the CSS block
	 * @param string CSS block
	 */
	public function registerStyleSheet($key,$css)
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
	 * @param string hidden field value
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
			$str.="<link rel=\"stylesheet\" type=\"text/css\" href=\"".THttpUtility::htmlEncode($url)."\" />\n";
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
				$value=THttpUtility::htmlEncode($value);
				$str.="<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />\n";
				// set hidden field value to true to indicate this field is rendered
				// Note, hidden field rendering is invoked twice (at the beginning and ending of TForm)
				$this->_hiddenFields[$name]=true;
			}
		}
		if($str!=='')
			$writer->write("<div>\n".$str."</div>\n");
	}
}

?>