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

	public function pbar2_minus($sender,$param)
	{
		$this->pbar2->getOptions()->value = max(0, $this->pbar2->getOptions()->value - 10);
	}

	public function pbar2_plus($sender,$param)
	{
		$this->pbar2->getOptions()->value = min($this->pbar2->getOptions()->max, $this->pbar2->getOptions()->value + 10);
	}
}