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

Prado::using('System.Web.Javascripts.*');

/**
 * TClientScriptManager class
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
	private $_beginScripts=array();
	private $_endScripts=array();
	private $_scriptFiles=array();
	private $_onSubmitStatements=array();
	private $_arrayDeclares=array();
	private $_expandoAttributes=array();

	private $_headScriptFiles=array();
	private $_headScripts=array();

	private $_styleSheetFiles=array();
	private $_styleSheets=array();

	private $_registeredPradoScripts=array();
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
	 * The script files will be published.
	 * @param string script library name. Valid names include
	 * 'prado', 'effects', 'ajax', 'validator', 'logger',
	 * 'datepicker', 'rico', 'colorpicker'.
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
				$this->publishFilePath($basePath.'/'.$script.'.js');
				$this->_registeredPradoFiles[$script]=true;
			}
			$scriptLoader=$basePath.'/'.self::SCRIPT_LOADER;
			$url=$this->publishFilePath($scriptLoader);
			$url.='?js='.implode(',',array_keys($this->_registeredPradoFiles));
			if($this->getApplication()->getMode()===TApplication::STATE_DEBUG)
				$url.='&__nocache';
			$this->registerScriptFile('prado:pradoscripts',$url);
		}
	}

	public function registerPostBackControl($control,$namespace='Prado.WebUI')
	{
		$options = $this->getPostBackOptions($control);
		$type = get_class($control);
		$namespace = empty($namespace) ? "window" : $namespace;
		$code = "new {$namespace}.{$type}($options);";
		$this->registerEndScript(sprintf('%08X', crc32($code)), $code);

		$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
		$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');
		$this->registerPradoScript('prado');
	}

	protected function getPostBackOptions($control)
	{
		$postback = $control->getPostBackOptions();
		if(!isset($postback['ID']))
			$postback['ID'] = $control->getClientID();
		if(!isset($postback['FormID']))
			$postback['FormID'] = $this->_page->getForm()->getClientID();
		return TJavaScript::encode($postback);
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

/*	protected function registerPostBackScript()
	{
		if(!$this->_postBackScriptRegistered)
		{
			$this->_postBackScriptRegistered=true;
			$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
			$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');
			$this->registerPradoScript('prado');
		}
	}

	public function registerFocusScript($target)
	{
		if(!$this->_focusScriptRegistered)
		{
			$this->_focusScriptRegistered=true;
			$this->registerPradoScript('prado');
			$this->registerEndScript('prado:focus','Prado.Focus.setFocus("'.THttpUtility::quoteJavaScriptString($target).'");');
		}
	}

	public function registerScrollScript($x,$y)
	{
		if(!$this->_scrollScriptRegistered)
		{
			$this->_scrollScriptRegistered=true;
			$this->registerHiddenField(TPage::FIELD_SCROLL_X,$x);
			$this->registerHiddenField(TPage::FIELD_SCROLL_Y,$y);
			// TBD, need scroll.js
		}
	}

	public function registerDefaultButtonScript($source, $target)
	{
		$this->registerPradoScript('prado');
		$button = $target->getClientID();
		$panel = $source->getClientID();
		return "Event.observe('{$panel}', 'keyup', Prado.Button.fireButton.bindEvent($('{$panel}'), '$button'));";
	}

	public function registerValidationScript()
	{
	}*/

	public function isHiddenFieldRegistered($key)
	{
		return isset($this->_hiddenFields[$key]);
	}

	public function isScriptRegistered($key)
	{
		return isset($this->_scripts[$key]);
	}

	public function isScriptFileRegistered($key)
	{
		return isset($this->_scriptFiles[$key]);
	}

	public function isBeginScriptRegistered($key)
	{
		return isset($this->_beginScripts[$key]);
	}

	public function isEndScriptRegistered($key)
	{
		return isset($this->_endScripts[$key]);
	}

