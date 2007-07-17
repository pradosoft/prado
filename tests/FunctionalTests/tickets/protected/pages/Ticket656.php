<?php
class Ticket656 extends TPage 
{
	public function updateLbl ($sender,$param)
	{
		$this->lblStatus->setText($this->datePicker->getDate());
	}
}
?>