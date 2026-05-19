<?php

class Ticket578 extends TPage
{
	public function button2_onclick($sender, $param)
	{
		$this->label1->Text = "Button 1 was clicked : " . htmlspecialchars($this->text1->Text);
	}
}