/*	public function isHeadScriptFileRegistered($key)
	{
		return isset($this->_headScriptFiles[$key]);
	}

	public function isHeadScriptRegistered($key)
	{
		return isset($this->_headScripts[$key]);
	}
*/

	public function isStyleSheetFileRegistered($key)
	{
		return isset($this->_styleSheetFiles[$key]);
	}

	public function isStyleSheetRegistered($key)
	{
		return isset($this->_styleSheets[$key]);
	}

/*	public function isOnSubmitStatementRegistered($key)
	{
		return isset($this->_onSubmitStatements[$key]);
	}

	public function registerArrayDeclaration($name,$value)
	{
		$this->_arrayDeclares[$name][]=$value;
	}
*/
	public function registerScriptFile($key,$url)
	{
		$this->_scriptFiles[$key]=$url;
	}

	public function registerHiddenField($name,$value)
	{
		// if the named hidden field exists and has a value null, it means the hidden field is rendered already
		if(!isset($this->_hiddenFields[$name]) || $this->_hiddenFields[$name]!==null)
			$this->_hiddenFields[$name]=$value;
	}

/*	public function registerOnSubmitStatement($key,$script)
	{
		$this->_onSubmitStatements[$key]=$script;
	}
*/
	public function registerBeginScript($key,$script)
	{
		$this->_beginScripts[$key]=$script;
	}

	public function registerEndScript($key,$script)
	{
		$this->_endScripts[$key]=$script;
	}

/*	public function registerHeadScriptFile($key,$url)
	{
		$this->_headScriptFiles[$key]=$url;
	}

	public function registerHeadScript($key,$script)
	{
		$this->_headScripts[$key]=$script;
	}
*/
	public function registerStyleSheetFile($key,$url)
	{
		$this->_styleSheetFiles[$key]=$url;
	}

	public function registerStyleSheet($key,$css)
	{
		$this->_styleSheets[$key]=$css;
	}

/*	public function registerExpandoAttribute($controlID,$name,$value)
	{
		$this->_expandoAttributes[$controlID][$name]=$value;
	}

	public function renderArrayDeclarations($writer)
	{
		if(count($this->_arrayDeclares))
		{
			$str="<script type=\"text/javascript\">\n//<![CDATA[\n";
			foreach($this->_arrayDeclares as $name=>$array)
				$str.="var $name=new Array(".implode(',',$array).");\n";
			$str.="\n//]]>\n</script>\n";
			$writer->write($str);
		}
	}
*/
	public function renderScriptFiles($writer)
	{
		$str='';
		foreach($this->_scriptFiles as $include)
			$str.="<script type=\"text/javascript\" src=\"".THttpUtility::htmlEncode($include)."\"></script>\n";
		$writer->write($str);
	}

/*	public function renderOnSubmitStatements($writer)
	{
		// ???
	}
*/
	public function renderBeginScripts($writer)
	{
		if(count($this->_beginScripts))
			$writer->write("<script type=\"text/javascript\">\n//<![CDATA[\n".implode("\n",$this->_beginScripts)."\n//]]>\n</script>\n");
	}

	public function renderEndScripts($writer)
	{
		if(count($this->_endScripts))
			$writer->write("<script type=\"text/javascript\">\n//<![CDATA[\n".implode("\n",$this->_endScripts)."\n//]]>\n</script>\n");
	}

	public function renderHiddenFields($writer)
	{
		$str='';
		foreach($this->_hiddenFields as $name=>$value)
		{
			if($value!==null)
			{
				$value=THttpUtility::htmlEncode($value);
				$str.="<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />\n";
				// set hidden field value to null to indicate this field is rendered
				// Note, hidden field rendering is invoked twice (at the beginning and ending of TForm)
				$this->_hiddenFields[$name]=null;
			}
		}
		if($str!=='')
			$writer->write("<div>\n".$str."</div>\n");
	}

