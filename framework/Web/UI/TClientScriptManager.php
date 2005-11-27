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
	const POSTBACK_FUNC='__doPostBack';
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

	public function getPostBackEventReference($options,$javascriptPrefix=true)
	{
		$str=$javascriptPrefix?'javascript:':'';
		if($options->AutoPostBack)
			$str.="setTimeout('";
		if(!$options->PerformValidation && !$options->TrackFocus && $options->ClientSubmit && $options->ActionUrl==='')
		{
			$this->registerPostBackScript();
			$postback=self::POSTBACK_FUNC.'(\''.$options->TargetControl->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($options->Argument).'\')';
			if($options->AutoPostBack)
			{
				$str.=THttpUtility::quoteJavaScriptString($postback);
				$str.="',0)";
			}
			else
				$str.=$postback;
			return $str;
		}
		$str.='WebForm_DoPostBackWithOptions(new WebForm_PostBackOptions("';
		$str.=$options->TargetControl->getUniqueID().'", ';
		if(($arg=$options->Argument)==='')
			$str.='"", ';
		else
			$str.='"'.THttpUtility::quoteJavaScriptString($arg).'", ';
		$flag=false;
		if($options->PerformValidation)
		{
			$flag=true;
			$str.='true, ';
		}
		else
			$str.='false, ';
		if($options->ValidationGroup!=='')
		{
			$flag=true;
			$str.='"'.$options->ValidationGroup.'", ';
		}
		else
			$str.='"", ';
		if($options->ActionUrl!=='')
		{
			$flag=true;
			$this->_owner->setContainsCrossPagePost(true);
			$str.='"'.THttpUtility::quoteJavaScriptString($options->ActionUrl).'", ';
		}
		else
			$str.='"", ';
		if($options->TrackFocus)
		{
			$this->_owner->registerFocusScript();
			$flag=true;
			$str.='true, ';
		}
		else
			$str.='false, ';
		if($options->ClientSubmit)
		{
			$flag=true;
			$this->_owner->registerPostBackScript();
			$str.='true))';
		}
		else
			$str.='false))';
		if($options->AutoPostBack)
			$str.="', 0)";
		if($flag)
		{
			$this->_owner->registerWebFormsScript();
			return $str;
		}
		else
			return '';
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
		if(count($this->_startupScripts))
		{
			$str="\n<script type=\"text/javascript\">\n<!--\n";
			foreach($this->_startupScripts as $script)
				$str.="\n".$script;
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

	public function registerPostBackScript()
	{
		$this->registerHiddenField('__EVENTTARGET','');
		$this->registerHiddenField('__EVENTPARAM','');
		$id=$this->_owner->getForm()->getUniqueID();
		$script=<<<EOD
function __doPostBack(eventTarget, eventParameter) {
	var validation = typeof(Prado) != 'undefined' && typeof(Prado.Validation) != 'undefined';
	var theform = document.getElementById ? document.getElementById('$id') : document.forms['$id'];
	theform.__EVENTTARGET.value = eventTarget.split('\$').join(':');
	theform.__EVENTPARAMETER.value = eventParameter;
	if(!validation || Prado.Validation.OnSubmit(theform))
	   theform.submit();
}
EOD;
		$this->registerStartupScript('form',$script);
	}
}

?>