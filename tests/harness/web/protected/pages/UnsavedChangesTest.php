<?php

/**
 * Harness page for the TUnsavedChangesGuard functional tests (#1408).
 *
 * "Save by postback" and "Save by link" post back and count a save; an empty name fails
 * server validation. "Save by callback" counts a save and marks the guard clean.
 * "Callback without save" changes nothing. "Re-render panel" re-renders the panel's
 * field with a server value. "Callback redirect" redirects to HttpRedirectTarget.
 */
class UnsavedChangesTest extends TPage
{
	public function save($sender, $param)
	{
		if ($this->getIsValid()) {
			$this->saves->setText($this->saves->getText() + 1);
		}
	}

	public function callbackSave($sender, $param)
	{
		$this->saves->setText($this->saves->getText() + 1);
		$this->guard->markClean();
	}

	public function callbackOnly($sender, $param)
	{
	}

	public function refreshPanel($sender, $param)
	{
		$this->server->setText('server ' . time());
		$this->panel->render($param->getNewWriter());
	}

	public function redirect($sender, $param)
	{
		$this->getResponse()->redirect($this->getService()->constructUrl('HttpRedirectTarget'));
	}
}
