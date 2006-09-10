<?php
// $Id$
class Home extends TPage
{
	public function checkboxClicked($sender,$param)
	{
		$sender->Text= $sender->ClientID . " clicked";
	}

	public function checkboxCallback($sender, $param)
	{
		$sender->Text .= ' using callback';
	}
}

?>