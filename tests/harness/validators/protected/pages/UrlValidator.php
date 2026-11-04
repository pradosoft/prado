<?php

/**
 * Harness page for the TUrlValidator functional tests.
 *
 * "validator1" uses the defaults, "validator2" allows ftp and single-label ASCII
 * hosts, and "validator3" validates on the server only. "status" reports the
 * page validity after a postback.
 */
class UrlValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
