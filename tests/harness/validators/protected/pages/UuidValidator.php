<?php

/**
 * Harness page for the TUuidValidator functional tests.
 *
 * "validator1" uses the defaults, "validator2" allows versions 4 and 7 in any
 * form, and "validator3" validates on the server only. "status" reports the
 * page validity after a postback.
 */
class UuidValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
