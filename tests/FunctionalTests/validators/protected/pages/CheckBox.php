<?php

class CheckBox extends TPage
{
	public function button1Clicked($sender,$param)
	{
		$this->Result1->Text="Button1 is clicked";
		if($this->IsValid)
			$this->Result1->Text.=' and valid';
	}

	public function button2Clicked($sender,$param)
	{
		$this->Result2->Text="Button2 is clicked";
		if($this->IsValid)
			$this->Result2->Text.=' and valid';
	}

	public function button3Clicked($sender,$param)
	{
		$this->Result3->Text="Button3 is clicked";
		if($this->IsValid)
			$this->Result3->Text.=' and valid';
	}
}

?>