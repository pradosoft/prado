<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket484 extends TPage
{

	function onLoad($param)
	{
		new TActiveButton;
		for ($i = 0; $i<1500; $i++)
		{
			$ctl = Prado::createComponent("TLabel");
			$ctl->Text = "Label ".$i;
			$this->Controls[] = $ctl;
		}
	}

	function button2_onclick($sender, $param)
	{
		$this->label1->Text = "Button 1 was clicked ";
	}

	function button2_oncallback($sender, $param)
	{
		$this->label1->Text .= "using callback!";
	}
}

?>