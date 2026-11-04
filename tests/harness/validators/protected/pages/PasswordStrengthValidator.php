<?php

/**
 * Harness page for the TPasswordStrengthValidator functional tests.
 *
 * "validator1" checks the default Medium strength in the browser and on the
 * server. "validator2" checks Strong on the server only. "status" reports the
 * page validity after a postback.
 */
class PasswordStrengthValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
