<?php

prado::using ('System.Web.UI.ActiveControls.*');

class Ticket671 extends TPage
{
	public function validateSelection($sender, $param)
	{
		$param->setIsValid($param->getValue()==3);
	}
	
	public function selectItem ($sender, $param)
	{
		$this->lblResult->text="You have selected '".$sender->getSelectedItem()->getText()."'.";
		if (!$this->getIsValid()) $this->lblResult->text .= " But this is not valid !";
	}
	
	public function submit ($sender, $param)
	{
		$this->lblResult->text="You have successfully validated the form";
	}
	
	public function validateTextBox($sender,$param)	{
		$param->setIsValid(strtolower($param->getValue())=="prado");
	}

	public function submit2($sender,$param) {
		$this->lblResult2->text="Thanks !";
	}

	
}
?>