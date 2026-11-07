<?php

/**
 * Harness page for the TConfirmButton functional tests (#1401).
 *
 * "Remove" asks for confirmation in a dialog before it posts back; it sits in a
 * paragraph, which its dialog does not break. "Archive" validates the Reason
 * field; a failed validation closes its dialog so the message shows.
 */
class ConfirmButtonTest extends TPage
{
	public function removeClicked($sender, $param)
	{
		$this->status->setText('Removed');
	}

	public function archiveClicked($sender, $param)
	{
		$this->status->setText('Archived: ' . $this->reason->getText());
	}
}
