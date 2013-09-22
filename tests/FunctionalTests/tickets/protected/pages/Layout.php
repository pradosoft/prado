<?php

class Layout extends TTemplateControl
{
	public function onLoad($param)
	{
		$num	= str_replace(array('Ticket', 'Issue'), '', $this->getPage()->getPagePath());
		$type	= str_replace($num, '', $this->getPage()->getPagePath());
		
		$this->getPage()->setTitle("Verifying $type $num");
		$this->ticketlink->setText("Verifying $type $num");
		
		//TODO New issues will link to https://github.com/pradosoft/prado/issues/{$num}
		if(strToLower($type) === 'issue') {
			$this->ticketlink->setNavigateUrl("http://code.google.com/p/prado3/issues/detail?id={$num}");	
		}
		else {
			$this->ticketlink->setNavigateUrl("http://trac.pradosoft.com/prado/ticket/{$num}");	
		}
		
	}
}
