<?php

/**
 * Harness page for TProofOfWork functional tests.
 *
 * The AntiBot folder configures a file cache, which TProofOfWork needs to claim each challenge once.
 * Check is a validating callback; Ping is a callback without validation. Each result counts its callbacks.
 */
class ProofOfWorkTest extends TPage
{
	public function sendClicked($sender, $param)
	{
		$this->Result->setText($this->getIsValid() ? 'Accepted' : 'Rejected');
	}

	public function checkClicked($sender, $param)
	{
		$count = $this->getViewState('Checks', 0) + 1;
		$this->setViewState('Checks', $count);
		$this->CallbackResult->setText(($this->getIsValid() ? 'Accepted' : 'Rejected') . ' #' . $count);
	}

	public function pingClicked($sender, $param)
	{
		$count = $this->getViewState('Pings', 0) + 1;
		$this->setViewState('Pings', $count);
		$this->PingResult->setText('Pong #' . $count);
	}
}
