<?php

class TInPlaceTextBoxTest extends TPage
{
	function load_text($sender, $param)
	{
		$sender->Text = "muahaha";
	}

	function label1_changed($sender, $param)
	{
		$this->status->Text = "Status: ". $sender->Text;
	}
}

?>