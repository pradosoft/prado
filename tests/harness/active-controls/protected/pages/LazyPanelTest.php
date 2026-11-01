<?php

use Prado\Exceptions\TInvalidOperationException;

/**
 * Harness page for the TLazyPanel functional tests (#1408).
 *
 * Each panel counts its OnLoadContent in "Loads". "Fail the next load" makes
 * OnLoadContent throw, so a test sees a failed load. "Other callback" and
 * "Postback" show that loaded content stays loaded. "Server load" loads the
 * Manual panel "server" from another control's callback.
 */
class LazyPanelTest extends TPage
{
	public function loadContent($sender, $param)
	{
		if ($this->fail->getChecked()) {
			throw new TInvalidOperationException('lazy panel load failed');
		}
		$this->loads->setText((string) ((int) $this->loads->getText() + 1));
	}

	public function otherCallback($sender, $param)
	{
		$this->callbacks->setText((string) ((int) $this->callbacks->getText() + 1));
		if ($this->onload->getIsContentLoaded()) {
			$this->noteEcho->setText(strtoupper($this->note->getText()));
		}
	}

	public function serverLoad($sender, $param)
	{
		$this->server->loadContent($param);
	}
}
