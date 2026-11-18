<?php

/**
 * TDbCacheSqliteTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Data\DbSpecific\Sqlite\Caching;

use Prado\Data\TDbDriver;
use Prado\Test\Unit\Harness\Traits\TDbCacheDriverTestTrait;

/**
 * Runs {@see \Prado\Caching\TDbCache} on an SQLite database.
 */
class TDbCacheSqliteTest extends \PHPUnit\Framework\TestCase
{
	use TDbCacheDriverTestTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_SQLITE;
	}
}
