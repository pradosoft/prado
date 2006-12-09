<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket433 extends TPage
{
	public function onLoad($param)
	{
		if(!$this->IsPostBack)
			$this->VoteClick->Text = "BEFORE click";
	}

	public function onUpdateVoteClick($sender, $param)
	{
		$sender->Text = 'AFTER click';
	}

	public function onUpdateVoteCallback($sender, $param)
	{
		$sender->Text .= ' CALLBACK DONE';
	}
}

?>