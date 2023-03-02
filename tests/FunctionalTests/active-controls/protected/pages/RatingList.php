<?php

class RatingList extends TPage
{
	public function list1_oncallback($sender, $param)
	{
		$newRating = ($sender->Rating + $sender->SelectedIndex + 1) / 2;
		$sender->Rating = $newRating;
		$sender->Caption = "Rating : " . $newRating;
		$sender->Enabled = false;
	}


	public function list2_oncallback($sender, $param)
	{
	}

	public function button1_clicked($sender, $param)
	{
		$this->list1->Enabled = true;
	}

	public function button2_clicked($sender, $param)
	{
		$this->list1->Enabled = false;
	}

	public function button5_clicked($sender, $param)
	{
		$this->list1->SelectedIndex = 3;
	}
}
