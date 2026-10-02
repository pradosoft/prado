<?php

namespace Prado\Test\Unit\Data\SqlMap\Configuration;

use PHPUnit\Framework\TestCase;
use Prado\Data\SqlMap\Configuration\TResultMap;
use Prado\Data\SqlMap\DataMapper\TSqlMapException;
use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;
use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry;

class TResultMapTest extends TestCase
{
	public function testCreateInstanceWrapsTypeHandlerException(): void
	{
		$handler = new class () extends TSqlMapTypeHandler {
			public function getParameter($object)
			{
				return $object;
			}

			public function getResult($string)
			{
				return $string;
			}

			public function createNewInstance($row = null)
			{
				throw new TSqlMapException('sqlmap_unable_to_find_class', 'missing-class');
			}
		};
		$handler->setType('failing-type');

		$registry = new TSqlMapTypeHandlerRegistry();
		$registry->registerTypeHandler($handler);

		$resultMap = new TResultMap();
		$resultMap->setClass('failing-type');
		$resultMap->setID('failing-map');

		try {
			$resultMap->createInstanceOfResult($registry);
			self::fail('Expected the type handler exception to be wrapped.');
		} catch (TSqlMapException $exception) {
			self::assertStringStartsWith('Unable to create a new instance', $exception->getMessage());
			self::assertStringContainsString('failing-type', $exception->getMessage());
			self::assertStringContainsString($handler::class, $exception->getMessage());
			self::assertStringContainsString('failing-map', $exception->getMessage());
		}
	}
}
