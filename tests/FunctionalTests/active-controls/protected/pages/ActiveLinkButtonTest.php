<?php

class ActiveLinkButtonTest extends TPage
{
	public function button2_onclick($sender, $param)
	{
		$this->label1->Text = "Button 1 was clicked ";
	}

	public function button2_oncallback($sender, $param)
	{
		$this->label1->Text .= "using callback!";
	}
}
