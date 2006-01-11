<?php

class Home extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$this->Repeater->setDataSource($this->getInitialProperties());
			$this->Repeater->dataBind();
		}
		else
			$this->Repeater->ensureChildControls();
	}

	protected function getInitialProperties()
	{
		return array(
			new PropertyDefinition,
			new PropertyDefinition,
			new PropertyDefinition,
			new PropertyDefinition,
		);
	}

	public function generateCode($sender,$param)
	{
		$code="<?php\n\n";
		$code.="class ".$this->ClassName->Text." extends ".$this->ParentClass->Text."implements ".$this->Interfaces->Text;
		$code.="\n";
		$code.="{\n";
		$code.="}\n";
		$code.="?>";
		$this->SourceCode->Text=htmlentities($code);
	}
}

class PropertyDefinition extends TComponent
{
	private $_name='';
	private $_type='string';
	private $_default='';
	private $_readOnly=false;
	private $_protected=false;
	private $_storage='ViewState';
	private $_comments='';

	public function getName()
	{
		return $this->_name;
	}

	public function setName($value)
	{
		$this->_name=$value;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function setType($value)
	{
		$this->_type=$value;
	}

	public function getDefaultValue()
	{
		return $this->_default;
	}

	public function setDefaultValue($value)
	{
		$this->_default=$value;
	}

	public function getReadOnly()
	{
		return $this->_readOnly;
	}

	public function setReadOnly($value)
	{
		$this->_readOnly=TPropertyValue::ensureBoolean($value);
	}

	public function getIsProtected()
	{
		return $this->_protected;
	}

	public function setIsProtected($value)
	{
		$this->_protected=TPropertyValue::ensureBoolean($value);
	}

	public function getStorage()
	{
		return $this->_storage;
	}

	public function setStorage($value)
	{
		$this->_storage=$value;
	}

	public function getComments()
	{
		return $this->_comments;
	}

	public function setComments($value)
	{
		$this->_comments=$value;
	}
}

?>