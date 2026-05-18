<?php

class Ticket205 extends TPage
{
	public function customValidate($sender, $param)
	{
		$param->IsValid = $this->textbox1->Text == "Prado";
	}
}
