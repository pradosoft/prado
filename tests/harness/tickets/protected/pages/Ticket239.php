<?php

class Ticket239 extends TPage
{
	public function activateView($sender, $param)
	{
		$this->Result->Text .= $sender->getID() . " is activated. ";
	}

	public function deactivateView($sender, $param)
	{
		$this->Result->Text .= $sender->getID() . " is deactivated. ";
	}
}
