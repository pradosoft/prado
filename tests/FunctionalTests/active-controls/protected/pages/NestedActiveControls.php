<?php

class NestedActiveControls extends TPage
{
	function callback1_requested($sender, $param)
	{
		$this->content1->visible = true;
		$this->panel1->render($param->NewWriter);
	}

	function button1_clicked($sender, $param)
	{
		$this->label1->Text = "Label 1: Button 1 Clicked";
		$this->label2->Text = "Label 2: Button 1 Clicked";
		$this->label3->Text = "Label 3: Button 1 Clicked";
	}
}

?>