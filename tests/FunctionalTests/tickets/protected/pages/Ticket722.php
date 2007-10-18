<?php

prado::using ('System.Web.UI.ActiveControls.*');

class Ticket722 extends TPage
{
	public function changeState ($sender, $param)
	{
		$state=$this->InPlaceTextBox->getReadOnly();
		$this->InPlaceTextBox->setReadOnly(!$state);
		$sender->setText($state?"Change to Read Only":"Change to Editable");
		$this->InPlaceTextBox->setText($state?$this->getText():$this->getText().' [Read Only]');
	}
	
	public function onTextChanged ($sender, $param)
	{
		$this->setText($sender->getText());
	}
	
	public function setText ($value)
	{
		$this->setViewState('text', $value, "Editable Text");
	}
	
	public function getText ()
	{
		return $this->getViewState('text', "Editable Text");
	}
}
?>