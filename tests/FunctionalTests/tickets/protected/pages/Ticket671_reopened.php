<?php

Prado::Using ('System.Web.UI.ActiveControls.*');

class Ticket671_reopened extends TPage
{
	
	public function save( $sender, $param )
	{
		$txt="Save callback called ";
		if ( $this->isValid )
			$txt .= "DATA OK";
		else
			$txt .= "DATA NOK";
		$this->Result->Text.= ' --- ' . $txt;
	}
	
	public function check( $sender, $param )
	{		
		//$c=$this->CheckCount;
		$this->Result->Text="Check callback called (".++$this->CheckCount.")";
		//$this->CheckCount=$c;
		if ( $this->testField->getText() == 'Test' )
			$param->isValid = true;
		else
			$param->isValid = false;
	}
	
	public function setCheckCount($value)
	{
		$this->setViewState('CheckCount', $value);
	}
	
	public function getCheckCount()
	{
		return $this->getViewState('CheckCount', 0);
	}
}
?>