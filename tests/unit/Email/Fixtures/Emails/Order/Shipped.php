<?php

namespace Application\Emails\Order;

use Prado\Email\UI\TEmailBody;

class Shipped extends TEmailBody
{
	private int $_number = 0;

	public int $loads = 0;

	public function getNumber(): int
	{
		return $this->_number;
	}

	public function setNumber($value)
	{
		$this->_number = (int) $value;
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->loads++;
		$this->getEmail()->setHeader('X-Order', (string) $this->_number);
	}
}
