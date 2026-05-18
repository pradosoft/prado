<?php

class InPlaceWithValidator extends TPage
{
	public function button_valid($sender, $param)
	{
		$this->status->Text = "Status: " . $this->Firstname->Text . "." . $this->Lastname->Text;
	}
}
