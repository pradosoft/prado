<?php

class ActiveImageButtonTest extends TPage
{
	public function change_text($sender, $param)
	{
		$this->image1->AlternateText = "Muahahahah";
	}

	public function change_image($sender, $param)
	{
		$this->image1->ImageUrl = $sender->CustomData;
	}

	public function change_align($sender, $param)
	{
		$this->image1->ImageAlign = "absbottom";
	}

	public function change_description($sender, $param)
	{
		$this->image1->DescriptionUrl = "maahahhaa";
	}

	public function image1_clicked($sender, $param)
	{
		$this->label1->Text = "Image clicked at x={$param->x}, y={$param->y}";
	}
}
