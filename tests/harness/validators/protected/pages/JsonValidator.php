<?php

/**
 * Harness page for the TJsonValidator functional tests.
 *
 * "validator1" validates in the browser and on the server, and "validator2"
 * validates on the server only. "status" reports the page validity after a postback.
 */
class JsonValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
