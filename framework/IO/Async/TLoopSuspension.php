<?php

/**
 * TLoopSuspension class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Revolt\EventLoop\Suspension;

/**
 * TLoopSuspension class.
 *
 * TLoopSuspension is the {@see Suspension} of an {@see ITickableEventLoop}.  It gives a fiber
 * the wait pattern of {@see IEventLoop}: register a one-shot callback that calls
 * {@see resume()} or {@see throw()}, then call {@see suspend()}.
 *
 * Fiber → suspend behavior:
 *  - a worker fiber → {@see \Fiber::suspend()}; {@see resume()} re-enters it from a
 *    {@see IEventLoop::defer() deferred} callback of the loop.
 *  - the main fiber → {@see ITickableEventLoop::tick() ticks} the loop until {@see resume()}
 *    or {@see throw()} is called; a loop that {@see ITickableEventLoop::isEmpty() empties}
 *    first throws an {@see \Error}, as nothing can resume the suspension.
 *
 * A suspension serves its fiber for the fiber's lifetime: {@see suspend()} follows every
 * {@see resume()}, and a second {@see suspend()} before the resume, or a {@see resume()}
 * without a pending suspend, throws an {@see \Error}.  {@see \Prado\IO\Socket\TSocketReactor}
 * creates one per fiber from {@see IEventLoop::suspension()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLoopSuspension implements Suspension
{
	/** @var ITickableEventLoop The loop that resumes the suspension. */
	private ITickableEventLoop $_loop;

	/** @var ?\WeakReference<\Fiber> The owning fiber, or null for the main fiber. */
	private ?\WeakReference $_fiber;

	/** @var bool Whether {@see suspend()} is waiting. */
	private bool $_pending = false;

	/** @var bool Whether the main fiber's wait has its value. */
	private bool $_resolved = false;

	/** @var mixed The value delivered to a main-fiber wait. */
	private mixed $_value = null;

	/** @var ?\Throwable The exception delivered to a main-fiber wait. */
	private ?\Throwable $_throwable = null;

	/**
	 * Binds the suspension to the loop and to the fiber that creates it.
	 * @param ITickableEventLoop $loop The loop that resumes the suspension.
	 */
	public function __construct(ITickableEventLoop $loop)
	{
		$this->_loop = $loop;
		$fiber = \Fiber::getCurrent();
		$this->_fiber = $fiber === null ? null : \WeakReference::create($fiber);
	}

	/**
	 * Resumes the suspended fiber with a value.
	 * @param mixed $value The value {@see suspend()} returns.
	 * @throws \Error When no {@see suspend()} is pending.
	 */
	public function resume(mixed $value = null): void
	{
		$this->settle($value, null);
	}

	/**
	 * Resumes the suspended fiber by throwing an exception from {@see suspend()}.
	 * @param \Throwable $throwable The exception {@see suspend()} throws.
	 * @throws \Error When no {@see suspend()} is pending.
	 */
	public function throw(\Throwable $throwable): void
	{
		$this->settle(null, $throwable);
	}

	/**
	 * Suspends the current fiber until {@see resume()} or {@see throw()} is called.
	 * @throws \Error When called from another fiber, when a suspend is pending, or when the loop
	 *   empties before the suspension is resumed.
	 * @throws \Throwable The exception passed to {@see throw()}.
	 * @return mixed The value passed to {@see resume()}.
	 */
	public function suspend(): mixed
	{
		if ($this->_pending) {
			throw new \Error('Must call resume() or throw() before calling suspend() again');
		}
		$fiber = $this->_fiber?->get();
		if ($fiber !== \Fiber::getCurrent()) {
			throw new \Error('Must not call suspend() from another fiber');
		}
		$this->_pending = true;
		$this->_resolved = false;
		if ($fiber !== null) {
			return \Fiber::suspend();
		}
		return $this->waitInMain();
	}

	/**
	 * Ticks the loop from the main fiber until {@see resume()} or {@see throw()} settles the wait,
	 * then returns the value or throws the exception.
	 * @throws \Error When the loop empties before the suspension is resumed.
	 * @throws \Throwable The exception passed to {@see throw()}.
	 * @return mixed The value passed to {@see resume()}.
	 */
	private function waitInMain(): mixed
	{
		try {
			while (!$this->_resolved) {
				if ($this->_loop->isEmpty()) {
					throw new \Error('The event loop emptied without resuming the suspension');
				}
				$this->_loop->tick(null);
			}
		} finally {
			$this->_pending = false;
		}
		$throwable = $this->_throwable;
		$value = $this->_value;
		$this->_throwable = null;
		$this->_value = null;
		if ($throwable !== null) {
			throw $throwable;
		}
		return $value;
	}

	/**
	 * Indicates whether {@see suspend()} is waiting.
	 * @return bool Whether a suspend is pending.
	 */
	public function isPending(): bool
	{
		return $this->_pending;
	}

	/**
	 * Delivers a value or an exception to the pending {@see suspend()}.  A worker fiber is
	 * re-entered from a deferred loop callback; the main fiber picks the result up as its tick
	 * returns.
	 * @param mixed $value The value to return from {@see suspend()}.
	 * @param ?\Throwable $throwable The exception to throw from {@see suspend()}.
	 * @throws \Error When no {@see suspend()} is pending.
	 */
	private function settle(mixed $value, ?\Throwable $throwable): void
	{
		if (!$this->_pending) {
			throw new \Error('Must call suspend() before calling resume()');
		}
		$fiber = $this->_fiber?->get();
		if ($fiber === null) {
			$this->_value = $value;
			$this->_throwable = $throwable;
			$this->_resolved = true;
			return;
		}
		$this->_pending = false;
		$this->_loop->defer(static function () use ($fiber, $value, $throwable): void {
			if (!$fiber->isSuspended()) {
				return;
			}
			if ($throwable !== null) {
				$fiber->throw($throwable);
			} else {
				$fiber->resume($value);
			}
		});
	}
}
