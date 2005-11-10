<?php

class TClientScriptManager extends TComponent
{
	private $_owner;
	private $_hiddenFields=array();
	private $_scriptBlocks=array();
	private $_startupScripts=array();
	private $_scriptIncludes=array();
	private $_onSubmitStatements=array();
	private $_arrayDeclares=array();

	public function __construct(TPage $owner)
	{
		$this->_owner=$owner;
	}

	final public function getPostBackEventReference($options)
	{
		if($options->RequiresJavaScriptProtocol)
			$str='javascript:';
		else
			$str='';
		if($options->AutoPostBack)
			$str.="setTimeout('";
		if(!$options->PerformValidation && !$options->TrackFocus && $options->ClientSubmit && $options->ActionUrl==='')
		{
			$this->_owner->registerPostBackScript();
			$postback="__doPostBack('".$options->TargetControl->getUniqueID()."','".THttpUtility::quoteJavaScriptString($options->Argument)."')";
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

	final public function isHiddenFieldRegistered($key)
	{
		return isset($this->_hiddenFields[$key]);
	}

	final public function isClientScriptBlockRegistered($key)
	{
		return isset($this->_scriptBlocks[$key]);
	}

	final public function isClientScriptIncludeRegistered($key)
	{
		return isset($this->_scriptIncludes[$key]);
	}

	final public function isStartupScriptRegistered($key)
	{
		return isset($this->_startupScripts[$key]);
	}

	final public function isOnSubmitStatementRegistered($key)
	{
		return isset($this->_onSubmitStatements[$key]);
	}

	final public function registerArrayDeclaration($name,$value)
	{
		$this->_arrayDeclares[$name][]=$value;
	}

	final public function registerClientScriptBlock($key,$script)
	{
		$this->_criptBlocks[$key]=$script;
	}

	final public function registerClientScriptInclude($key,$url)
	{
		$this->_scriptIncludes[$key]=$url;
	}

	// todo: register an asset

	final public function registerHiddenField($name,$value)
	{
		$this->_hiddenFields[$name]=$value;
	}

	final public function registerOnSubmitStatement($key,$script)
	{
		$this->_onSubmitStatements[$key]=$script;
	}

	final public function registerStartupScript($key,$script)
	{
		$this->_startupScripts[$key]=$script;
	}

	final public function renderArrayDeclarations($writer)
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

	final public function renderClientScriptBlocks($writer)
	{
		$str='';
		foreach($this->_scriptBlocks as $script)
			$str.=$script;
		if($this->_owner->getClientOnSubmitEvent()!=='' && $this->_owner->getClientSupportsJavaScript())
		{
			$str.="function WebForm_OnSubmit() {\n";
			foreach($this->_onSubmitStatements as $script)
				$str.=$script;
			$str.="\nreturn true;\n}";
		}
		if($str!=='')
			$writer->write("\n<script type=\"text/javascript\">\n<!--\n".$str."// -->\n</script>\n");
	}

	final public function renderClientStartupScripts($writer)
	{
		if(count($this->_startupScripts))
		{
			$str="\n<script type=\"text/javascript\">\n<!--\n";
			foreach($this->_startupScripts as $script)
				$str.=$script;
			$str.="// -->\n</script>\n";
			$writer->write($str);
		}
	}

	final public function renderHiddenFields($writer)
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

	/**
	 * @internal
	 */
	final public function getHasHiddenFields()
	{
		return count($this->_hiddenFields)>0;
	}

	/**
	 * @internal
	 */
	final public function getHasSubmitStatements()
	{
		return count($this->_onSubmitStatements)>0;
	}
}

?>