<?php

class Ticket587 extends TPage
{
	public function onTriggerCallback($sender, $param)
	{
		$count = (int) $this->count->getText();
		$this->count->setText(++$count);
	}
}
