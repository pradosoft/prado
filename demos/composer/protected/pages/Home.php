<?php

class Home extends TPage
{
	private $_classDefinition=null;

	public function getClassDefinition()
	{
		if(!$this->_classDefinition)
			$this->_classDefinition=new ClassDefinition;
		return $this->_classDefinition;
	}

	public function onInit($param)
	{
		parent::onInit($param);
		if(!$this->IsPostBack)
		{
			$properties=$this->ClassDefinition->Properties;
			$properties[]=new PropertyDefinition;
			$properties[]=new PropertyDefinition;
			$properties[]=new PropertyDefinition;
			$this->PropertyList->DataSource=$properties;
			$this->dataBind();
		}
	}

	protected function refresh()
	{
		$this->PropertyList->DataSource=$this->ClassDefinition->Properties;
		$this->EventList->DataSource=$this->ClassDefinition->Events;
		$this->dataBind();
	}

	public function propertyAction($sender,$param)
	{
		if($param->CommandName==='remove')
		{
			$this->ClassDefinition->Properties->removeAt($param->CommandParameter);
		}
		else if($param->CommandName==='up')
		{
			$property=$this->ClassDefinition->Properties->itemAt($param->CommandParameter);
			$this->ClassDefinition->Properties->removeAt($param->CommandParameter);
			$this->ClassDefinition->Properties->insert($param->CommandParameter-1,$property);
		}
		else if($param->CommandName==='down')
		{
			$property=$this->ClassDefinition->Properties->itemAt($param->CommandParameter);
			$this->ClassDefinition->Properties->removeAt($param->CommandParameter);
			$this->ClassDefinition->Properties->insert($param->CommandParameter+1,$property);
		}
		$this->refresh();
	}

	public function eventAction($sender,$param)
	{
		if($param->CommandName==='remove')
		{
			$this->ClassDefinition->Events->removeAt($param->CommandParameter);
		}
		else if($param->CommandName==='up')
		{
			$property=$this->ClassDefinition->Events->itemAt($param->CommandParameter);
			$this->ClassDefinition->Events->removeAt($param->CommandParameter);
			$this->ClassDefinition->Events->insert($param->CommandParameter-1,$property);
		}
		else if($param->CommandName==='down')
		{
			$property=$this->ClassDefinition->Events->itemAt($param->CommandParameter);
			$this->ClassDefinition->Events->removeAt($param->CommandParameter);
			$this->ClassDefinition->Events->insert($param->CommandParameter+1,$property);
		}
		$this->refresh();
	}

	public function addProperty($sender,$param)
	{
		$this->ClassDefinition->Properties->add(new PropertyDefinition);
		$this->refresh();
	}

	public function addEvent($sender,$param)
	{
		$this->ClassDefinition->Events->add(new EventDefinition);
		$this->refresh();
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		//if($this->IsPostBack && $this->IsValid)
		if($this->IsPostBack)
		{
			$def=$this->ClassDefinition;
			$def->reset();
			$def->ClassName=$this->ClassName->Text;
			$def->ParentClass=$this->ParentClass->Text;
			$def->Interfaces=$this->Interfaces->Text;
			$def->Comments=$this->Comments->Text;
			$def->Author=$this->AuthorName->Text;
			$def->Email=$this->AuthorEmail->Text;
			foreach($this->PropertyList->Items as $item)
			{
				$property=new PropertyDefinition;
				$property->Name=$item->PropertyName->Text;
				$property->Type=$item->PropertyType->Text;
				$property->DefaultValue=$item->DefaultValue->Text;
				$property->ReadOnly=$item->ReadOnly->Checked;
				$property->IsProtected=$item->IsProtected->Checked;
				$property->Comments=$item->Comments->Text;
				$property->Storage=$item->Storage->Text;
				$def->Properties[]=$property;
			}
			foreach($this->EventList->Items as $item)
			{
				$event=new EventDefinition;
				$event->Name=$item->EventName->Text;
				$event->Comments=$item->Comments->Text;
				$def->Events[]=$event;
			}
		}
	}

	public function generateCode($sender,$param)
	{
		$this->refresh();
		$writer=Prado::createComponent('System.IO.TTextWriter');
		$this->ClassDefinition->render($writer);
		$this->SourceCode->Text=$writer->flush();
	}
}

class ClassDefinition extends TComponent
{
	private $_className='ClassName';
	private $_parentClass='TWebControl';
	private $_interfaces='';
	private $_properties=null;
	private $_events=null;
	private $_email='';
	private $_author='';
	private $_comments='';

	public function reset()
	{
		$this->_className='ClassName';
		$this->_parentClass='TWebControl';
		$this->_interfaces='';
		$this->_properties=new TList;
		$this->_events=new TList;
		$this->_email='';
		$this->_author='';
		$this->_comments='';
	}

	public function render($writer)
	{
		$this->renderComments($writer);
		$this->renderClass($writer);
	}

	protected function renderComments($writer)
	{
		$str ="/**\n";
		$str.=" * Class {$this->ClassName}.\n";
		if($this->Comments!=='')
		{
			$str.=" *\n";
			$str.=implode("\n * ",explode("\n",wordwrap($this->Comments)));
			$str.=" *\n\n";
		}
		if($this->Author!=='')
		{
			$str.=" * @author {$this->Author}";
			if($this->Email!=='')
				$str.=" <{$this->Email}>";
			$str.="\n";
		}
		$str.=" * @version \$Revision: \$  \$Date: \$\n";
		$str.=" */\n";
		$writer->write($str);
	}

