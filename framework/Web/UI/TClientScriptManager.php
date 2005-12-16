<?php

class TPostBackOptions extends TComponent
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

class TClientScriptManager extends TComponent
{
	const SCRIPT_DIR='Web/Javascripts/js';
	const POSTBACK_FUNC='Prado.doPostBack';
	private $_page;
	private $_hiddenFields=array();
	private $_beginScripts=array();
	private $_endScripts=array();
	private $_scriptFiles=array();
	private $_headScriptFiles=array();
	private $_headScripts=array();
	private $_styleSheetFiles=array();
	private $_styleSheets=array();
	private $_onSubmitStatements=array();
	private $_arrayDeclares=array();
	private $_expandoAttributes=array();
	private $_postBackScriptRegistered=false;
	private $_focusScriptRegistered=false;
	private $_scrollScriptRegistered=false;
	private $_publishedScriptFiles=array();

	public function __construct(TPage $owner)
	{
		$this->_page=$owner;
	}

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
	}

	public function registerPradoScript($script)
	{
		foreach(TPradoClientScript::getScripts($script) as $scriptFile)
		{
			if(isset($this->_publishedScriptFiles[$scriptFile]))
				$url=$this->_publishedScriptFiles[$scriptFile];
			else
			{
				$base = Prado::getFrameworkPath();
				$clientScripts = self::SCRIPT_DIR;
				$file = "{$base}/{$clientScripts}/{$scriptFile}.js";
				$assetManager = $this->_page->getService()->getAssetManager();
				$url= $assetManager->publishFilePath($file);
				$this->_publishedScriptFiles[$scriptFile]=$url;
				$this->registerScriptFile('prado:'.$scriptFile,$url);
			}
		}
		//return $url;
	}

	protected function registerPostBackScript()
	{
		if(!$this->_postBackScriptRegistered)
		{
			$this->_postBackScriptRegistered=true;
			$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
			$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');
			$this->registerPradoScript('base');
		}
	}

	public function registerFocusScript($target)
	{
		if(!$this->_focusScriptRegistered)
		{
			$this->_focusScriptRegistered=true;
			$this->registerPradoScript('base');
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

	public function registerDefaultButtonScript($button)
	{
		$this->registerPradoScript('base');
		return 'return Prado.Button.fireButton(event,\''.$button->getClientID().'\')';
	}

	public function registerValidationScript()
	{
	}

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

	public function isHeadScriptFileRegistered($key)
	{
		return isset($this->_headScriptFiles[$key]);
	}

	public function isHeadScriptRegistered($key)
	{
		return isset($this->_headScripts[$key]);
	}

	public function isStyleSheetFileRegistered($key)
	{
		return isset($this->_styleSheetFiles[$key]);
	}

	public function isStyleSheetRegistered($key)
	{
		return isset($this->_styleSheets[$key]);
	}

	public function isOnSubmitStatementRegistered($key)
	{
		return isset($this->_onSubmitStatements[$key]);
	}

	public function registerArrayDeclaration($name,$value)
	{
		$this->_arrayDeclares[$name][]=$value;
	}

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

	public function registerOnSubmitStatement($key,$script)
	{
		$this->_onSubmitStatements[$key]=$script;
	}

	public function registerBeginScript($key,$script)
	{
		$this->_beginScripts[$key]=$script;
	}

	public function registerEndScript($key,$script)
	{
		$this->_endScripts[$key]=$script;
	}

	public function registerHeadScriptFile($key,$url)
	{
		$this->_headScriptFiles[$key]=$url;
	}

	public function registerHeadScript($key,$script)
	{
		$this->_headScripts[$key]=$script;
	}

	public function registerStyleSheetFile($key,$url)
	{
		$this->_styleSheetFiles[$key]=$url;
	}

	public function registerStyleSheet($key,$css)
	{
		$this->_styleSheets[$key]=$css;
	}

	public function registerExpandoAttribute($controlID,$name,$value)
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

	public function renderScriptFiles($writer)
	{
		$str='';
		foreach($this->_scriptFiles as $include)
			$str.="<script type=\"text/javascript\" src=\"".THttpUtility::htmlEncode($include)."\"></script>\n";
		$writer->write($str);
	}

	public function renderOnSubmitStatements($writer)
	{
		// ???
	}

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

	public function renderExpandoAttributes($writer)
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

	public function renderHeadScriptFiles($writer)
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

	public function getHasSubmitStatements()
	{
		return count($this->_onSubmitStatements)>0;
	}

	public function registerClientEvent($control, $event, $code)
	{
		if(empty($code)) return;
		$this->registerPradoScript("dom");
		$script= "Event.observe('{$control->ClientID}', '{$event}', function(e){ {$code} });";
		$key = "prado:{$control->ClientID}:{$event}";
		$this->registerEndScript($key, $script);
	}



	/*
	private void EnsureEventValidationFieldLoaded();
	internal string GetEventValidationFieldValue();
	public string GetWebResourceUrl(Type type, string resourceName);
	public void RegisterClientScriptResource(Type type, string resourceName);
	internal void RegisterDefaultButtonScript(Control button, $writer, bool useAddAttribute);
	public function SaveEventValidationField();
	public void ValidateEvent(string uniqueId, string argument);
	public function getCallbackEventReference()
	*/
}

/**
 * TJavascript class file. Javascript utilties, converts basic PHP types into
 * appropriate javascript types.
 *
 * Example:
 * <code>
 * $options['onLoading'] = "doit";
 * $options['onComplete'] = "more";
 * $js = TJavascript::toList($options);
 * //expects the following javascript code
 * // {'onLoading':'doit','onComplete':'more'}
 * </code>
 *
 * Namespace: System.Web.UI
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.3 $  $Date: 2005/11/10 23:43:26 $
 * @package System.Web.UI
 */
class TJavascript
{
	/**
	 * Coverts PHP arrays (only the array values) into javascript array.
	 * @param array the array data to convert
	 * @param string append additional javascript array data
	 * @param boolean if true empty string and empty array will be converted
	 * @return string javascript array as string.
	 */
	public static function toArray($array,$append=null,$strict=false)
	{
		$results = array();
		$converter = new TJavascript();
		foreach($array as $v)
		{
			if($strict || (!$strict && $v !== '' && $v !== array()))
			{
				$type = 'to_'.gettype($v);
				if($type == 'to_array')
					$results[] = $converter->toArray($v, $append, $strict);
				else
					$results[] = $converter->{$type}($v);
			}
		}
		$extra = '';
		if(strlen($append) > 0)
			$extra .= count($results) > 0 ? ','.$append : $append;
		return '['.implode(',', $results).$extra.']';
	}

	/**
	 * Coverts PHP arrays (both key and value) into javascript objects.
	 * @param array the array data to convert
	 * @param string append additional javascript object data
	 * @param boolean if true empty string and empty array will be converted
	 * @return string javascript object as string.
	 */
	public static function toList($array,$append=null, $strict=false)
	{
		$results = array();
		$converter = new TJavascript();
		foreach($array as $k => $v)
		{
			if($strict || (!$strict && $v !== '' && $v !== array()))
			{
				$type = 'to_'.gettype($v);
				if($type == 'to_array')
					$results[] = "'{$k}':".$converter->toList($v, $append, $strict);
				else
					$results[] = "'{$k}':".$converter->{$type}($v);
			}
		}
		$extra = '';
		if(strlen($append) > 0)
			$extra .= count($results) > 0 ? ','.$append : $append;

		return '{'.implode(',', $results).$extra.'}';
	}

	public function to_boolean($v)
	{
		return $v ? 'true' : 'false';
	}

	public function to_integer($v)
	{
		return "{$v}";
	}

	public function to_double($v)
	{
		return "{$v}";
	}

	/**
	 * If string begins with [ and ends ], or begins with { and ends }
	 * it is assumed to be javascript arrays or objects and no further
	 * conversion is applied.
	 */
	public function to_string($v)
	{
		if(strlen($v)>1)
		{
			$first = $v{0}; $last = $v{strlen($v)-1};
			if($first == '[' && $last == ']' ||
				($first == '{' && $last == '}'))
				return $v;
		}
		return "'".addslashes($v)."'";
	}

	public function to_array($v)
	{
		return TJavascript::toArray($v);
	}

	public function to_null($v)
	{
		return 'null';
	}
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
	protected static $dependencies = array(
		'base' => array('base'),
		'dom' => array('base', 'dom'),
		'effects' => array('base', 'dom', 'effects'),
		'controls' => array('base', 'dom', 'effects', 'controls'),
		'validator' => array('base', 'dom', 'validator'),
		'logger' => array('base', 'dom', 'logger'),
		'ajax' => array('base', 'dom', 'ajax')
		);

	/**
	 * Resolve dependencies for the given library.
	 * @param array list of libraries to load.
	 * @return array list of libraries including its dependencies.
	 */
	public static function getScripts($scripts)
	{
		$files = array();
		if(!is_array($scripts)) $scripts = array($scripts);
		foreach($scripts as $script)
		{
			if(isset(self::$dependencies[$script]))
				$files = array_merge($files, self::$dependencies[$script]);
			$files[] = $script;
		}
		$files = array_unique($files);
		return $files;
	}
}

?>