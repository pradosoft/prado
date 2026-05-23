<?php

class Issue724 extends TPage
{
	protected function cmdA($sender, $param)
	{
		$this->labelA->Text = 'Button A Pressed';
		// simulate a slow callback by waiting 5 secs
		sleep(5);
	}

	protected function cmdB($sender, $param)
	{
		$this->labelB->Text = 'When button has been B pressed, the text of label A was: ' . $this->labelA->Text;
	}
}
