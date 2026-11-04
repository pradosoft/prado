<?php

/**
 * Harness page for the TLuhnValidator functional tests.
 *
 * "validator1" accepts digits only, "validator2" ignores spaces and hyphens,
 * and "validator3" validates on the server only. "status" reports the page
 * validity after a postback.
 */
class LuhnValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
