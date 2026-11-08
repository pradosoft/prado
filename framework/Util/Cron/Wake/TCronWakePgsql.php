<?php

/**
 * TCronWakePgsql class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron\Wake;

use Prado\Data\IDataConnection;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Data\TDbDriverCapabilities;
use Prado\Data\TDbPropertiesTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\IEventLoop;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Util\IDbModule;

/**
 * TCronWakePgsql class.
 *
 * TCronWakePgsql is the {@see \Prado\Util\Cron\Wake\ICronWakeTransport} over PostgreSQL
 * `LISTEN`/`NOTIFY`: {@see signal()} runs `NOTIFY` on the {@see setChannel Channel} through the
 * {@see setConnectionID ConnectionID} connection, and the waiting cron process holds a connection
 * of its own that runs `LISTEN` and receives the notifications.  Every host that shares the
 * database receives the wake, with no shared file system or cache.
 *
 * The listening connection is separate from the application's connection because the cron
 * daemon closes that one while it waits, and because a `LISTEN` belongs to its session.
 * {@see wait()} blocks in `PDO::pgsqlGetNotify()` for the remaining time; {@see watch()} polls it
 * every {@see setPollInterval PollInterval} on the loop.
 *
 * ```xml
 * <module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakePgsql" ConnectionID="db" PollInterval="0.25" />
 * <module id="cron" class="Prado\Util\Cron\TDbCronManager" ConnectionID="db" WakeTransport="cronwake" Daemon="true" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakePgsql extends TModule implements ICronWakeTransport, IDbModule
{
	use TDbPropertiesTrait {
		getDbConnection as private getConfiguredDbConnection;
	}

	/** The default notification channel. */
	public const DEFAULT_CHANNEL = 'prado_cron_wake';

	/** The default seconds between polls of the notifications in {@see watch()}. */
	public const DEFAULT_POLL_INTERVAL = 1.0;

	/** @var ?string the notification channel; null for {@see DEFAULT_CHANNEL} */
	private ?string $_channel = null;

	/** @var ?float the seconds between polls of the notifications in {@see watch()}; null for {@see DEFAULT_POLL_INTERVAL} */
	private ?float $_pollInterval = null;

	/** @var ?IDataConnection the connection set directly; null uses ConnectionID */
	private ?IDataConnection $_connection = null;

	/** @var ?TDbConnection the listening connection of the waiting side */
	private ?TDbConnection $_listener = null;

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;

	/**
	 * @param ?IDataConnection $connection the connection to notify through, or null for {@see getConnectionID ConnectionID}
	 */
	public function __construct(?IDataConnection $connection = null)
	{
		$this->_connection = $connection;
		parent::__construct();
	}

	/**
	 * Initializes the module.  A property left unset takes its class constant through `static::`, so a
	 * subclass overrides a default by redefining {@see DEFAULT_CHANNEL} or {@see DEFAULT_POLL_INTERVAL}.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		$this->_channel ??= static::DEFAULT_CHANNEL;
		$this->_pollInterval ??= (float) static::DEFAULT_POLL_INTERVAL;
		parent::init($config);
	}

	/**
	 * @return string the notification channel, an SQL identifier; default {@see DEFAULT_CHANNEL}
	 */
	public function getChannel(): string
	{
		return $this->_channel ?? static::DEFAULT_CHANNEL;
	}

	/**
	 * @param string $value the notification channel, an SQL identifier
	 * @throws TInvalidDataValueException when the channel is not an SQL identifier
	 * @return static the current object
	 */
	public function setChannel($value): static
	{
		$this->_channel = $this->ensureSqlIdentifier(TPropertyValue::ensureString($value), 'Channel');
		return $this;
	}

	/**
	 * @return float the seconds between polls of the notifications in {@see watch()}, default 1.0
	 */
	public function getPollInterval(): float
	{
		return $this->_pollInterval ?? (float) static::DEFAULT_POLL_INTERVAL;
	}

	/**
	 * @param float|string $value the seconds between polls of the notifications in {@see watch()}
	 * @throws TInvalidDataValueException when the interval is not greater than zero
	 * @return static the current object
	 */
	public function setPollInterval($value): static
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value <= 0) {
			throw new TInvalidDataValueException('cronwake_pollinterval_invalid', $value);
		}
		$this->_pollInterval = $value;
		return $this;
	}

	/**
	 * Returns the connection to notify through: the connection set directly, activated, or the
	 * {@see getConnectionID ConnectionID} connection.  The connection must be PostgreSQL.
	 * @throws TConfigurationException when the connection's driver is not PostgreSQL
	 * @return IDataConnection the connection
	 */
	public function getDbConnection(): IDataConnection
	{
		if ($this->_connection !== null) {
			if (!$this->_connection->getActive()) {
				$this->_connection->setActive(true);
			}
			$connection = $this->_connection;
		} else {
			$connection = $this->getConfiguredDbConnection();
		}
		$driver = TDbDriverCapabilities::normalizeDriver((string) $connection->getDriverName());
		if ($driver !== TDbDriver::DRIVER_PGSQL) {
			throw new TConfigurationException('cronwakepgsql_driver_invalid', $driver);
		}
		return $connection;
	}

	/**
	 * @param ?IDataConnection $value the connection to notify through; null uses {@see getConnectionID ConnectionID}
	 * @return static the current object
	 */
	public function setDbConnection(?IDataConnection $value): static
	{
		$this->_connection = $value;
		return $this;
	}

	/**
	 * @return bool whether the `pdo_pgsql` extension is loaded
	 */
	protected function getIsAvailable(): bool
	{
		return \extension_loaded('pdo_pgsql');
	}

	/**
	 * Returns the listening connection, a connection of this transport's own with the credentials
	 * of {@see getDbConnection}, with `LISTEN` on the channel.
	 * @return TDbConnection the listening connection
	 */
	protected function getListener(): TDbConnection
	{
		if ($this->_listener === null || !$this->_listener->getActive()) {
			$source = $this->getDbConnection();
			if (!($source instanceof TDbConnection)) {
				throw new TConfigurationException('cronwakepgsql_driver_invalid', $source::class);
			}
			$listener = new TDbConnection($source->getConnectionString(), $source->getUsername(), $source->getPassword(), $source->getCharset());
			$listener->setActive(true);
			$listener->createCommand('LISTEN ' . $this->getChannel())->execute();
			$this->_listener = $listener;
		}
		return $this->_listener;
	}

	/**
	 * Takes the notifications received so far, waiting up to the given milliseconds for the first.
	 * @param int $milliseconds the milliseconds to wait for a notification
	 * @return bool whether a notification was received
	 */
	protected function takeNotifications(int $milliseconds): bool
	{
		$pdo = $this->getListener()->getPdoInstance();
		if ($pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC, $milliseconds) === false) {
			return false;
		}
		while ($pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC, 0) !== false) {
			// drain the notifications queued behind the first
		}
		return true;
	}

	/**
	 * Runs `NOTIFY` on the channel.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether the notification was sent
	 */
	public function signal(float $now): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		$this->getDbConnection()->createCommand('NOTIFY ' . $this->getChannel())->execute();
		return true;
	}

	/**
	 * Waits for a notification until the timestamp passes.  Notifications received since the last
	 * wait are consumed at once.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when a notification arrived, false when the wait timed out, null when unavailable
	 */
	public function wait(float $until, float $now): ?bool
	{
		if (!$this->getIsAvailable()) {
			return null;
		}
		return $this->takeNotifications((int) max(0, round(($until - $now) * 1000)));
	}

	/**
	 * Polls the notifications every {@see getPollInterval PollInterval} on the loop and calls back
	 * when one arrived.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each notification
	 * @return bool whether the transport is watching
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		$this->unwatch();
		$this->getListener();
		$this->_loop = $loop;
		$this->_watcher = $loop->repeat($this->getPollInterval(), function () use ($onWake) {
			if ($this->takeNotifications(0)) {
				$onWake();
			}
		});
		return true;
	}

	/**
	 * Cancels the polling loop callback.
	 */
	public function unwatch(): void
	{
		if ($this->_watcher !== null && $this->_loop !== null) {
			$this->_loop->cancel($this->_watcher);
		}
		$this->_watcher = null;
		$this->_loop = null;
	}

	/**
	 * Cancels the watcher and closes the listening connection, which ends its `LISTEN`.
	 */
	public function close(): void
	{
		$this->unwatch();
		if ($this->_listener !== null) {
			$this->_listener->setActive(false);
			$this->_listener = null;
		}
	}

	/**
	 * @return string the error message key when the ConnectionID is not a TDataSourceConfig
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'cronwakepgsql_connectionid_invalid';
	}
}
