<?php

class ActiveLinkButtonTest extends TPage
{
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