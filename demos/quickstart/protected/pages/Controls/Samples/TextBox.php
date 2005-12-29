<?php

class TextBox extends TPage
{
	public function textChanged($sender,$param)
	{
		$sender->Text="text changed";
	}

	public function submitText($sender,$param)
	{
		$this->TextBox1->Text="You just entered '".$this->TextBox1->Text."'.";
	}
}

?>