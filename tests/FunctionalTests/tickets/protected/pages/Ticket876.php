<?php

class Ticket876 extends TPage {
	
	public function onSetEmptyCssUrl($sender, $param) {
		$this->TabPanel->CssUrl = "";
	}
	
}

?>
