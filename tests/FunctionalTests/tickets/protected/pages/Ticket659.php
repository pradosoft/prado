<?php
/**
 *
 * 
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @copyright Copyright &copy; 2007, CERAM Sophia Antipolis
 * @license url nameoflicense
 * @version $Id: Ticket659.php 2039 2007-06-28 08:41:57Z tof $
 * 
 */

prado::using ('Application.pages.ExtendedToggleImageButton');

class Ticket659 extends TPage {
	public function clickToggleButton ($sender, $param) {
		$this->lbl->Text=$sender->State;
	}
}