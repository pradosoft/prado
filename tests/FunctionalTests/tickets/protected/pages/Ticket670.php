<?php
class Ticket670 extends TPage 
{
	public function clickOk($sender,$param)
	{
		$this->lbl->Text=$this->datePicker->getDate();
	}
}
?>