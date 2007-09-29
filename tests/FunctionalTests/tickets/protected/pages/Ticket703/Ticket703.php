<?php

class Ticket703 extends TPage {
	public function onLoad ($param)
	{
		parent::onLoad($param);
		if (!$this->isPostBack && !$this->isCallback)
		{
			$this->refreshLog();		
		}
	}
	
	public function refreshLog ()
	{
		$this->logBox->Text=file_get_contents(prado::getPathOfNameSpace('Ticket703.Logs.LogFile', '.txt'));
	}
	
	public function clearLog ($sender, $param)
	{
		$file=prado::getPathOfNameSpace('Ticket703.Logs.LogFile', '.txt');
		$f=fopen($file,"w");
		fclose($f);
		$this->refreshLog();
	}
	
	public function addLog($sender,$param)
	{
		prado::log($this->logMessage->getText(), TLogger::DEBUG, "Tickets"); 
	}
}
?>