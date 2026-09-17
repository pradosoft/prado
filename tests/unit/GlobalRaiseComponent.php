<?php

namespace Prado\Test\Unit;

use Prado\Util\IDynamicMethods;

class GlobalRaiseComponent extends NewComponent implements IDynamicMethods
{
	private $_callorder = [];

	public function getCallOrders()
	{
		return $this->_callorder;
	}
	public function __dycall($method, $args)
	{
		if (strncasecmp($method, 'fx', 2) !== 0) {
			return;
		}
		$this->_callorder[] = 'fxcall';
	}
	public function fxGlobalListener($sender, $param)
	{
		$this->_callorder[] = 'fxGL';
	}
	public function fxPrimaryGlobalEvent($sender, $param)
	{
		$this->_callorder[] = 'primary';
	}
	public function commonRaiseEventListener($sender, $param)
	{
		$this->_callorder[] = 'com';
	}
	public function postglobalRaiseEventListener($sender, $param)
	{
		$this->_callorder[] = 'postgl';
	}
	public function preglobalRaiseEventListener($sender, $param)
	{
		$this->_callorder[] = 'pregl';
	}
}
