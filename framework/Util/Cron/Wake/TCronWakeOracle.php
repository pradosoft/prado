<?php

/**
 * TCronWakeOracle class file.
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
 * TCronWakeOracle class.
 *
 * TCronWakeOracle is the {@see \Prado\Util\Cron\Wake\ICronWakeTransport} over Oracle
 * `DBMS_ALERT`: {@see signal()} runs `DBMS_ALERT.SIGNAL` on the {@see setAlert Alert} and commits,
 * and the waiting cron process holds a connection of its own that is registered for the alert
 * and blocks in `DBMS_ALERT.WAITONE`.  Every host that shares the database receives the wake, with
 * no shared file system or cache.  The user needs `EXECUTE` on `DBMS_ALERT`.
 *
 * A signal is delivered when its transaction commits, so {@see signal()} runs on a connection of
 * the transport's own, opened from the credentials of the {@see setConnectionID ConnectionID}
 * connection, and never commits the application's transaction.  The listening connection is
 * separate for the same reason, and because the cron daemon closes the application's connection
 * while it waits.  Several signals before a wait coalesce into one.  {@see wait()} blocks in
 * `WAITONE` for the remaining whole seconds; {@see watch()} polls it with a zero timeout every
 * {@see setPollInterval PollInterval} on the loop.
 *
 * ```xml
 * <module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakeOracle" ConnectionID="db" PollInterval="0.25" />
 * <module id="cron" class="Prado\Util\Cron\TDbCronManager" ConnectionID="db" WakeTransport="cronwake" Daemon="true" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeOracle extends TModule implements ICronWakeTransport, IDbModule
{
	use TDbPropertiesTrait {
		getDbConnection as private getConfiguredDbConnection;
	}

	/** The default alert name. */
	public const DEFAULT_ALERT = 'PRADO_CRON_WAKE';

	/** The longest alert name `DBMS_ALERT` accepts. */
	public const MAX_ALERT_LENGTH = 30;

	/** The default seconds between polls of the alert in {@see watch()}. */
	public const DEFAULT_POLL_INTERVAL = 1.0;

	/** @var ?string the alert name; null for {@see DEFAULT_ALERT} */
	private ?string $_alert = null;

	/** @var ?float the seconds between polls of the alert in {@see watch()}; null for {@see DEFAULT_POLL_INTERVAL} */
	private ?float $_pollInterval = null;

	/** @var ?IDataConnection the connection set directly; null uses ConnectionID */
	private ?IDataConnection $_connection = null;

	/** @var ?IDataConnection the registered listening connection of the waiting side */
	private ?IDataConnection $_listener = null;

	/** @var ?IDataConnection the signaling connection, whose commits are its own */
	private ?IDataConnection $_signaler = null;

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;

	/**
	 * @param ?IDataConnection $connection the connection whose credentials the transport's connections take, or null for {@see getConnectionID ConnectionID}
	 */
	public function __construct(?IDataConnection $connection = null)
	{
		$this->_connection = $connection;
		parent::__construct();
	}

	/**
	 * Initializes the module.  A property left unset takes its class constant through `static::`, so a
	 * subclass overrides a default by redefining {@see DEFAULT_ALERT} or {@see DEFAULT_POLL_INTERVAL}.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		$this->_alert ??= static::DEFAULT_ALERT;
		$this->_pollInterval ??= (float) static::DEFAULT_POLL_INTERVAL;
		parent::init($config);
	}

	/**
	 * @return string the alert name, an SQL identifier of at most {@see MAX_ALERT_LENGTH} characters; default {@see DEFAULT_ALERT}
	 */
	public function getAlert(): string
	{
		return $this->_alert ?? static::DEFAULT_ALERT;
	}

	/**
	 * @param string $value the alert name, an SQL identifier of at most {@see MAX_ALERT_LENGTH} characters, case-insensitive
	 * @throws TInvalidDataValueException when the name is not an SQL identifier or is too long
	 * @return static the current object
	 */
	public function setAlert($value): static
	{
		$value = $this->ensureSqlIdentifier(TPropertyValue::ensureString($value), 'Alert');
		if (strlen($value) > static::MAX_ALERT_LENGTH) {
			throw new TInvalidDataValueException('cronwakeoracle_alert_invalid', $value, static::MAX_ALERT_LENGTH);
		}
		$this->_alert = $value;
		return $this;
	}

	/**
	 * @return float the seconds between polls of the alert in {@see watch()}, default 1.0
	 */
	public function getPollInterval(): float
	{
		return $this->_pollInterval ?? (float) static::DEFAULT_POLL_INTERVAL;
	}

	/**
	 * @param float|string $value the seconds between polls of the alert in {@see watch()}
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
	 * Returns the connection whose credentials the transport's own connections take: the connection
	 * set directly, activated, or the {@see getConnectionID ConnectionID} connection.  The connection
	 * must be Oracle.
	 * @throws TConfigurationException when the connection's driver is not Oracle
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
		if ($driver !== TDbDriver::DRIVER_OCI) {
			throw new TConfigurationException('cronwakeoracle_driver_invalid', $driver);
		}
		return $connection;
	}

	/**
	 * @param ?IDataConnection $value the connection whose credentials the transport's connections take; null uses {@see getConnectionID ConnectionID}
	 * @return static the current object
	 */
	public function setDbConnection(?IDataConnection $value): static
	{
		$this->_connection = $value;
		return $this;
	}

	/**
	 * @return bool whether the `pdo_oci` extension is loaded
	 */
	protected function getIsAvailable(): bool
	{
		return \extension_loaded('pdo_oci');
	}

	/**
	 * Opens a connection of the transport's own with the credentials of {@see getDbConnection}.
	 * @throws TConfigurationException when the source connection is not a TDbConnection
	 * @return IDataConnection the open connection
	 */
	protected function newConnection(): IDataConnection
	{
		$source = $this->getDbConnection();
		if (!($source instanceof TDbConnection)) {
			throw new TConfigurationException('cronwakeoracle_driver_invalid', $source::class);
		}
		$connection = new TDbConnection($source->getConnectionString(), $source->getUsername(), $source->getPassword(), $source->getCharset());
		$connection->setActive(true);
		return $connection;
	}

	/**
	 * Returns the listening connection, registered for the alert.
	 * @return IDataConnection the listening connection
	 */
	protected function getListener(): IDataConnection
	{
		if ($this->_listener === null || !$this->_listener->getActive()) {
			$listener = $this->newConnection();
			$command = $listener->createCommand('BEGIN DBMS_ALERT.REGISTER(:name); END;');
			$command->bindValue(':name', $this->getAlert());
			$command->execute();
			$this->_listener = $listener;
		}
		return $this->_listener;
	}

	/**
	 * Returns the signaling connection.
	 * @return IDataConnection the signaling connection
	 */
	protected function getSignaler(): IDataConnection
	{
		if ($this->_signaler === null || !$this->_signaler->getActive()) {
			$this->_signaler = $this->newConnection();
		}
		return $this->_signaler;
	}

	/**
	 * Waits for the alert on the listening connection.  `WAITONE` returns status 0 when the alert
	 * was signaled and 1 at the timeout.
	 * @param int $seconds the whole seconds to wait; 0 returns at once
	 * @return bool whether the alert was signaled
	 */
	protected function waitOne(int $seconds): bool
	{
		$status = 1;
		$command = $this->getListener()->createCommand(
			'DECLARE message VARCHAR2(1800); status INTEGER; BEGIN DBMS_ALERT.WAITONE(:name, message, status, :timeout); :status := status; END;'
		);
		$command->bindValue(':name', $this->getAlert());
		$command->bindValue(':timeout', $seconds, \PDO::PARAM_INT);
		$command->bindParameter(':status', $status, \PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT, 10);
		$command->execute();
		return (int) $status === 0;
	}

	/**
	 * Signals the alert and commits on the signaling connection.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether the alert was signaled
	 */
	public function signal(float $now): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		$command = $this->getSignaler()->createCommand('BEGIN DBMS_ALERT.SIGNAL(:name, :message); COMMIT; END;');
		$command->bindValue(':name', $this->getAlert());
		$command->bindValue(':message', (string) $now);
		$command->execute();
		return true;
	}

	/**
	 * Waits for the alert until the timestamp passes.  A signal received since the last wait is
	 * consumed at once.  The remaining time is rounded up to whole seconds, the resolution of
	 * `WAITONE`.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when the alert was signaled, false when the wait timed out, null when unavailable
	 */
	public function wait(float $until, float $now): ?bool
	{
		if (!$this->getIsAvailable()) {
			return null;
		}
		return $this->waitOne((int) max(0, ceil($until - $now)));
	}

	/**
	 * Polls the alert with a zero timeout every {@see getPollInterval PollInterval} on the loop and
	 * calls back when it was signaled.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each signal
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
			if ($this->waitOne(0)) {
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
	 * Cancels the watcher, removes the alert registration, and closes the transport's connections.
	 */
	public function close(): void
	{
		$this->unwatch();
		if ($this->_listener !== null) {
			if ($this->_listener->getActive()) {
				$command = $this->_listener->createCommand('BEGIN DBMS_ALERT.REMOVE(:name); END;');
				$command->bindValue(':name', $this->getAlert());
				$command->execute();
				$this->_listener->setActive(false);
			}
			$this->_listener = null;
		}
		if ($this->_signaler !== null) {
			$this->_signaler->setActive(false);
			$this->_signaler = null;
		}
	}

	/**
	 * @return string the error message key when the ConnectionID is not a TDataSourceConfig
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'cronwakeoracle_connectionid_invalid';
	}
}
