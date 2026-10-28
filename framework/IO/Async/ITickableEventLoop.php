<?php

/**
 * ITickableEventLoop interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

/**
 * ITickableEventLoop interface.
 *
 * ITickableEventLoop is the optional capability of an {@see IEventLoop} that runs one iteration
 * on demand.  {@see tick()} is a test affordance: it waits for the next event or the timeout,
 * runs what is due, and returns a ready count.  Production code waits through a
 * {@see IEventLoop::suspension() Suspension} instead; a `tick()` loop is not a wait.
 *
 * {@see \Prado\IO\Socket\TSocketReactor} implements this interface.
 * {@see \Prado\IO\Async\TRevoltEventLoop} does not, as the Revolt driver has no public
 * single-iteration entry point.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface ITickableEventLoop extends IEventLoop
{
	/**
	 * Runs one loop iteration: waits for readiness up to the timeout or the next timer, whichever
	 * is sooner, then runs the due callbacks.  A loop that {@see isEmpty() is empty} returns at
	 * once when the timeout is null.
	 * @param ?float $timeout The maximum seconds to wait; null waits for the next event.
	 * @return int The number of ready streams, or 0 on timeout.
	 */
	public function tick(?float $timeout = null): int;

	/**
	 * Indicates whether no enabled callback remains, so {@see IEventLoop::run() run()} returns.
	 * @return bool Whether the loop has nothing to run.
	 */
	public function isEmpty(): bool;
}
