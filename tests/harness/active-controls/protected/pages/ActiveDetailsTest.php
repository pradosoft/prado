<?php

class ActiveDetailsTest extends TPage
{
	public function details_opened($sender, $param)
	{
		$this->label1->Text = 'opened';
	}

	public function details_closed($sender, $param)
	{
		$this->label1->Text = 'closed';
	}

	public function open_details($sender, $param)
	{
		$this->details1->Open = true;
		$this->label1->Text = 'server-opened';
	}

	public function close_details($sender, $param)
	{
		$this->details1->Open = false;
		$this->label1->Text = 'server-closed';
	}
}
