<?php

class Issue216 extends TPage
{
	public function buttonClickCallback($sender, $param)
	{
		$this->result->setText('Tab ActiveIndex is : ' . $this->tabpanel->ActiveViewIndex);
	}
}
