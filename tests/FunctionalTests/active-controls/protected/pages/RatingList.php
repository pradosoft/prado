<?php

class RatingList extends TPage
{
	function list1_oncallback($sender, $param)
	{
		$newRating = ($sender->Rating + $sender->SelectedIndex+1)/2;
		$sender->Rating = $newRating;
		$sender->Caption = "Rating : ".$newRating;
		$sender->Enabled=false;
	}


	function list2_oncallback($sender, $param)
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