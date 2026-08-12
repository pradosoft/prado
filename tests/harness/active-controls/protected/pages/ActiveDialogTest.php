<?php

class ActiveDialogTest extends TPage
{
	public function dialog_opened($sender, $param)
	{
		$this->label1->Text = 'opened';
	}

	public function dialog_closed($sender, $param)
	{
		$this->label1->Text = 'closed';
	}

	public function open_dialog($sender, $param)
	{
		$this->dialog1->Open = true;
		$this->label1->Text = 'server-opened';
	}

	public function close_dialog($sender, $param)
	{
		$this->dialog1->Open = false;
		$this->label1->Text = 'server-closed';
	}
}
