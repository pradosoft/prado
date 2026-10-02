<?php

/**
 * Harness page for TFormGuard functional tests. The result names the failure reason.
 */
class FormGuardTest extends TPage
{
	public function sendClicked($sender, $param)
	{
		$this->Result->setText($this->getIsValid() ? 'Accepted' : 'Rejected: ' . $this->Guard->getFailureReason());
	}
}
