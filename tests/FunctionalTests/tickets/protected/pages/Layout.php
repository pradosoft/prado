<?php

class Layout extends TTemplateControl
{
	public function onLoad($param)
	{
		$array = array();
		preg_match('/\d+/',$this->getPage()->getPagePath(), $array);
		$num = $array[0];
		$this->getPage()->setTitle("Verifying Ticket $num");
		$this->ticketlink->setText("Verifying Ticket $num");
		$this->ticketlink->setNavigateUrl("http://trac.pradosoft.com/ticket/{$num}");
	}
}

?>