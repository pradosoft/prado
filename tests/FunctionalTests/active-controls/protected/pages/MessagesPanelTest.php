<?php

class MessagesPanelTest extends TPage
{
	public function show_clicked($sender, $param)
	{
		$this->panel1->setMessage("hello world");
	}

	public function hide_clicked($sender, $param)
	{
		$this->panel1->setMessage("");
	}
}
