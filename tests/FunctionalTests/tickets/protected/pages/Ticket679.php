<?php

class Ticket679 extends TPage
{
	// repeater bug
	public function onLoad($param)
	{
		parent::onLoad($param);
		$dataArray[0][ 'id' ] = '1' ;

		if (!$this->getPage()->getIsPostback()) {
			$this->Repeater->DataSource = $dataArray ;
			$this->Repeater->dataBind() ;
		}
	}

	public function changeText($sender, $param)
	{
		$obj = $this->myLabel ;
		$obj->Text = $sender->Text ;
		$obj->Display = "Dynamic";

		// solution
				//$this->CallBackClient->show($obj, true);
	}


	// activeradiobutton bug
	public function checkRadioButton($sender, $param)
	{
		$this->myRadioButton->checked = true;
	}
	public function uncheckRadioButton($sender, $param)
	{
		$this->myRadioButton->checked = false;

		// solution
			   //$this->CallbackClient->check($this->myRadioButton, false);
	}
}
