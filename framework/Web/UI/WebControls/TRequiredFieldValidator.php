<?php

class TRequiredFieldValidator extends TBaseValidator
{
	public function getInitialValue()
	{
		$this->getViewState('InitialValue','');
	}

	public function setInitialValue($value)
	{
		$this->setViewState('InitialValue',TPropertyValue::ensureString($value),'');
	}

	protected function evaluateIsValid()
	{
		$value=$this->getValidationValue($this->getValidationTarget());
		return trim($value)!==trim($this->getInitialValue());
	}

	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['initialvalue']=$this->getInitialValue();
		return $options;
	}
}

?>