	protected function renderClass($writer)
	{
		$writer->write("class {$this->ClassName}");
		if($this->ParentClass!=='')
			$writer->write(" extends {$this->ParentClass}");
		if($this->Interfaces!=='')
			$writer->write(" implements {$this->Interfaces}");
		$writer->write("\n{\n");
		$this->renderVariables($writer);
		$this->renderProperties($writer);
		$this->renderEvents($writer);
		$writer->write("}\n");
	}

	private function getVariableName($propertyName)
	{
		return '_'.strtolower($propertyName[0]).substr($propertyName,1);
	}

	protected function renderVariables($writer)
	{
		foreach($this->Properties as $property)
		{
			if($property->Storage==='Memory')
			{
				$name=$this->getVariableName($property->Name);
				$value=$this->getValueAsString($property->DefaultValue,$property->Type);
				$writer->write("\t/**\n\t * @var {$property->Type} {$property->Comments}\n\t */\n");
				$writer->write("\tprivate \$$name=$value;\n");
			}
		}
	}

	private function getValueAsString($value,$type)
	{
		switch($type)
		{
			case 'integer':
				$value=TPropertyValue::ensureInteger($value);
				break;
			case 'float':
				$value=TPropertyValue::ensureFloat($value);
				break;
			case 'boolean':
				if(TPropertyValue::ensureBoolean($value))
					$value='true';
				else
					$value='false';
				break;
			case 'enumerable':
				$value="'$value'";
				break;
			case 'mixed':
				$value='null';
				break;
			case 'string':
				$value="'$value'";
				break;
		}
		return "$value";
	}

	private function getValueConversionString($type)
	{
		switch($type)
		{
			case 'integer': return 'TPropertyValue::ensureInteger($value)';
			case 'float': return 'TPropertyValue::ensureFloat($value)';
			case 'boolean': return 'TPropertyValue::ensureBoolean($value)';
			case 'enumerable': return 'TPropertyValue::ensureEnum($value)';
			case 'mixed': return '$value';
			case 'string': return 'TPropertyValue::ensureString($value)';
		}
	}

	protected function renderProperties($writer)
	{
		foreach($this->Properties as $property)
		{
			$name=$property->Name;
			if($name==='')
				continue;
			$comments=implode("\n\t * ",explode("\n",wordwrap($property->Comments)));
			$access=$property->IsProtected?'protected':'public';
			$setter='set'.$property->Name.'($value)';
			$getter='get'.$property->Name.'()';
			$value=$this->getValueAsString($property->DefaultValue,$property->Type);
			if($property->Storage==='ViewState')
			{
				$readStatement="return \$this->getViewState('$name',$value);";
				$writeStatement="\$this->setViewState('$name',".$this->getValueConversionString($property->Type).",$value);";
			}
			else if($property->Storage==='ControlState')
			{
				$readStatement="return \$this->getControlState('$name',$value);";
				$writeStatement="\$this->setControlState('$name',".$this->getValueConversionString($property->Type).",$value);";
			}
			else
			{
				$varname=$this->getVariableName($property->Name);
				$readStatement="return \$this->$varname;";
				$writeStatement="\$this->$varname=".$this->getValueConversionString($property->Type).";";
			}
			$writer->write("\n\t/**\n\t * @return {$property->Type} $comments Defaults to $value.\n\t */\n");
			$writer->write("\t$access function $getter\n\t{\n\t\t$readStatement\n\t}\n");
			if(!$property->ReadOnly)
			{
				$writer->write("\n\t/**\n\t * @param {$property->Type} $comments\n\t */\n");
				$writer->write("\t$access function $setter\n\t{\n\t\t$writeStatement\n\t}\n");
			}
		}
	}

	protected function renderEvents($writer)
	{
		foreach($this->Events as $event)
		{
			$name=$event->Name;
			if($name==='')
				continue;
			$comments=implode("\n\t * ",explode("\n",wordwrap($event->Comments)));
			$writer->write("\n\t/**\n\t * Raises <b>$name</b> event.\n\t * $comments\n\t * @param TEventParameter event parameter\n\t */\n");
			$writer->write("\tpublic function on$name(\$param)\n\t{\n\t\t\$this->raiseEvent('$name',\$this,\$param);\n\t}\n");
		}
	}

	public function getClassName()
	{
		return $this->_className;
	}

	public function setClassName($value)
	{
		$this->_className=trim($value);
	}

	public function getParentClass()
	{
		return $this->_parentClass;
	}

	public function setParentClass($value)
	{
		$this->_parentClass=trim($value);
	}

	public function getInterfaces()
	{
		return $this->_interfaces;
	}

	public function setInterfaces($value)
	{
		$this->_interfaces=$value;
	}

	public function getProperties()
	{
		if(!$this->_properties)
			$this->_properties=new TList;
		return $this->_properties;
	}

	public function getEvents()
	{
		if(!$this->_events)
			$this->_events=new TList;
		return $this->_events;
	}

	public function getComments()
	{
		return $this->_comments;
	}

	public function setComments($value)
	{
		$this->_comments=$value;
	}

	public function getAuthor()
	{
		return $this->_author;
	}

	public function setAuthor($value)
	{
		$this->_author=trim($value);
	}

	public function getEmail()
	{
		return $this->_email;
	}

	public function setEmail($value)
	{
		$this->_email=trim($value);
	}
}

class EventDefinition extends TComponent
{
	private $_name='';
	private $_comments='';

	public function getName()
	{
		return $this->_name;
	}

	public function setName($value)
	{
		$this->_name=ucfirst(trim($value));
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
		$this->_name=ucfirst(trim($value));
	}

	public function getType()
	{
		return $this->_type;
	}

	public function setType($value)
	{
		$this->_type=trim($value);
	}

	public function getDefaultValue()
	{
		return $this->_default;
	}

	public function setDefaultValue($value)
	{
		$this->_default=trim($value);
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
		$this->_storage=trim($value);
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