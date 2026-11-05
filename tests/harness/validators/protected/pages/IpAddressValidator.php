<?php

/**
 * Harness page for the TIpAddressValidator functional tests.
 *
 * "validator1" uses the defaults, "validator2" requires a public IPv4 CIDR
 * block, and "validator3" requires a private address on the server only.
 * "status" reports the page validity after a postback.
 */
class IpAddressValidator extends TPage
{
	public function submitted($sender, $param)
	{
		$this->status->setText($this->getIsValid() ? 'Page valid' : 'Page invalid');
	}
}
