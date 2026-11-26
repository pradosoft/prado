<?php

namespace Prado\Test\Unit\Data\DbSpecific\SqlSrv;

use Prado\Data\TDbDriver;
use Prado\Test\Unit\Harness\Traits\TDbCronManagerDriverTestTrait;

/**
 * Runs {@see \Prado\Util\Cron\TDbCronManager} on a live SQL Server server.
 */
class TDbCronManagerSqlSrvIntegrationTest extends \PHPUnit\Framework\TestCase
{
	use TDbCronManagerDriverTestTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_SQLSRV;
	}
}
