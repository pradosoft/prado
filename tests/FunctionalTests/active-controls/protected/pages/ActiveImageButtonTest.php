<?php

class ActiveImageButtonTest extends TPage
{
	function change_text($sender, $param)
	{
		$this->image1->AlternateText = "Muahahahah";
	}

	function change_image($sender, $param)
	{
		$this->image1->ImageUrl = $sender->CustomData;
	}

	function change_align($sender, $param)
	{
		$this->image1->ImageAlign="absbottom";
	}

	function change_description($sender, $param)
	{
		$this->image1->DescriptionUrl = "maahahhaa";
	}

	function image1_clicked($sender, $param)
	{
		$this->label1->Text = "Image clicked at x={$param->x}, y={$param->y}";
	}
}

?>