<?php

/**
 * Harness page for Dynamic validator output that moves a submit button.
 *
 * The amount's Dynamic message sits inline before the Save button. The title's
 * Dynamic validation summary sits above the Save Title button. Hiding either one
 * moves its button.
 */
class ValidatorDynamicShiftTest extends TPage
{
	public function save($sender, $param)
	{
		$this->status->setText('saved ' . $this->amount->getText());
	}

	public function saveTitle($sender, $param)
	{
		$this->status->setText('saved ' . $this->title->getText());
	}
}
