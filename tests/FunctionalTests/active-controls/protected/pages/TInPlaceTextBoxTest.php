<?php

class TInPlaceTextBoxTest extends TPage
{
	public function load_text($sender, $param)
	{
		$sender->Text = "muahaha";
	}

	public function label1_changed($sender, $param)
	{
		$this->status->Text = "Status: " . $sender->Text;
	}

	public function button_clicked($sender, $param)
	{
		$this->label1->Text = "hahahaha";
	}

	public function NewPackageSubject($sender, $param)
	{
		throw new TException('Exist');
	}
}
