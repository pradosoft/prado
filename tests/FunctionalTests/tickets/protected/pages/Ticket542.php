<?php
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket542 extends TPage {

	public function slideUp($sender, $param) {
		$this->CallbackClient->slideUp($this->TheBox);
	}

	public function blindUp($sender, $param) {
		$this->CallbackClient->blindUp($this->TheBox);
	}

	public function slideDown($sender, $param) {
		$this->CallbackClient->slideDown($this->TheBox);
	}

	public function blindDown($sender, $param) {
		$this->CallbackClient->blindDown($this->TheBox);
	}

}

?>