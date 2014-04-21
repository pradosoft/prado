<?php

class Home extends TPage
{
	public function pbar1_complete($sender,$param)
	{
		$this->label1->Text="Progressbar complete!";
	}

	public function pbar1_changed($sender,$param)
	{
		$this->label1->Text="Progressbar changed.";
	}
}