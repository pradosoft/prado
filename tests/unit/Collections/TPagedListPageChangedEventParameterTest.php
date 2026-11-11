<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TPagedListPageChangedEventParameter;

class TPagedListPageChangedEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testOldPageIndex()
	{
		$param = new TPagedListPageChangedEventParameter(4);
		self::assertSame(4, $param->getOldPageIndex());
		self::assertSame(4, $param->OldPageIndex);
	}
}
