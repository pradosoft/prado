<?php

/**
 * TRedisMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;

/**
 * TRedisMutex class.
 *
 * TRedisMutex locks a name in a Redis server with `SET key token NX PX expire`, and
 * releases it with a Lua script that deletes the key only when it holds this
 * mutex's token.  It excludes the clients of the server.  It needs the `redis`
 * extension.
 *
 * The lock is a lease of {@see getExpire Expire} seconds.  A holder that crashes
 * keeps it until it expires, and a holder whose work outlasts it loses it.  The key
 * of a name is `mutex:` and the name with the {@see getKeyPrefix KeyPrefix}.
 *
 * {@see setHost Host} and {@see setPort Port}, or {@see setSocket Socket}, select
 * the server; {@see setIndex Index} selects the database.  The connection opens in
 * {@see init()}.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TRedisMutex" Host="localhost" Port="6379" Expire="30" />
 * ```
 *
 * ```php
 * return [
 *     'modules' => [
 *         'mutex' => [
 *             'class' => 'Prado\Util\Mutex\TRedisMutex',
 *             'properties' => ['Socket' => '/var/run/redis/redis.sock', 'Index' => 1],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRedisMutex extends TMutex
{
	use TInitializedTrait;

	/** The default seconds a lock lasts. */
	public const DEFAULT_EXPIRE = 30;

	/** The Lua script that deletes KEYS[1] when it holds ARGV[1]. */
	public const RELEASE_SCRIPT = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";

	/** @var ?\Redis the Redis connection. */
	private ?object $_redis = null;

	/** @var string the host name of the server. */
	private string $_host = 'localhost';

	/** @var int the port of the server. */
	private int $_port = 6379;

	/** @var ?string the Unix socket of the server; null uses TCP. */
	private ?string $_socket = null;

	/** @var int the database index. */
	private int $_index = 0;

	/** @var float the seconds a lock lasts. */
	private float $_expire = self::DEFAULT_EXPIRE;

	/** @var array<string, string> the token of each held name. */
	private array $_tokens = [];

	/**
	 * @return bool whether the `redis` extension is loaded and the `\Redis` class exists.
	 */
	public static function getIsAvailable(): bool
	{
		return extension_loaded('redis') && class_exists('\Redis', false);
	}

	/**
	 * Releases the held names and closes the connection.
	 */
	public function __destruct()
	{
		parent::__destruct();
		$redis = $this->getRedisDirect();
		if ($redis instanceof \Redis) {
			$redis->close();
		}
	}

	/**
	 * Initializes the module and connects to the server.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration.
	 * @throws TConfigurationException when the `redis` extension is not loaded.
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
			throw new TConfigurationException('redismutex_extension_required');
		}
		$redis = $this->newRedis();
		if ($this->_socket !== null) {
			$redis->connect($this->getSocket());
		} else {
			$redis->connect($this->getHost(), $this->getPort());
		}
		$redis->select($this->getIndex());
		$this->setRedisDirect($redis);
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * Creates the Redis instance.  Override in a subclass to substitute another implementation.
	 * @return \Redis the new Redis instance.
	 */
	protected function newRedis(): object
	{
		return new \Redis();
	}

	/**
	 * @return ?\Redis the Redis connection, or null before initialization.
	 */
	protected function getRedisDirect(): ?object
	{
		return $this->_redis;
	}

	/**
	 * @param ?\Redis $value the Redis connection.
	 */
	protected function setRedisDirect(?object $value): void
	{
		$this->_redis = $value;
	}

	/**
	 * @return string the host name of the server. Default 'localhost'.
	 */
	public function getHost(): string
	{
		return $this->_host;
	}

	/**
	 * @param string $value the host name of the server.
	 * @throws \Prado\Exceptions\TInvalidOperationException when the module is initialized.
	 * @return static the current object.
	 */
	public function setHost($value): static
	{
		$this->assertUninitialized('Host');
		$this->_host = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return int the port of the server. Default 6379.
	 */
	public function getPort(): int
	{
		return $this->_port;
	}

	/**
	 * @param int|string $value the port of the server.
	 * @throws \Prado\Exceptions\TInvalidOperationException when the module is initialized.
	 * @return static the current object.
	 */
	public function setPort($value): static
	{
		$this->assertUninitialized('Port');
		$this->_port = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * @return ?string the Unix socket of the server. Default null, using TCP.
	 */
	public function getSocket(): ?string
	{
		return $this->_socket;
	}

	/**
	 * @param ?string $value the Unix socket of the server; it takes precedence over Host and Port.
	 * @throws \Prado\Exceptions\TInvalidOperationException when the module is initialized.
	 * @return static the current object.
	 */
	public function setSocket($value): static
	{
		$this->assertUninitialized('Socket');
		$this->_socket = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return int the database index. Default 0.
	 */
	public function getIndex(): int
	{
		return $this->_index;
	}

	/**
	 * @param int|string $value the database index.
	 * @throws \Prado\Exceptions\TInvalidOperationException when the module is initialized.
	 * @return static the current object.
	 */
	public function setIndex($value): static
	{
		$this->assertUninitialized('Index');
		$this->_index = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * @return float the seconds a lock lasts. Default 30.
	 */
	public function getExpire(): float
	{
		return $this->_expire;
	}

	/**
	 * @param float|int|string $value the seconds a lock lasts, to the millisecond.
	 * @throws TInvalidDataValueException when the value is less than 0.001.
	 * @return static the current object.
	 */
	public function setExpire($value): static
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value < 0.001) {
			throw new TInvalidDataValueException('mutex_expire_invalid', static::class, $value);
		}
		$this->_expire = $value;
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return string the Redis key of the name.
	 */
	public function getRedisKey(string $name): string
	{
		return 'mutex:' . $this->getLockKey($name);
	}

	/**
	 * Sets the key of the name to a random token when the key is absent.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$token = bin2hex(random_bytes(16));
		$options = ['nx', 'px' => (int) round($this->getExpire() * 1000)];
		if ($this->getRedisDirect()->set($this->getRedisKey($name), $token, $options) !== true) {
			return false;
		}
		$this->_tokens[$name] = $token;
		return true;
	}

	/**
	 * Deletes the key of the name when it holds this mutex's token, in one script.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released; false when the lease expired.
	 */
	protected function releaseLock(string $name): bool
	{
		$token = $this->_tokens[$name] ?? null;
		unset($this->_tokens[$name]);
		if ($token === null) {
			return false;
		}
		return (int) $this->getRedisDirect()->eval(self::RELEASE_SCRIPT, [$this->getRedisKey($name), $token], 1) === 1;
	}

	/**
	 * Drops the token in a forked child, leaving the parent's key.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		unset($this->_tokens[$name]);
	}
}
