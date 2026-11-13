<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\TBehavior;

/**
 * Denies the cron permission checks named in {@see $deny} as a permissions manager does: each named
 * `dy` permission event answers true, and the others pass on along the call chain.
 */
class TTestCronDenyingBehavior extends TBehavior
{
	/** @var string[] the denied `dy` events */
	public array $deny = [];

	public function __construct(array $deny = [])
	{
		$this->deny = $deny;
		parent::__construct();
	}

	protected function check(string $event, array $args): mixed
	{
		if (in_array($event, $this->deny, true)) {
			return true;
		}
		$callchain = array_pop($args);
		return $callchain->$event(...$args);
	}

	public function dyRegisterShellAction(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyGetTasks(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyGetUserTasks(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyGetPendingTasks(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyRunTask(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyAddTask(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyAddTaskGlobal(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyUpdateTask(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyRemoveTask(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyClearCronLog(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyRemoveCronLogItem(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyGetCronLog(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}

	public function dyGetCronLogCount(...$args)
	{
		return $this->check(__FUNCTION__, $args);
	}
}
