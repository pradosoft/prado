<?php

namespace Prado\Test\Unit\Data\DbSpecific\Firebird;

use Prado\Data\TDbDriver;
use Prado\Test\Unit\Harness\Traits\TDbCronManagerDriverTestTrait;

/**
 * Runs {@see \Prado\Util\Cron\TDbCronManager} on a live Firebird server.
 */
class TDbCronManagerFirebirdIntegrationTest extends \PHPUnit\Framework\TestCase
{
	use TDbCronManagerDriverTestTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_FIREBIRD;
	}
}
