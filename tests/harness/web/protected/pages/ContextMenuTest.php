<?php

/**
 * Harness page for the TContextMenu functional tests (#1401).
 *
 * A right-click, Shift+F10, or the ContextMenu key on the "photo" panel opens a
 * menu of two postback buttons; an item writes its text to the status label.
 */
class ContextMenuTest extends TPage
{
	public function itemClicked($sender, $param)
	{
		$this->status->setText('Clicked: ' . $sender->getText());
	}
}
