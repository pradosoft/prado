<?php
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket598 extends TPage
{
public function onLoad ($param) {
		parent::onLoad($param);
		if (!$this->isPostBack and !$this->isCallBack) {
			$this->Lbl->setText(date("h:m:s"));
		}
	}
	public function startBigTask ($sender, $param) {
		sleep(10); // Simulate task
	}

	public function updateLbl($sender, $param) {
		$this->Lbl->SetText(date("h:m:s"));
	}
}
?>