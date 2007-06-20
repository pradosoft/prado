<?php

class Layout extends TTemplateControl
{
	public function onLoad($param)
	{
		$this->getPage()->setTitle("Verifying Ticket 653");
		$this->ticketlink->setText("Verifying Ticket 653");
		$this->ticketlink->setNavigateUrl("http://trac.pradosoft.com/prado/ticket/653");
	}
}

?>