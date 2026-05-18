<?php

class Ticket925 extends TPage
{
	public function getTimer1Value()
	{
		return $this->getViewState('timer1', 0);
	}

	public function getTimer2Value()
	{
		return $this->getViewState('timer2', 0);
	}

	public function setTimer1Value($value)
	{
		$this->setViewState('timer1', $value, 0);
	}

	public function setTimer2Value($value)
	{
		$this->setViewState('timer2', $value, 0);
	}

	public function startTimer1($sender, $param)
	{
		$this->timer1->startTimer();
	}

	public function stopTimer1($sender, $param)
	{
		$this->timer1->stopTimer();
	}

	public function startTimer2($sender, $param)
	{
		$this->timer2->startTimer();
	}

	public function stopTimer2($sender, $param)
	{
		$this->timer2->stopTimer();
	}

	public function changeIntervalTimer1($sender, $param)
	{
		$this->timer1->setInterval(1);
	}

	public function timer1callback($sender, $param)
	{
		$this->timer1result->Text .= ($this->Timer1Value += $this->timer1->Interval) . '... ';
		if ($this->Timer1Value > 20) {
			$this->timer1Value = 0;
			$this->timer1result->Text = '';
			$this->timer1->stopTimer();
		}
	}

	public function timer2callback($sender, $param)
	{
		$this->timer2result->Text .= ($this->Timer2Value += $this->timer2->Interval) . '... ';
		if ($this->Timer2Value > 20) {
			$this->timer2Value = 0;
			$this->timer2result->Text = '';
			$this->timer2->stopTimer();
		}
	}
}
