<?php

/**
 * ICronWakeTransport interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\IO\Async\IEventLoop;

/**
 * ICronWakeTransport interface.
 *
 * ICronWakeTransport carries a wake from a process that schedules a task to the cron process
 * that is waiting for the next trigger, so the task runs on time instead of after the remaining
 * wait.  {@see \Prado\Util\Cron\TCronModule::signalWake()} calls {@see signal()}; a per-minute cron
 * process waits through {@see wait()}, and a cron daemon registers {@see watch()} callbacks on
 * its event loop.  A wake carries no data; the cron process re-polls its pending tasks.
 *
 * | Transport | Reach | Latency |
 * |---|---|---|
 * | {@see \Prado\Util\Cron\TCronWakeSocket} | processes of a host (`udg`, `udp`) | immediate |
 * | {@see \Prado\Util\Cron\TCronWakeFile} | hosts sharing the file system | `PollInterval` |
 * | {@see \Prado\Util\Cron\TCronWakeCache} | clients of the cache | `PollInterval` |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface ICronWakeTransport
{
	/**
	 * Sends a wake to the waiting cron process.  A missing listener is a harmless no-op.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether a wake was sent
	 */
	public function signal(float $now): bool;

	/**
	 * Waits until the given timestamp, returning early when a wake arrives.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when a wake arrived, false when the wait timed out, null when the transport
	 * is unavailable so the caller falls back to a plain sleep
	 */
	public function wait(float $until, float $now): ?bool;

	/**
	 * Registers the loop callbacks that deliver wakes to a cron daemon.  The callback runs from the
	 * loop on every wake until {@see unwatch()}.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called as `()` on each wake
	 * @return bool whether the transport is watching; false when it is unavailable
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool;

	/**
	 * Cancels the loop callbacks of {@see watch()}.
	 */
	public function unwatch(): void;

	/**
	 * Releases the transport's resources, such as a bound socket or file.
	 */
	public function close(): void;
}
