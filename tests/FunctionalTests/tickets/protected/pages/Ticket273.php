<?php

class Ticket273 extends TPage
{
	public function button_clicked($sender, $param)
	{
		$this->label1->Text = $sender->Text . ' Clicked!';
	}
}
