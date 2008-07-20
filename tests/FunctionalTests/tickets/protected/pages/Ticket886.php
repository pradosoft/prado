<?php
class Ticket886 extends TPage {
	
	public function onButtonClicked($sender, $param) {
		$this->Output->Text = date('Y-m-d', $this->Year->TimeStamp);
	}
	
}
?>
