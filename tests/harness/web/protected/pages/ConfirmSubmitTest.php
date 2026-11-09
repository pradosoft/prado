<?php

/**
 * Harness page for the TConfirmSubmit functional tests (#1401).
 *
 * The wrapper holds one control of each kind that acts: a link button, submit
 * buttons (one validated), an image button, AutoPostBack checkbox, list, and text
 * box, an active button, and a hyperlink. "Plain check", "Plain text", and "Outside
 * action" do not ask for confirmation.
 */
class ConfirmSubmitTest extends TPage
{
	public function acted($sender, $param)
	{
		$this->status->setText('Acted: ' . $sender->getText());
	}

	public function imageActed($sender, $param)
	{
		$this->status->setText('Acted: image');
	}

	public function changed($sender, $param)
	{
		$this->status->setText('Changed: ' . $sender->getID());
	}

	public function activeActed($sender, $param)
	{
		$this->activeStatus->setText('Called back');
	}
}
