<?php

class DatePickerInCallback extends TPage  {

	public function onLoad($param){
		parent::onLoad($param);
		if(!$this->IsPostBack)
			$this->datepicker->setTimeStamp(time());
	}

	public function testDatePicker($sender, $param){
		$this->status->Text = $this->datepicker->getTimestamp()."  ".$this->datepicker->getText();
	}

 }

?>