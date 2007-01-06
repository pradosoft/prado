<?php

class MessagesPanelTest extends TPage
{
	function show_clicked($sender, $param)
	{
		$this->panel1->setMessage("hello world");
	}

	function hide_clicked($sender, $param)
	{
		$this->panel1->setMessage("");
	}
}

?>