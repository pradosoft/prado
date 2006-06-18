<?php

class CallbackTimerTest extends TPage
{
	function start_timer($sender, $param)
	{
		$this->timer1->startTimer();
		$this->setViewState('count', 0);
	}
	
	function stop_timer($sender, $param)
	{
		$this->timer1->stopTimer();
	}
	
	function tick($sender, $param)
	{
		$count = intval($this->getViewState('count'));
		$this->setViewState('count', ++$count);		
		if($count > 10) 
			$this->timer1->stopTimer();
		else
			$this->label1->Text .= " ".$count;
	}
}

?>