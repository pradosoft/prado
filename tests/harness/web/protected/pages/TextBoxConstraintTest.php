<?php

/**
 * Harness page for the TTextBox constraint functional tests.
 *
 * Renders a Number text box with Min, Max, Step, Required, and Placeholder, an
 * Email text box with Multiple and Pattern, and a Time text box with Min, Max,
 * and Step, each named by a TLabel, and a Week text box with a TRangeValidator
 * that has a Step.
 */
class TextBoxConstraintTest extends TPage
{
	public function save($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'saved ' . $this->sprint->getText() : 'invalid');
	}
}
