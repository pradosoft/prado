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

Prado::using('System.Web.Javascripts.*');

class TClientScriptManager extends TComponent
{
	const SCRIPT_DIR='Web/Javascripts/js';
	//const POSTBACK_FUNC='Prado.doPostBack';
	
	private $_page;
	private $_hiddenFields=array();
	private $_beginScripts=array();
	private $_endScripts=array();
	private $_scriptFiles=array();
	
	//private $_headScriptFiles=array();
	//private $_headScripts=array();
	
	private $_styleSheetFiles=array();
	private $_styleSheets=array();
	
	private $_client;
	
	/*private $_onSubmitStatements=array();
	private $_arrayDeclares=array();
	private $_expandoAttributes=array();
	private $_postBackScriptRegistered=false;
	private $_focusScriptRegistered=false;
	private $_scrollScriptRegistered=false;
	*/
	
	private $_publishedScriptFiles=array();

	
	public function __construct(TPage $owner)
	{
		$this->_page=$owner;
		$this->_client = new TClientScript($this);
	}
	
	
	public function registerPostBackControl($control,$namespace='Prado.WebUI')
	{
		$options = $this->getPostBackOptions($control);
		$type = get_class($control);
		$code = "new {$namespace}.{$type}($options);";
		$this->registerEndScript(sprintf('%08X', crc32($code)), $code);
		
		$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
		$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');
		$this->registerClientScript('prado');
	}

	protected function getPostBackOptions($control)
	{
		$postback = $control->getPostBackOptions();
		if(!isset($postback['ID'])) 
			$postback['ID'] = $control->getClientID();
		if(!isset($postback['FormID']))
			$postback['FormID'] = $this->_page->getForm()->getClientID();
		$options = new TJavascriptSerializer($postback);
		return $options->toJavascript();
	}


	/**
	 * Register client scripts. 
	 */
	public function registerClientScript($script)
	{
		static $scripts = array();
		$scripts = array_unique(array_merge($scripts, 
						TClientScript::getScripts($script)));

		$this->publishClientScriptAssets($scripts);

		//create the client script url
		$url = $this->publishClientScriptCompressorAsset();
		$url .= '?js='.implode(',', $scripts);
		if(Prado::getApplication()->getMode() == TApplication::STATE_DEBUG)
			$url .= '&__nocache';
		$this->registerScriptFile('prado:gzipscripts', $url);
	}

	/**
	 * Publish each individual javascript file.
	 */
	protected function publishClientScriptAssets($scripts)
	{
		foreach($scripts as $lib)
		{
			if(!isset($this->_publishedScriptFiles[$lib]))
			{
				$base = Prado::getFrameworkPath();
				$clientScripts = self::SCRIPT_DIR;
				$assetManager = $this->_page->getService()->getAssetManager();
				$file = "{$base}/{$clientScripts}/{$lib}.js";
				$assetManager->publishFilePath($file);
				$this->_publishedScriptFiles[$lib] = true;
			}
		}
	}

	/**
	 * @return string URL of the compressor asset script.
	 */
	protected function publishClientScriptCompressorAsset()
	{
		$scriptFile = 'clientscripts.php';
		if(isset($this->_publishedScriptFiles[$scriptFile]))
			return $this->_publishedScriptFiles[$scriptFile];
		else
		{
			$base = Prado::getFrameworkPath();
			$clientScripts = self::SCRIPT_DIR;
			$assetManager = $this->_page->getService()->getAssetManager();
			$file = "{$base}/{$clientScripts}/{$scriptFile}";
			$url= $assetManager->publishFilePath($file);
			$this->_publishedScriptFiles[$scriptFile] = $url;
			return $url;
		}
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

?>