/*	public function renderExpandoAttributes($writer)
	{
		if(count($this->_expandoAttributes))
		{
			$str="<script type=\"text/javascript\">\n//<![CDATA[\n";
			foreach($this->_expandoAttributes as $controlID=>$attrs)
			{
				$str.="var $controlID = document.all ? document.all[\"$controlID\"] : document.getElementById(\"$controlID\");\n";
				foreach($attrs as $name=>$value)
				{
					if($value===null)
						$str.="{$key}[\"$name\"]=null;\n";
					else
						$str.="{$key}[\"$name\"]=\"$value\";\n";
				}
			}
			$str.="\n//]]>\n</script>\n";
			$writer->write($str);
		}
	}
*/

/*	public function renderHeadScriptFiles($writer)
	{
		$str='';
		foreach($this->_headScriptFiles as $url)
			$str.="<script type=\"text/javascript\" src=\"".THttpUtility::htmlEncode($url)."\"></script>\n";
		$writer->write($str);
	}

	public function renderHeadScripts($writer)
	{
		if(count($this->_headScripts))
			$writer->write("<script type=\"text/javascript\">\n//<![CDATA[\n".implode("\n",$this->_headScripts)."\n//]]>\n</script>\n");
	}
*/

	public function renderJavascriptBlock($code)
	{
		return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$code}\n/*]]>*/\n</script>";
	}

	public function renderStyleSheetFiles($writer)
	{
		$str='';
		foreach($this->_styleSheetFiles as $url)
		{
			$str.="<link rel=\"stylesheet\" type=\"text/css\" href=\"".THttpUtility::htmlEncode($url)."\" />\n";
		}
		$writer->write($str);
	}

	public function renderStyleSheets($writer)
	{
		if(count($this->_styleSheets))
			$writer->write("<style type=\"text/css\">\n".implode("\n",$this->_styleSheets)."\n</style>\n");
	}

	public function getHasHiddenFields()
	{
		return count($this->_hiddenFields)>0;
	}

/*	public function getHasSubmitStatements()
	{
		return count($this->_onSubmitStatements)>0;
	}
*/
/*	public function registerClientEvent($control, $event, $code)
	{
		if(empty($code)) return;
		$this->registerPradoScript("prado");
		$script= "Event.observe('{$control->ClientID}', '{$event}', function(e){ {$code} });";
		$key = "prado:{$control->ClientID}:{$event}";
		$this->registerEndScript($key, $script);
	}
*/
}

/**
 * PradoClientScript class.
 *
 * Resolves Prado client script dependencies. e.g. TPradoClientScript::getScripts("dom");
 *
 * - <b>base</b> basic javascript utilities, e.g. $()
 * - <b>dom</b> DOM and Form functions, e.g. $F(inputID) to retrive form input values.
 * - <b>effects</b> Effects such as fade, shake, move
 * - <b>controls</b> Prado client-side components, e.g. Slider, AJAX components
 * - <b>validator</b> Prado client-side validators.
 * - <b>ajax</b> Prado AJAX library including Prototype's AJAX and JSON.
 *
 * Dependencies for each library are automatically resolved.
 *
 * Namespace: System.Web.UI
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.1 $  $Date: 2005/11/06 23:02:33 $
 * @package System.Web.UI
 */
class TPradoClientScript
{
	/**
	 * Client-side javascript library dependencies
	 * @var array
	 */
	protected static $_dependencies = array(
		'prado' => array('prado'),
		'effects' => array('prado', 'effects'),
		'ajax' => array('prado', 'effects', 'ajax'),
		'validator' => array('prado', 'validator'),
		'logger' => array('prado', 'logger'),
		'datepicker' => array('prado', 'datepicker'),
		'rico' => array('prado', 'effects', 'ajax', 'rico'),
		'colorpicker' => array('prado', 'colorpicker')
		);

