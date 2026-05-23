<?php

class Ticket470 extends TPage
{
	/**
	 * Increase the reload counter and render the activepanel content
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function Reload($sender, $param)
	{
		$this->Results->Text = "";
		$this->counter->Text = $this->counter->Text + 1;
		$this->activePanelTest->renderControl($param->getNewWriter());
	}

	/**
	 *function to call when the form is valid (and the linkbutton fired his callback event)
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function Valid($sender, $param)
	{
		$this->Results->Text = "OK!!!";
	}
}
