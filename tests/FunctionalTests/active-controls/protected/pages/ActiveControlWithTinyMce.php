<?php

class ActiveControlWithTinyMce extends TPage
{
	function button1_callback($sender, $param)
	{
		$this->label1->Text = $this->text1->SafeText;
	}
}

?>