<?php

class RatingList extends TPage
{
	function list1_oncallback($sender, $param)
	{
		$sender->Enabled=false;
	}

	function button1_clicked($sender, $param)
	{
		$this->list1->Enabled = true;
	}

	function button2_clicked($sender, $param)
	{
		$this->list1->SelectedIndex=3;
		$this->list1->Enabled=false;
	}
}

?>