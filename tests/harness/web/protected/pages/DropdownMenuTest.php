<?php

/**
 * Harness page for the TDropdownMenu functional tests (#1401).
 *
 * "Actions" opens a menu of two postback buttons, a disabled button, a
 * separator, and a link. A postback item writes its text to the status label.
 */
class DropdownMenuTest extends TPage
{
	public function itemClicked($sender, $param)
	{
		$this->status->setText('Clicked: ' . $sender->getText());
	}
}
