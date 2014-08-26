<?php
/**
 *
 *
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @copyright Copyright &copy; 2007, CERAM Sophia Antipolis
 * @license url nameoflicense
 *
 */

prado::using ('Application.pages.ExtendedToggleImageButton');

class Ticket659 extends TPage {
	public function clickToggleButton ($sender, $param) {
		$this->lbl->Text=$sender->State;
	}
}