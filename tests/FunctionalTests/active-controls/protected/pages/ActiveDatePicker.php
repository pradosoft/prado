<?php

prado::using('System.Web.UI.ActiveControls.*');

class ActiveDatePicker extends TPage  {

	public function onLoad($param){
		parent::onLoad($param);
		if(!$this->IsPostBack)
			$this->datepicker->setTimeStamp(time());
	}

	public function testDatePicker($sender, $param){
		$this->status->Text = $this->datepicker->getText();
	}

	public function today ($sender, $param)
	{
		$this->datepicker->setTimestamp(time());
	}
	
	public function increase ($sender, $param)
	{
		$this->datepicker->setTimestamp(strtotime('+1 day', $this->datepicker->getTimestamp()));
	}
	public function decrease ($sender, $param)
	{
		$this->datepicker->setTimestamp(strtotime('-1 day', $this->datepicker->getTimestamp()));
	}
	
	public function toggleMode ($sender, $param)
	{
		if ($this->datepicker->getInputMode()==TDatePickerInputMode::DropDownList)
			$this->datepicker->setInputMode(TDatePickerInputMode::TextBox);
		else
			$this->datepicker->setInputMode(TDatePickerInputMode::DropDownList);
	}
	
 }
 

?>