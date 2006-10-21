<?php

class PageStateTest extends TPage
{
	function button1_oncallback($sender, $param)
	{
		$this->label1->Text .= " button1 clicked ";
	}
}

?>