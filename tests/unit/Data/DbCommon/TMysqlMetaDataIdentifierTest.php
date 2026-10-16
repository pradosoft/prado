<?php

namespace Prado\Test\Unit\Data\DbCommon;

use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

class TMysqlMetaDataIdentifierTest extends \PHPUnit\Framework\TestCase
{
	public function testIsValidIdentifier(): void
	{
		$meta = new TMysqlMetaData(new TDbConnection('sqlite::memory:'));
		foreach (['accounts', 'my_table', 'my table', 'tbl$1', 'a|b'] as $name) {
			$this->assertTrue(PradoUnit::invoke($meta, 'isValidIdentifier', $name), $name);
		}
		foreach (['a/b', 'a\\b', 'a.b', "a\x00b", "a\xFFb"] as $name) {
			$this->assertFalse(PradoUnit::invoke($meta, 'isValidIdentifier', $name), json_encode($name, JSON_INVALID_UTF8_SUBSTITUTE));
		}
	}

	public function testGetSchemaTableNameRejectsBackslash(): void
	{
		$meta = new TMysqlMetaData(new TDbConnection('sqlite::memory:'));
		$this->assertSame(['db', 'accounts'], PradoUnit::invoke($meta, 'getSchemaTableName', '`db`.`accounts`'));
		$this->expectException(TDbException::class);
		PradoUnit::invoke($meta, 'getSchemaTableName', 'acc\\ounts');
	}
}
