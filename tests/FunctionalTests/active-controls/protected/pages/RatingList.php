<?php

class RatingList extends TPage
{
	function list1_oncallback($sender, $param)
	{
	}

	function button1_clicked($sender, $param)
	{
		$this->list1->Enabled = true;
	}

	function button2_clicked($sender, $param)
	{
		$this->list1->Enabled=false;
	}

	function button5_clicked($sender, $param)
	{
		$this->list1->SelectedIndex=3;
	}
}

?>