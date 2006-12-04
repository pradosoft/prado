<?php

class PageStateTest extends TPage
{
	function button1_oncallback($sender, $param)
	{
		//sleep(rand(0,5));
		$sender->CustomData = $sender->CustomData + 1;
		$this->label1->Text = " button1 clicked ".$sender->CustomData." times";
	}
}

?>