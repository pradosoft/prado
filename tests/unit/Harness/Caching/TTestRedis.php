<?php

/**
 * TTestRedis class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

/**
 * TTestRedis is a `\Redis` that records its calls and keeps values in an array, so
 * {@see \Prado\Caching\TRedisCache} runs without a server. It needs the `redis`
 * extension; load it only after checking `extension_loaded('redis')`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestRedis extends \Redis
{
	/** @var array<int, array> the recorded calls, each `[method, ...arguments]` */
	public array $calls = [];
	/** @var array<string, mixed> the stored values by key */
	public array $data = [];

	public function connect(string $host, int $port = 6379, float $timeout = 0, ?string $persistent_id = null, int $retry_interval = 0, float $read_timeout = 0, ?array $context = null): bool
	{
		$this->calls[] = func_num_args() === 1 ? ['connect', $host] : ['connect', $host, $port];
		return true;
	}

	public function setOption(int $option, mixed $value): bool
	{
		$this->calls[] = ['setOption', $option, $value];
		return true;
	}

	public function select(int $db): \Redis|bool
	{
		$this->calls[] = ['select', $db];
		return true;
	}

	public function get(string $key): mixed
	{
		return $this->data[$key] ?? false;
	}

	public function set(string $key, mixed $value, mixed $options = null): \Redis|string|bool
	{
		$this->calls[] = ['set', $key, $options];
		if (is_array($options) && in_array('nx', $options, true) && array_key_exists($key, $this->data)) {
			return false;
		}
		$this->data[$key] = $value;
		return true;
	}

	public function delete(array|string $key, string ...$other_keys): \Redis|int|false
	{
		$this->calls[] = ['delete', $key];
		$found = array_key_exists($key, $this->data) ? 1 : 0;
		unset($this->data[$key]);
		return $found;
	}

	public function flushDB(?bool $sync = null): \Redis|bool
	{
		$this->calls[] = ['flushDB'];
		$this->data = [];
		return true;
	}

	public function close(): bool
	{
		$this->calls[] = ['close'];
		return true;
	}
}
