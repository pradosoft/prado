<?php

class InPlaceWithValidator extends TPage
{
	function button_valid($sender, $param){

		$this->status->Text = "Status: ". $this->Firstname->Text.".".$this->Lastname->Text;

	}

}

?>