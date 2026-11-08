<?php

/**
 * Harness page for the dialog invoker functional tests (#959).
 *
 * "Edit name" and "Show notice" open their dialogs through Invoker Commands,
 * without script. The edit dialog is Modal: a Save without a name reopens it
 * modally after the postback, and Cancel closes it with the return value
 * "cancel". Its HeaderText names it, and its close button closes it with an
 * empty return value. The notice dialog closes on a click outside it (ClosedBy="Any").
 */
class DialogInvokerTest extends TPage
{
	public function saveClicked($sender, $param)
	{
		$name = trim($this->userName->getText());
		$this->editDialog->setOpen($name === '');
		$this->status->setText($name === '' ? 'Name required' : 'Saved: ' . $name);
	}
}
