<?php

/**
 * TCronWakePoll class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron\Wake;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TAsync;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TCronWakePoll class.
 *
 * TCronWakePoll is the base of the {@see \Prado\Util\Cron\Wake\ICronWakeTransport} transports that
 * signal through a shared stamp and detect a wake by polling it: a signal {@see writeStamp()}
 * writes a new stamp, and the waiting side compares {@see readStamp()} every
 * {@see setPollInterval PollInterval} seconds with the stamp it last saw.  A stamp medium that
 * several hosts share, a file system or a cache, carries the wake between hosts.  The transports
 * are modules, so an application configures one and names it in
 * {@see \Prado\Util\Cron\TCronModule::setWakeTransport WakeTransport}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TCronWakePoll extends TModule implements ICronWakeTransport
{
	/** The default seconds between polls of the stamp. */
	public const DEFAULT_POLL_INTERVAL = 1.0;

	/** @var ?float the seconds between polls of the stamp; null for {@see DEFAULT_POLL_INTERVAL} */
	private ?float $_pollInterval = null;

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;

	/** @var mixed the stamp last seen by the watcher */
	private mixed $_seen = null;

	/**
	 * Reads the current stamp.  Two reads without a {@see writeStamp()} between them return equal
	 * values; a read after a write returns a different value.
	 * @return mixed the current stamp, or null when there is none
	 */
	abstract protected function readStamp(): mixed;

	/**
	 * Writes a new stamp, the wake.
	 * @return bool whether the stamp was written
	 */
	abstract protected function writeStamp(): bool;

	/**
	 * @return bool whether the stamp medium is available
	 */
	abstract protected function getIsAvailable(): bool;

	/**
	 * Initializes the module.  A property left unset takes its class constant through `static::`, so a
	 * subclass overrides the default by redefining {@see DEFAULT_POLL_INTERVAL}.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		$this->_pollInterval ??= (float) static::DEFAULT_POLL_INTERVAL;
		parent::init($config);
	}

	/**
	 * @return float the seconds between polls of the stamp, default {@see DEFAULT_POLL_INTERVAL}
	 */
	public function getPollInterval(): float
	{
		return $this->_pollInterval ?? (float) static::DEFAULT_POLL_INTERVAL;
	}

	/**
	 * @param float|string $value the seconds between polls of the stamp
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
	 * Writes a new stamp.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether the stamp was written
	 */
	public function signal(float $now): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		return $this->writeStamp();
	}

	/**
	 * Polls the stamp until it changes or the timestamp passes.  The remaining time is measured on
	 * the real clock from `$now`.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when the stamp changed, false when the wait timed out, null when unavailable
	 */
	public function wait(float $until, float $now): ?bool
	{
		if (!$this->getIsAvailable()) {
			return null;
		}
		$seen = $this->readStamp();
		$remaining = $until - $now;
		$start = \microtime(true);
		while ($remaining > 0) {
			TAsync::sleep(min($remaining, $this->getPollInterval()));
			if ($this->readStamp() !== $seen) {
				return true;
			}
			$remaining = ($until - $now) - (\microtime(true) - $start);
		}
		return false;
	}

	/**
	 * Polls the stamp every {@see getPollInterval PollInterval} on the loop and calls back on a change.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each change of the stamp
	 * @return bool whether the transport is watching
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		$this->unwatch();
		$this->_seen = $this->readStamp();
		$this->_loop = $loop;
		$this->_watcher = $loop->repeat($this->getPollInterval(), function () use ($onWake) {
			$stamp = $this->readStamp();
			if ($stamp !== $this->_seen) {
				$this->_seen = $stamp;
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
	 * Cancels the polling loop callback.
	 */
	public function close(): void
	{
		$this->unwatch();
	}
}
