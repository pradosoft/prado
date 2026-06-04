<?php

/**
 * TTestDbCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TDbCache;
use Prado\Data\TDbConnection;

/**
 * TTestDbCache is a {@see TDbCache} harness exposing its protected cache-initialization,
 * connection, and serialized-contract seams. The clock is fakeable via
 * {@see TTestCacheClockTrait}. No fake database is provided; tests that need a live
 * connection still skip when PDO/SQLite is unavailable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestDbCache extends TDbCache
{
	use TTestCacheClockTrait;

	public function pubGetIsCacheInitialized(): bool
	{
		return $this->getIsCacheInitialized();
	}

	public function pubSetIsCacheInitialized(bool $value): void
	{
		$this->setIsCacheInitialized($value);
	}

	public function pubInitializeCache($force = false)
	{
		return $this->initializeCache($force);
	}

	public function pubGetSqliteDatabaseName(): string
	{
		return $this->getSqliteDatabaseName();
	}

	public function pubGetCustomDbConnection(): ?TDbConnection
	{
		return $this->getCustomDbConnection();
	}

	public function pubGetDbConnectionActivationType(): ?bool
	{
		return $this->getDbConnectionActivationType();
	}

	public function pubGetSerializedValue(string $key): false|string
	{
		return $this->getSerializedValue($key);
	}

	public function pubSetSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->setSerializedValue($key, $value, $expire);
	}

	public function pubAddSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->addSerializedValue($key, $value, $expire);
	}

	public function pubDeleteValue(string $key): bool
	{
		return $this->deleteValue($key);
	}
}
