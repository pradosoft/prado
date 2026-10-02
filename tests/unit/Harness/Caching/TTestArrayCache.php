<?php

/**
 * TTestArrayCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

use Prado\Caching\ICache;

/**
 * TTestArrayCache is an in-memory {@see ICache} for unit tests.
 *
 * {@see add()} stores a value only when the key is absent, as the shared caches do.
 * {@see $values} holds the stored values and {@see $expires} the expiry passed with each.
 * Entries do not expire.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestArrayCache implements ICache
{
	/** @var array<string, mixed> the stored values by key. */
	public array $values = [];

	/** @var array<string, int> the expiry passed with each stored key. */
	public array $expires = [];

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function get($id)
	{
		return array_key_exists($id, $this->values) ? $this->values[$id] : false;
	}

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->values[$id] = $value;
		$this->expires[$id] = $expire;
		return true;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		if (array_key_exists($id, $this->values)) {
			return false;
		}
		return $this->set($id, $value, $expire, $dependency);
	}

	public function delete($id)
	{
		unset($this->values[$id], $this->expires[$id]);
		return true;
	}

	public function flush()
	{
		$this->values = [];
		$this->expires = [];
		return true;
	}
}
