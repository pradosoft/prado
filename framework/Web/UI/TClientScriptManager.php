<?php

class TPostBackOptions extends TComponent
{
	public $ActionUrl;
	public $Argument;
	public $AutoPostBack;
	public $ClientSubmit;
	public $PerformValidation;
	public $TargetControl;
	public $TrackFocus;
	public $ValidationGroup;

	public function __construct($targetControl=null,
								$argument='',
								$actionUrl='',
								$autoPostBack=false,
								$trackFocus=false,
								$clientSubmit=true,
								$performValidation=false,
								$validationGroup='')
	{
		$this->ActionUrl=$actionUrl;
		$this->Argument=$argument;
		$this->AutoPostBack=$autoPostBack;
		$this->ClientSubmit=$clientSubmit;
		$this->PerformValidation=$performValidation;
		$this->TargetControl=$targetControl;
		$this->TrackFocus=$trackFocus;
		$this->ValidationGroup=$validationGroup;
	}
}

class TClientScriptManager extends TComponent
{
	const SCRIPT_DIR='Web/Javascripts/js';
	const POSTBACK_FUNC='Prado.PostBack.perform';
	const POSTBACK_OPTIONS='Prado.PostBack.Options';
	const FIELD_POSTBACK_TARGET='PRADO_POSTBACK_TARGET';
	const FIELD_POSTBACK_PARAMETER='PRADO_POSTBACK_PARAMETER';
	const FIELD_LASTFOCUS='PRADO_LASTFOCUS';
	const FIELD_PAGE_STATE='PRADO_PAGE_STATE';
	private $_owner;
	private $_hiddenFields=array();
	private $_scriptBlocks=array();
	private $_startupScripts=array();
	private $_scriptIncludes=array();
	private $_onSubmitStatements=array();
	private $_arrayDeclares=array();
	private $_expandoAttributes=array();

	public function __construct(TPage $owner)
	{
		$this->_owner=$owner;
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
				$this->_owner->setCrossPagePostBack(true);
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
			//if(!$flag)
			//	return '';
		}
		else
			$opt='null';
		$this->registerPostBackScript();
		$formID=$this->_owner->getForm()->getUniqueID();
		$postback=self::POSTBACK_FUNC.'(\''.$formID.'\',\''.$control->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($parameter).'\','.$opt.')';
		if($options && $options->AutoPostBack)
			$postback='setTimeout(\''.THttpUtility::quoteJavaScriptString($postback).'\',0)';
		return $javascriptPrefix?'javascript:'.$postback:$postback;
	}

	protected function registerPostBackScript()
	{
		$this->registerHiddenField(self::FIELD_POSTBACK_TARGET,'');
		$this->registerHiddenField(self::FIELD_POSTBACK_PARAMETER,'');
		$am=$this->_owner->getService()->getAssetManager();
		$url=$am->publishFilePath(Prado::getFrameworkPath().'/'.self::SCRIPT_DIR.'/base.js');
		$this->registerScriptInclude('prado:base',$url);
	}

	public function registerFocusScript()
	{
		// need Focus.js
	}

	public function registerScrollScript()
	{
		$this->registerHiddenField(self::FIELD_SCROLL_X,$this->_owner->getScrollPositionX());
		$this->registerHiddenField(self::FIELD_SCROLL_Y,$this->_owner->getScrollPositionY());
		/*
		this.ClientScript.RegisterStartupScript(typeof(Page), "PageScrollPositionScript", "\r\ntheForm.oldSubmit = theForm.submit;\r\ntheForm.submit = WebForm_SaveScrollPositionSubmit;\r\n\r\ntheForm.oldOnSubmit = theForm.onsubmit;\r\ntheForm.onsubmit = WebForm_SaveScrollPositionOnSubmit;\r\n" + (this.IsPostBack ? "\r\ntheForm.oldOnLoad = window.onload;\r\nwindow.onload = WebForm_RestoreScrollPosition;\r\n" : string.Empty), true);
		need base.js
		*/
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

	public function isStartupScriptRegistered($key)
	{
		return isset($this->_startupScripts[$key]);
	}

	public function isOnSubmitStatementRegistered($key)
	{
		return isset($this->_onSubmitStatements[$key]);
	}

	public function registerArrayDeclaration($name,$value)
	{
		$this->_arrayDeclares[$name][]=$value;
	}

	public function registerScriptBlock($key,$script)
	{
		$this->_scriptBlocks[$key]=$script;
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

	public function registerStartupScript($key,$script)
	{
		$this->_startupScripts[$key]=$script;
	}

	public function registerExpandoAttribute($controlID,$name,$value)
	{
		$this->_expandoAttributes[$controlID][$name]=$value;
	}

	public function renderArrayDeclarations($writer)
	{
		if(count($this->_arrayDeclares))
		{
			$str="\n<script type=\"text/javascript\">\n<!--\n";
			foreach($this->_arrayDeclares as $name=>$array)
			{
				$str.="var $name=new Array(";
				$flag=true;
				foreach($array as $value)
				{
					if($flag)
					{
						$flag=false;
						$str.=$value;
					}
					else
						$str.=','.$value;
				}
				$str.=");\n";
			}
			$str.="// -->\n</script>\n";
			$writer->write($str);
		}
	}

	public function renderScriptBlocks($writer)
	{
		$str='';
		foreach($this->_scriptBlocks as $script)
			$str.="\n".$script;
		if($this->_owner->getClientOnSubmitEvent()!=='' && $this->_owner->getClientSupportsJavaScript())
		{
			$str.="\nfunction WebForm_OnSubmit() {\n";
			foreach($this->_onSubmitStatements as $script)
				$str.=$script;
			$str.="\nreturn true;\n}";
		}
		if($str!=='')
			$writer->write("\n<script type=\"text/javascript\">\n<!--\n".$str."// -->\n</script>\n");
	}

	public function renderStartupScripts($writer)
	{
		$str='';
		foreach($this->_scriptIncludes as $include)
			$str.="\n<script type=\"text/javascript\" src=\"".THttpUtility::htmlEncode($include)."\"></script>";
		if(count($this->_startupScripts))
		{
			$str.="\n<script type=\"text/javascript\">\n<!--\n";
			foreach($this->_startupScripts as $script)
				$str.=$script;
			$str.="// -->\n</script>\n";
			$writer->write($str);
		}
	}

	public function renderHiddenFields($writer)
	{
		$str='';
		foreach($this->_hiddenFields as $name=>$value)
		{
			$value=THttpUtility::htmlEncode($value);
			$str.="\n<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />";
		}
		if($str!=='')
			$writer->write($str);
		$this->_hiddenFields=array();
	}

	public function renderExpandoAttribute($writer)
	{
		if(count($this->_expandoAttributes))
		{
			$str="\n<script type=\"text/javascript\">\n<!--\n";
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
			$str.="// -->\n</script>\n";
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