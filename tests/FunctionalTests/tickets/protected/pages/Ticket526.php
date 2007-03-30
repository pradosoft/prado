<?php

Prado::using('System.Web.UI.ActiveControls.*');
class Ticket526 extends TPage
{
	public function callback($s, $p)
	{
		$this->dp->Mode="Button";
		$this->textbox->Text = 'callback';
		$this->activePanel->Enabled="false";
		$this->activePanel->render($p->NewWriter);
	}
}

?>