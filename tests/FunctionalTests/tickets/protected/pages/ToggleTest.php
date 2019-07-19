<?php
/**
 *
 *
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @license url nameoflicense
 *
 */

prado::using('Application.controls.ToggleImageButton');

class ToggleTest extends TPage
{
	public function clickToggleButton($sender, $param)
	{
		$this->lbl->Text = $sender->State;
	}
}
