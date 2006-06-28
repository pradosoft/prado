<?php

class Ticket239 extends TPage
{
	public function activateView($sender,$param)
	{
		$this->Result->Text.=$sender->ID." is activated. ";
	}

	public function deactivateView($sender,$param)
	{
		$this->Result->Text.=$sender->ID." is deactivated. ";
	}
}

?>