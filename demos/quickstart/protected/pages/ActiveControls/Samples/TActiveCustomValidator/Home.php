<?php

class Home extends TPage
{
	public function validator1_onvalidate($sender, $param)
	{
		$param->IsValid = $this->textbox1->Text == 'Prado';
	}

	public function button1_oncallback($sender, $param)
	{
		if($this->IsValid)
			$this->label1->Text='Callback success';
		else
			$this->label1->Text='Validation failed';
	}
}

