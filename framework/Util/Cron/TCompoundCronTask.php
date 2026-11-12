<?php

/**
 * TCompoundCronTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\IO\Async\TAsync;
use Prado\TPropertyValue;

/**
 * TCompoundCronTask class.
 *
 * TCompoundCronTask runs several tasks as one run: one claim, one result, one set of events and pings.
 * {@see getTasks Tasks} lists the steps, separated by ';' in configuration: a task as the `Task` of a
 * job is, a class name or `module->method(args)`, or `@name` for the work of a configured task of the
 * cron module.
 * ```xml
 * <job name="nightly" schedule="0 3 * * *" task="Prado\Util\Cron\TCompoundCronTask"
 *      Tasks="dbcache->flushCacheExpired(true); MyApp\ReportTask; @cleanup" />
 * ```
 *
 * The steps run in order in the task's fiber, as its user and with its
 * {@see getCancellationToken CancellationToken}.  A step that throws stops the rest with
 * {@see getStopOnFailure StopOnFailure} (the default); otherwise the rest run and the first exception
 * ends the run.  With {@see getParallel Parallel}, the steps run in fibers of their own at once, and
 * the first exception ends the run after all finish.  A step referenced by `@name` runs the task's
 * `execute()` only: its schedule, claim, and events are the compound's.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCompoundCronTask extends TCronTask
{
	/** The separator of the steps in a configured string. */
	public const STEP_SEPARATOR = ';';

	/** @var string[] the steps */
	private array $_tasks = [];

	/** @var bool whether a step that throws stops the rest */
	private bool $_stopOnFailure = true;

	/** @var bool whether the steps run at once */
	private bool $_parallel = false;

	/**
	 * Runs the steps.
	 * @param TCronModule $cronModule the module running the task
	 * @throws TConfigurationException when the task has no steps, or a referenced task does not exist
	 * @throws \Throwable the exception of the first step that threw
	 */
	public function execute($cronModule)
	{
		if ($this->_tasks === []) {
			throw new TConfigurationException('compoundcrontask_no_tasks', $this->getName());
		}
		$steps = [];
		foreach ($this->_tasks as $i => $spec) {
			$steps[] = $this->createStep($cronModule, $spec, $i + 1);
		}
		$failure = null;
		if ($this->_parallel) {
			$futures = array_map(fn (TCronTask $step) => TAsync::run([$this, 'executeStep'], $step, $cronModule), $steps);
			foreach ($futures as $future) {
				try {
					$future->await();
				} catch (\Throwable $e) {
					$failure ??= $e;
				}
			}
		} else {
			foreach ($steps as $step) {
				try {
					$this->executeStep($step, $cronModule);
				} catch (\Throwable $e) {
					if ($this->_stopOnFailure) {
						throw $e;
					}
					$failure ??= $e;
				}
			}
		}
		if ($failure !== null) {
			throw $failure;
		}
	}

	/**
	 * Creates the task of a step: a configured task for `@name`, else a task of the spec named after the compound.
	 * @param TCronModule $cronModule the module running the task
	 * @param string $spec the step
	 * @param int $index the step's number, from 1
	 * @throws TConfigurationException when a referenced task does not exist
	 * @return TCronTask the step's task
	 */
	protected function createStep($cronModule, string $spec, int $index): TCronTask
	{
		if (str_starts_with($spec, '@')) {
			$task = $cronModule->getTask($name = substr($spec, 1));
			if (!($task instanceof TCronTask)) {
				throw new TConfigurationException('compoundcrontask_unknown_task', $this->getName(), $name);
			}
			return $task;
		}
		$task = $cronModule->instanceTask($spec);
		$task->setName($this->getName() . '#' . $index);
		$task->setUserName($this->getUserName());
		return $task;
	}

	/**
	 * Executes a step with the compound's cancellation token.
	 * @param TCronTask $step the step's task
	 * @param TCronModule $cronModule the module running the task
	 */
	public function executeStep(TCronTask $step, $cronModule): void
	{
		$prior = $step->getCancellationToken();
		$step->setCancellationToken($this->getCancellationToken());
		try {
			$step->execute($cronModule);
		} finally {
			$step->setCancellationToken($prior);
		}
	}

	/**
	 * @return string[] the steps: class names, `module->method(args)`, or `@name` of a configured task
	 */
	public function getTasks(): array
	{
		return $this->_tasks;
	}

	/**
	 * @param null|string|string[] $value the steps: a ';' list or an array
	 * @return static the current object
	 */
	public function setTasks($value): static
	{
		$value = ($value === null || $value === '') ? [] : (is_array($value) ? $value : explode(static::STEP_SEPARATOR, TPropertyValue::ensureString($value)));
		$this->_tasks = array_values(array_filter(array_map(static fn ($step) => trim((string) $step), $value), static fn ($step) => $step !== ''));
		return $this;
	}

	/**
	 * @return bool whether a step that throws stops the rest; default true
	 */
	public function getStopOnFailure(): bool
	{
		return $this->_stopOnFailure;
	}

	/**
	 * @param bool|string $value whether a step that throws stops the rest
	 * @return static the current object
	 */
	public function setStopOnFailure($value): static
	{
		$this->_stopOnFailure = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return bool whether the steps run at once in fibers of their own; default false
	 */
	public function getParallel(): bool
	{
		return $this->_parallel;
	}

	/**
	 * @param bool|string $value whether the steps run at once in fibers of their own
	 * @return static the current object
	 */
	public function setParallel($value): static
	{
		$this->_parallel = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * Excludes the default-valued properties from serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_tasks === []) {
			$exprops[] = "\0Prado\Util\Cron\TCompoundCronTask\0_tasks";
		}
		if ($this->_stopOnFailure) {
			$exprops[] = "\0Prado\Util\Cron\TCompoundCronTask\0_stopOnFailure";
		}
		if (!$this->_parallel) {
			$exprops[] = "\0Prado\Util\Cron\TCompoundCronTask\0_parallel";
		}
	}
}
