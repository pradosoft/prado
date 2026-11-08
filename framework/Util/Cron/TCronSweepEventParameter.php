<?php

/**
 * TCronSweepEventParameter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\TEventParameter;
use Prado\TPropertyValue;

/**
 * TCronSweepEventParameter class.
 *
 * TCronSweepEventParameter is the parameter of the {@see \Prado\Util\Cron\TCronModule} sweep events
 * {@see \Prado\Util\Cron\TCronModule::onSweepStart onSweepStart} and
 * {@see \Prado\Util\Cron\TCronModule::onSweepEnd onSweepEnd}.  A sweep is one pass of
 * {@see \Prado\Util\Cron\TCronModule::processPendingTasks processPendingTasks}: the pending tasks
 * are collected, then run.
 *
 * | Property | Set for | Meaning |
 * |---|---|---|
 * | `Tasks` | start, end | the pending {@see \Prado\Util\Cron\TCronTask} objects by name; a start handler removes, adds, reorders, or edits them before they run |
 * | `Daemon` | start, end | whether the sweep runs inside the cron daemon |
 * | `Results` | end | whether each task ran without an exception, by name |
 * | `Count` | start, end | the number of tasks |
 * | `Failed` | end | the number of tasks that threw |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronSweepEventParameter extends TEventParameter
{
	/** @var array<string, TCronTask> the tasks of the sweep, by name */
	private array $_tasks = [];

	/** @var bool whether the sweep runs inside the cron daemon */
	private bool $_daemon = false;

	/** @var array<string, bool> whether each task ran without an exception, by name */
	private array $_results = [];

	/**
	 * @param array<string, TCronTask> $tasks the pending tasks, by name
	 * @param bool $daemon whether the sweep runs inside the cron daemon
	 */
	public function __construct(array $tasks = [], bool $daemon = false)
	{
		$this->_tasks = $tasks;
		$this->_daemon = $daemon;
		parent::__construct();
	}

	/**
	 * @return array<string, TCronTask> the tasks of the sweep, by name
	 */
	public function getTasks(): array
	{
		return $this->_tasks;
	}

	/**
	 * Replaces the tasks of the sweep.  An `onSweepStart` handler removes a task from the pass, adds
	 * one, or reorders them; a task object's properties are edited in place.
	 * @param iterable<TCronTask> $tasks the tasks to run
	 * @return static the current object
	 */
	public function setTasks(iterable $tasks): static
	{
		$this->_tasks = [];
		foreach ($tasks as $task) {
			$this->_tasks[$task->getName()] = $task;
		}
		return $this;
	}

	/**
	 * @return int the number of tasks of the sweep
	 */
	public function getCount(): int
	{
		return count($this->_tasks);
	}

	/**
	 * @return bool whether the sweep runs inside the cron daemon
	 */
	public function getDaemon(): bool
	{
		return $this->_daemon;
	}

	/**
	 * @param bool $value whether the sweep runs inside the cron daemon
	 * @return static the current object
	 */
	public function setDaemon($value): static
	{
		$this->_daemon = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return array<string, bool> whether each task ran without an exception, by name; set for `onSweepEnd`
	 */
	public function getResults(): array
	{
		return $this->_results;
	}

	/**
	 * @param array<string, bool> $value whether each task ran without an exception, by name
	 * @return static the current object
	 */
	public function setResults(array $value): static
	{
		$this->_results = $value;
		return $this;
	}

	/**
	 * @return int the number of tasks that threw
	 */
	public function getFailed(): int
	{
		return count(array_filter($this->_results, static fn ($ok) => !$ok));
	}
}
