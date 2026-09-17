<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Test\Unit\Data\SqlMap\Domain\Account;

class AccountWithOrders extends Account
{
	private $_orders = [];

	public function setOrders($orders)
	{
		$this->_orders = $orders;
	}

	public function getOrders()
	{
		return $this->_orders;
	}
}
