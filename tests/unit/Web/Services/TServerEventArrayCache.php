<?php

namespace Prado\Test\Unit\Web\Services;

use Prado\Caching\ICache;

/**
 * An ICache over a shared array, standing in for a cache that several processes share.
 * Records the expire of every set() and counts the get() calls.
 */
class TServerEventArrayCache implements ICache
{
	public array $values = [];
	public array $expires = [];
	public int $gets = 0;

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function get($id)
	{
		$this->gets++;
		return $this->values[$id] ?? false;
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
	}
}
