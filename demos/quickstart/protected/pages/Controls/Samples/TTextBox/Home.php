<?php

class Home extends TPage
{
	protected function onLoad($param)
	{
		parent::onLoad($param);
		$this->Output->dataBind();
	}

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