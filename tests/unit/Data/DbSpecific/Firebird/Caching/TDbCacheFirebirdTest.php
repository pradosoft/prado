<?php

/**
 * TDbCacheFirebirdTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Data\DbSpecific\Firebird\Caching;

use Prado\Data\TDbDriver;
use Prado\Test\Unit\Harness\Traits\TDbCacheDriverTestTrait;

/**
 * Runs {@see \Prado\Caching\TDbCache} on a live Firebird server.
 */
class TDbCacheFirebirdTest extends \PHPUnit\Framework\TestCase
{
	use TDbCacheDriverTestTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_FIREBIRD;
	}
}
