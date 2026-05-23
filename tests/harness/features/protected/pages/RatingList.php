<?php

class RatingList extends TPage
{
	protected function rating3_selectionChanged($sender, $param)
	{
		$this->labelResult3->Text = $this->Rating3->SelectedValue;
	}
}
