<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Web\UI\TCommandEventParameter;

class TCommandEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testDefaults(): void
	{
		$param = new TCommandEventParameter();
		$this->assertSame('', $param->getCommandName());
		$this->assertSame('', $param->getCommandParameter());
	}

	public function testNameAndParameter(): void
	{
		$param = new TCommandEventParameter('sort', ['column' => 'name']);
		$this->assertSame('sort', $param->getCommandName());
		$this->assertSame(['column' => 'name'], $param->getCommandParameter());
	}
}
