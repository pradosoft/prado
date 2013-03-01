<?php
// $Id: Home.php 3189 2012-07-12 12:16:21Z ctrlaltca $
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

