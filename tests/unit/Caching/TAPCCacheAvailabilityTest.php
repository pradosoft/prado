<?php

/**
 * TAPCCacheAvailabilityTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TAPCCache;
use Prado\Exceptions\TConfigurationException;

/**
 * Tests that TAPCCache initializes, or names why it cannot, in the environment the test runs in.
 */
class TAPCCacheAvailabilityTest extends \PHPUnit\Framework\TestCase
{
	public function testInitSucceedsOrNamesWhyAPCuIsUnavailable(): void
	{
		$expected = match (true) {
			!extension_loaded('apcu') => 'apccache_extension_required',
			!ini_get('apc.enabled') => 'apccache_extension_not_enabled',
			str_starts_with(php_sapi_name(), 'cli') && !ini_get('apc.enable_cli') => 'apccache_extension_not_enabled_cli',
			default => null,
		};
		$this->assertSame($expected === null, TAPCCache::getIsAvailable());

		$cache = new TAPCCache();
		$cache->setPrimaryCache(false);
		try {
			$cache->init(null);
			$this->assertNull($expected, 'init() succeeded while APCu is unavailable.');
		} catch (TConfigurationException $e) {
			$this->assertSame($expected, $e->getErrorCode());
		}
	}
}
