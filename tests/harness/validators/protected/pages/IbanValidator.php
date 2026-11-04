<?php

/**
 * Harness page for the TIbanValidator functional tests.
 *
 * "validator1" accepts every IBAN country, "validator2" allows DE, AT, and CH,
 * and "validator3" validates on the server only. "status" reports the page
 * validity after a postback.
 */
class IbanValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
