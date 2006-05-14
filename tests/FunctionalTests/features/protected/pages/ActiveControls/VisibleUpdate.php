<?php
/*
 * Created on 13/05/2006
 */

class VisibleUpdate extends TPage
{ 
	function click1($sender)
	{
		$this->label1->setText($this->getButtonState($sender));
		
		//$this->button1->setEnabled(false);
		$this->button1->setVisible(false);
	//	$this->button2->setEnabled(true);	
		$this->button2->setVisible(true);	
	}

	function click2($sender)
	{
		$this->label1->setText($this->getButtonState($sender));

	//	$this->button1->setEnabled(true);
		$this->button1->setVisible(true);
	///	$this->button2->setEnabled(false);	
		$this->button2->setVisible(false);
	}
	
	protected function getButtonState($button)
	{
		return "Before you clicked on ".$button->Text.
			", Button 1 was ".($this->button1->Enabled ? 'enabled' : 'disabled').
			" and Button 2 was ".($this->button2->Enabled ? 'enabled' : 'disabled');
	}
}

?>