<?php

/**
 * Harness page for TProofOfWork functional tests.
 *
 * The AntiBot folder configures a file cache, which TProofOfWork needs to claim each challenge once.
 */
class ProofOfWorkTest extends TPage
{
	public function sendClicked($sender, $param)
	{
		$this->Result->setText($this->getIsValid() ? 'Accepted' : 'Rejected');
	}
}
