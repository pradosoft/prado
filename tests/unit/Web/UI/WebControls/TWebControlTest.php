<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TWebControl;

class TWebControlTest extends TestCase
{
	public function testSetAccessKeyAcceptsOneCharacter()
	{
		$control = new TWebControl();
		$control->setAccessKey('a');
		$this->assertSame('a', $control->getAccessKey());
		$control->setAccessKey('é');
		$this->assertSame('é', $control->getAccessKey());
		$control->setAccessKey('');
		$this->assertSame('', $control->getAccessKey());
		$control->setAccessKey(5);
		$this->assertSame('5', $control->getAccessKey());
	}

	public function testSetAccessKeyRejectsMoreThanOneCharacter()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TWebControl())->setAccessKey('ab');
	}
}
