<?php

class Ticket191 extends TPage
{
	public function buttonClicked($sender,$param)
	{
		if($this->IsValid)
			$this->Application->clearGlobalState('ticket190');
	}

	public function customValidation($sender,$param)
	{
		$param->IsValid=$this->Application->getGlobalState('ticket190')===$this->TextBox->Text;
	}

	public function updateGlobal($sender,$param)
	{
		$this->Application->setGlobalState('ticket190',$this->TextBox2->Text);
	}
}

?>