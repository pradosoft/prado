<?php

class Ticket484 extends TPage
{
	public function onLoad($param)
	{
		new TActiveButton;
		for ($i = 0; $i < 1500; $i++) {
			$ctl = Prado::createComponent("TLabel");
			$ctl->Text = "Label " . $i;
			$this->Controls[] = $ctl;
		}
	}

	public function button2_onclick($sender, $param)
	{
		$this->label1->Text = "Button 1 was clicked ";
	}

	public function button2_oncallback($sender, $param)
	{
		$this->label1->Text .= "using callback!";
	}
}
