<?php

class EventTriggeredCallback extends TPage
{
	public function text1_focused($sender, $param)
	{
		$this->label1->Text = 'text 1 focused';
	}

	public function panel1_onmouseover($sender, $param)
	{
		$this->label1->Text = 'panel 1 on mouse over ' . time();
	}

	public function button1_clicked($sender, $param)
	{
		$this->label1->Text = 'button 1 clicked';
	}
}
