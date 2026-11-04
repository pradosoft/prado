<?php

/**
 * Harness page for the TUnicodeValidator functional tests.
 *
 * "validator1" uses the defaults and "validator2" allows ASCII only. Both
 * validate on the server; "status" reports the page validity after a postback.
 */
class UnicodeValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
