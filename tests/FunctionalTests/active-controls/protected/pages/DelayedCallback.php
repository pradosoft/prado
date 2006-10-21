<?php

class DelayedCallback extends TPage
{
	function callback1($sender, $param)
	{
		$ms = 4;
		sleep($ms);
		$this->status->Text="Callback 1 returned after {$ms}s";
	}

	function callback2($sender, $param)
	{
		$ms = 2;
		sleep($ms);
		$this->status->Text="Callback 2 delayed {$ms}s";
	}

}

?>