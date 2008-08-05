<?php
class Ticket897 extends TPage {
	
	public function onButtonClicked($sender, $param) {
		$this->Output->Text = date('Y-m-d', $this->Date->TimeStamp);
	}
	
}
?>
