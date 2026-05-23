<?php

class Layout extends TTemplateControl
{
	public function onLoad($param)
	{
		$num = str_replace(['Ticket', 'Issue'], '', $this->getPage()->getPagePath());
		$type = str_replace($num, '', $this->getPage()->getPagePath());
		
		$this->getPage()->setTitle("Verifying $type $num");
		$this->ticketlink->setText("Verifying $type $num");
		
		$this->ticketlink->setNavigateUrl("https://github.com/pradosoft/prado/issues/{$num}");
	}
}
