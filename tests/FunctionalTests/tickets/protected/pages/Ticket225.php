<?php

class Ticket225 extends TPage
{
	function button4_Clicked()
	{
		$this->label1->setText($this->getGroupIDs($this->button1));
	}
	
	private function getGroupIDs($radio)
	{
		$ids = '';
		foreach($radio->getRadioButtonsInGroup() as $control)
			$ids .= " ".$control->getUniqueID();
		return $ids;
	}
}

?>