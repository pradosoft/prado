<?php

class TimeTriggeredCallbackTest extends TPage
{
	public function start_timer($sender, $param)
	{
		$this->timer1->startTimer();
		$this->setViewState('count', 0);
	}

	public function stop_timer($sender, $param)
	{
		$this->timer1->stopTimer();
	}

	public function tick($sender, $param)
	{
		$count = (int) ($this->getViewState('count'));
		$this->setViewState('count', ++$count);
		if ($count > 10) {
			$this->timer1->stopTimer();
		} else {
			$this->label1->Text .= " " . $count;
		}
	}
}
