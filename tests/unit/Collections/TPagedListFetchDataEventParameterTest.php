<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TPagedListFetchDataEventParameter;

class TPagedListFetchDataEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructorValues()
	{
		$param = new TPagedListFetchDataEventParameter(3, 30, 10);
		self::assertSame(3, $param->getNewPageIndex());
		self::assertSame(30, $param->getOffset());
		self::assertSame(10, $param->getLimit());
		self::assertNull($param->getData());
	}

	public function testData()
	{
		$param = new TPagedListFetchDataEventParameter(0, 0, 2);
		$param->setData(['a', 'b']);
		self::assertSame(['a', 'b'], $param->getData());
		$param->Data = null;
		self::assertNull($param->getData());
	}
}
