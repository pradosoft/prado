<?php

class TPostBackOptions extends TComponent
{
	public $ActionUrl;
	public $AutoPostBack;
	public $ClientSubmit;
	public $PerformValidation;
	public $TrackFocus;
	public $ValidationGroup;

	public function __construct($actionUrl='',$autoPostBack=false,$clientSubmit=true,
			$performValidation=false,$validationGroup='',$trackFocus=false)
	{
		$this->ActionUrl=$actionUrl;
		$this->AutoPostBack=$autoPostBack;
		$this->ClientSubmit=$clientSubmit;
		$this->PerformValidation=$performValidation;
		$this->ValidationGroup=$validationGroup;
		$this->TrackFocus=$trackFocus;
	}
}

class TClientScriptManager extends TComponent
{
	const SCRIPT_DIR='Web/Javascripts/js';
	const POSTBACK_FUNC='Prado.PostBack.perform';
	const POSTBACK_OPTIONS='Prado.PostBack.Options';
	private $_page;
	private $_hiddenFields=array();
	private $_beginScripts=array();
	private $_endScripts=array();
	private $_scriptIncludes=array();
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
		if($options)
		{
			$flag=false;
			$opt='new '.self::POSTBACK_OPTIONS.'(';
			if($options->PerformValidation)
			{
				$flag=true;
				$this->registerValidationScript();
				$opt.='true,';
			}
			else
				$opt.='false,';
			if($options->ValidationGroup!=='')
			{
				$flag=true;
				$opt.='"'.$options->ValidationGroup.'",';
			}
			else
				$opt.='"",';
			if($options->ActionUrl!=='')
			{
				$flag=true;
				$this->_page->setCrossPagePostBack(true);
				$opt.='"'.THttpUtility::quoteJavaScriptString($options->ActionUrl).'",';
			}
			else
				$opt.='"",';
			if($options->TrackFocus)
			{
				$flag=true;
				$this->registerFocusScript();
				$opt.='true,';
			}
			else
				$opt.='false,';
			if($options->ClientSubmit)
			{
				$flag=true;
				$opt.='true)';
			}
			else
				$opt.='false)';
			if(!$flag)
				return '';
		}
		else
			$opt='null';
		$this->registerPostBackScript();
		$formID=$this->_page->getForm()->getUniqueID();
		$postback=self::POSTBACK_FUNC.'(\''.$formID.'\',\''.$control->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($parameter).'\','.$opt.')';
		if($options && $options->AutoPostBack)
			$postback='setTimeout(\''.THttpUtility::quoteJavaScriptString($postback).'\',0)';
		return $javascriptPrefix?'javascript:'.$postback:$postback;
	}

	protected function registerPostBackScript()
	{
		if(!$this->_postBackScriptRegistered)
		{
			$this->_postBackScriptRegistered=true;
			$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
			$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');
			$this->registerScriptInclude('prado:base',$this->publishScriptFile('base.js'));
		}
	}

	private function publishScriptFile($jsFile)
	{
		if(!isset($this->_publishedScriptFiles[$jsFile]))
		{
			$am=$this->_page->getService()->getAssetManager();
			$this->_publishedScriptFiles[$jsFile]=$am->publishFilePath(Prado::getFrameworkPath().'/'.self::SCRIPT_DIR.'/'.$jsFile);
		}
		return $this->_publishedScriptFiles[$jsFile];
	}

	public function registerFocusScript($target)
	{
		if(!$this->_focusScriptRegistered)
		{
			$this->_focusScriptRegistered=true;
			$this->registerScriptInclude('prado:base',$this->publishScriptFile('base.js'));
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

	public function registerValidationScript()
	{
	}

	public function isHiddenFieldRegistered($key)
	{
		return isset($this->_hiddenFields[$key]);
	}

	public function isScriptBlockRegistered($key)
	{
		return isset($this->_scriptBlocks[$key]);
	}

	public function isScriptIncludeRegistered($key)
	{
		return isset($this->_scriptIncludes[$key]);
	}

	public function isBeginScriptRegistered($key)
	{
		return isset($this->_beginScripts[$key]);
	}

	public function isEndScriptRegistered($key)
	{
		return isset($this->_endScripts[$key]);
	}

	public function isOnSubmitStatementRegistered($key)
	{
		return isset($this->_onSubmitStatements[$key]);
	}

	public function registerArrayDeclaration($name,$value)
	{
		$this->_arrayDeclares[$name][]=$value;
	}

	public function registerScriptInclude($key,$url)
	{
		$this->_scriptIncludes[$key]=$url;
	}

	public function registerHiddenField($name,$value)
	{
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

	public function registerExpandoAttribute($controlID,$name,$value)
	{
		$this->_expandoAttributes[$controlID][$name]=$value;
	}

	public function renderArrayDeclarations($writer)
	{
		if(count($this->_arrayDeclares))
		{
			$str="<script type=\"text/javascript\"><!--\n";
			foreach($this->_arrayDeclares as $name=>$array)
				$str.="var $name=new Array(".implode(',',$array).");\n";
			$str.="\n// --></script>\n";
			$writer->write($str);
		}
	}

	public function renderScriptIncludes($writer)
	{
		foreach($this->_scriptIncludes as $include)
			$writer->write("<script type=\"text/javascript\" src=\"".THttpUtility::htmlEncode($include)."\"></script>\n");
	}

	public function renderOnSubmitStatements($writer)
	{
		// ???
	}

	public function renderBeginScripts($writer)
	{
		if(count($this->_beginScripts))
			$writer->write("<script type=\"text/javascript\"><!--\n".implode("\n",$this->_beginScripts)."\n// --></script>\n");
	}

	public function renderEndScripts($writer)
	{
		if(count($this->_endScripts))
			$writer->write("<script type=\"text/javascript\"><!--\n".implode("\n",$this->_endScripts)."\n// --></script>\n");
	}

	public function renderHiddenFields($writer)
	{
		$str='';
		foreach($this->_hiddenFields as $name=>$value)
		{
			$value=THttpUtility::htmlEncode($value);
			$str.="<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />\n";
		}
		if($str!=='')
			$writer->write($str);
	}

	public function renderExpandoAttributes($writer)
	{
		if(count($this->_expandoAttributes))
		{
			$str="<script type=\"text/javascript\"><!--\n";
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
			$str.="\n// --></script>\n";
			$writer->write($str);
		}
	}

	public function getHasHiddenFields()
	{
		return count($this->_hiddenFields)>0;
	}

	public function getHasSubmitStatements()
	{
		return count($this->_onSubmitStatements)>0;
	}


	/*
	internal void RenderWebFormsScript(HtmlTextWriter writer)
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