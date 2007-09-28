<?php

prado::using ('System.Web.UI.ActiveControls.*');

class Ticket691 extends TPage
{
	public function list_oncallback ($sender, $param)
	{
		$sender->Rating=$sender->SelectedIndex+1;
		$this->Result->Text="You vote ".$sender->Rating;
	}
}
?>