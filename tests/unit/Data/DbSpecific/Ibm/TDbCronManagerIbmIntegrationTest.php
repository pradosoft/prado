<?php

namespace Prado\Test\Unit\Data\DbSpecific\Ibm;

use Prado\Data\TDbDriver;
use Prado\Test\Unit\Harness\Traits\TDbCronManagerDriverTestTrait;

/**
 * Runs {@see \Prado\Util\Cron\TDbCronManager} on a live IBM DB2 server.
 */
class TDbCronManagerIbmIntegrationTest extends \PHPUnit\Framework\TestCase
{
	use TDbCronManagerDriverTestTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_IBM;
	}
}
