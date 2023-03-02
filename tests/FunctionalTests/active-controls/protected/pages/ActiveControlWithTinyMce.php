<?php

class ActiveControlWithTinyMce extends TPage
{
	public function button1_callback($sender, $param)
	{
		$this->label1->Text = $this->text1->SafeText;
	}
}