	/**
	 * Resolve dependencies for the given library name(s).
	 * @param string|array name(s) of the library to be loaded.
	 * @return array list of library file names (w/o extension) for the specified library name(s).
	 */
	public function getScripts($scripts)
	{
		$files = array();
		if(!is_array($scripts)) $scripts = array($scripts);
		foreach($scripts as $script)
		{
			if(isset(self::$_dependencies[$script]))
				$files = array_merge($files, self::$_dependencies[$script]);
			$files[] = $script;
		}
		$files = array_unique($files);
		return $files;
	}


	/**
	 * TODO: clean up
	 *
	public function getPostBackEventReference($control,$parameter='',$options=null,$javascriptPrefix=true)
	{
		if(!$options || (!$options->getPerformValidation() && !$options->getTrackFocus() && $options->getClientSubmit() && $options->getActionUrl()==''))
		{
			$this->registerPostBackScript();
			if(($form=$this->_page->getForm())!==null)
				$formID=$form->getClientID();
			else
				throw new TConfigurationException('clientscriptmanager_form_required');
			$postback=self::POSTBACK_FUNC.'(\''.$formID.'\',\''.$control->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($parameter).'\')';
			if($options && $options->getAutoPostBack())
				$postback='setTimeout(\''.THttpUtility::quoteJavaScriptString($postback).'\',0)';
			return $javascriptPrefix?'javascript:'.$postback:$postback;
		}
		$opt='';
		$flag=false;
		if($options->getPerformValidation())
		{
			$flag=true;
			$this->registerValidationScript();
			$opt.=',true,';
		}
		else
			$opt.=',false,';
		if($options->getValidationGroup()!=='')
		{
			$flag=true;
			$opt.='"'.$options->getValidationGroup().'",';
		}
		else
			$opt.='\'\',';
		if($options->getActionUrl()!=='')
		{
			$flag=true;
			$this->_page->setCrossPagePostBack(true);
			$opt.='"'.$options->getActionUrl().'",';
		}
		else
			$opt.='null,';
		if($options->getTrackFocus())
		{
			$flag=true;
			$this->registerFocusScript();
			$opt.='true,';
		}
		else
			$opt.='false,';
		if($options->getClientSubmit())
		{
			$flag=true;
			$opt.='true';
		}
		else
			$opt.='false';
		if(!$flag)
			return '';
		$this->registerPostBackScript();
		if(($form=$this->_page->getForm())!==null)
			$formID=$form->getClientID();
		else
			throw new TConfigurationException('clientscriptmanager_form_required');
		$postback=self::POSTBACK_FUNC.'(\''.$formID.'\',\''.$control->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($parameter).'\''.$opt.')';
		if($options && $options->getAutoPostBack())
			$postback='setTimeout(\''.THttpUtility::quoteJavaScriptString($postback).'\',0)';
		return $javascriptPrefix?'javascript:'.$postback:$postback;
	}*/

}

/*class TPostBackOptions extends TComponent
{
	public $_actionUrl='';
	public $_autoPostBack=false;
	public $_clientSubmit=true;
	public $_performValidation=false;
	public $_validationGroup='';
	public $_trackFocus=false;

	public function getActionUrl()
	{
		return $this->_actionUrl;
	}

	public function setActionUrl($value)
	{
		$this->_actionUrl=THttpUtility::quoteJavaScriptString($value);
	}

	public function getAutoPostBack()
	{
		return $this->_autoPostBack;
	}

	public function setAutoPostBack($value)
	{
		$this->_autoPostBack=$value;
	}

	public function getClientSubmit()
	{
		return $this->_clientSubmit;
	}

	public function setClientSubmit($value)
	{
		$this->_clientSubmit=$value;
	}

	public function getPerformValidation()
	{
		return $this->_performValidation;
	}

	public function setPerformValidation($value)
	{
		$this->_performValidation=$value;
	}

	public function getValidationGroup()
	{
		return $this->_validationGroup;
	}

	public function setValidationGroup($value)
	{
		$this->_validationGroup=$value;
	}

	public function getTrackFocus()
	{
		return $this->_trackFocus;
	}

	public function setTrackFocus($value)
	{
		$this->_trackFocus=$value;
	}
}
*/

?>