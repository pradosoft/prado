<?php

/**
 * TShellCronAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TExitException;
use Prado\Prado;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;
use Prado\Util\Cron\TCronModule;

/**
 * TShellCronAction class.
 *
 * Runs the TCronModule from the command line. The "cron" command, and "cron/tasks",
 * report the installed tasks being managed with their mode, state, and last result;
 * "cron/run" runs all pending tasks; "cron/top" shows the cron processes, running tasks,
 * next triggers, and last results, refreshing in a terminal; and "cron/index" reports the
 * possible cron tasks registered in the application.  A system crontab entry runs
 * "cron/run".  "cron/now" runs a task at once, whatever its schedule; "cron/next" shows the next
 * trigger times.  "cron/pause" and "cron/resume" stop and restart a task's triggered runs.
 * "cron/exec" runs one task in its process: the child process of a Process
 * task, which exits with {@see TCronModule::EXIT_ERROR}, {@see TCronModule::EXIT_NO_TASK},
 * or {@see TCronModule::EXIT_DENIED} when the task did not run cleanly.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TShellCronAction extends TShellAction
{
	/** The rows each table of {@see renderTop} shows at most. */
	public const TOP_ROWS = 10;

	protected $action = 'cron';
	protected $methods = ['tasks', 'run', 'index', 'daemon', 'retire', 'stop', 'top', 'exec', 'now', 'next', 'pause', 'resume'];
	protected $parameters = [null, null, null, null, null, null, null, ['task-name'], ['task-name'], null, ['task-name'], ['task-name']];
	protected $optional = [['task-name or @user'], null, null, null, ['seconds'], ['seconds'], ['seconds', 'count'], null, null, ['task-name', 'count'], null, null];
	protected $description = ['Manages Cron time-based services',
		'Displays the Cron tasks configured in the application. Filter by @user and task name.',
		'Runs the Cron Pending Tasks.',
		'Displays the registered Cron tasks information.',
		'Shows whether a cron daemon holds the lease, and whether it is retiring.',
		'Retires the running cron daemon: it hands the lease to the next cron/run tick with no gap.',
		'Stops the running cron daemon at once; the next cron/run tick starts a new one.',
		'Shows the cron processes, running tasks, next triggers, and last results; live in a terminal.',
		'Runs one task in this process; a Process task\'s child process runs it.',
		'Runs a task now, whatever its schedule, and shows its result.',
		'Shows the next trigger times of the tasks; filter by task name.',
		'Pauses a task: its triggers pass without runs until it is resumed.',
		'Resumes a paused task.',
	];

	private $_cron = false;
	private $_user;

	public function __construct()
	{
		/** @var \Prado\Security\Permissions\TUserPermissionsBehavior $user */
		$user = Prado::getApplication()->getUser();
		if ($user && $user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR)) {
			$this->_user = $user;
			if (!$user->can(TCronModule::PERM_CRON_RUN_PENDING)) {
				$this->removeShellMethod('run');
				$this->removeShellMethod('exec');
				$this->removeShellMethod('now');
				$this->removeShellMethod('pause');
				$this->removeShellMethod('resume');
				$this->removeShellMethod('retire');
				$this->removeShellMethod('stop');
			}
		}
		parent::__construct();
	}

	/**
	 * Removes a sub-command the current user may not run from the methods, parameters, optional
	 * parameters, and descriptions.
	 * @param string $name the sub-command
	 * @since 4.4.0
	 */
	protected function removeShellMethod(string $name): void
	{
		$i = array_search($name, $this->methods, true);
		if ($i === false) {
			return;
		}
		unset($this->methods[$i], $this->parameters[$i], $this->optional[$i], $this->description[$i + 1]);
		$this->methods = array_values($this->methods);
		$this->parameters = array_values($this->parameters);
		$this->optional = array_values($this->optional);
		$this->description = array_values($this->description);
	}

	/**
	 * @return string the Cron Class to find
	 */
	public function getModuleClass()
	{
		return \Prado\Util\Cron\TCronModule::class;
	}

	/**
	 * @return ?\Prado\Util\Cron\TCronModule returns the Cron Module of the applications
	 */
	public function getCronModule()
	{
		if ($this->_cron === false) {
			$app = Prado::getApplication();
			$moduleClass = $this->getModuleClass();
			$modules = $app->getModulesByType($moduleClass, false);
			$this->_cron = null;
			foreach ($modules as $id => $m) {
				if ($this->_cron = $app->getModule($id)) {
					break;
				}
			}
			if (!$this->_cron) {
				$this->_outWriter->writeError("A {$moduleClass} is not found");
				return null;
			}
		}
		if (!$this->_cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$this->_cron->attachBehavior(TCronModule::SHELL_LOG_BEHAVIOR, Prado::createComponent(TShellCronLogBehavior::class, $this->getWriter()));
		}
		return $this->_cron;
	}

	/**
	 * @param ?\Prado\Util\Cron\TCronModule $cron sets the Cron Module
	 */
	public function setCronModule($cron)
	{
		$this->_cron = $cron;
	}

	/**
	 * @param string[] $args the arguments to the command line action
	 */
	public function actionRun($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$time = $module->getLastCronTime();
		$this->_outWriter->writeLine("\n Last cron run time was " . ($time == 0 ? 'never' : date('Y-m-d H:i:s TP', (int) $time)) . '');
		if ($module->getEnableStatus() === null) {
			$module->setEnableStatus(true);
		}
		$module->processPendingTasks();
		return true;
	}

	/**
	 * Runs one task in this process through {@see TCronModule::executeTask}, without claiming the run:
	 * the child process of a Process task.  The process exits with {@see TCronModule::EXIT_ERROR} when
	 * the task threw, {@see TCronModule::EXIT_NO_TASK} when it is not found, and
	 * {@see TCronModule::EXIT_DENIED} when its user lacks the permission.
	 * @param string[] $args the arguments to the command line action; the task name
	 * @throws TExitException with the exit code when the task did not run cleanly
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionExec($args)
	{
		$module = $this->getCronModule();
		$name = $args[1] ?? '';
		$task = ($module && $name !== '') ? $module->getTask($name) : null;
		if (!$task) {
			$this->_outWriter->writeError("Cron task '{$name}' is not found.");
			throw new TExitException(TCronModule::EXIT_NO_TASK);
		}
		$code = match ($module->executeTask($task)) {
			TCronModule::RESULT_OK => 0,
			TCronModule::RESULT_DENIED => TCronModule::EXIT_DENIED,
			default => TCronModule::EXIT_ERROR,
		};
		if ($code !== 0) {
			throw new TExitException($code);
		}
		return true;
	}

	/**
	 * Runs a task at once through {@see TCronModule::runTaskNow}, whatever its schedule, pause, deadline,
	 * or time of day, and shows the result.  The run lock, the claim, the task events, and the pings apply
	 * as for a triggered run; with the module's EnableStatus unset, the run publishes its status for `cron/top`.
	 * @param string[] $args the arguments to the command line action; the task name
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionNow($args)
	{
		$module = $this->getCronModule();
		$name = $args[1] ?? '';
		$task = ($module && $name !== '') ? $module->getTask($name) : null;
		if (!$task) {
			$this->_outWriter->writeError("Cron task '{$name}' is not found.");
			return true;
		}
		$outcome = null;
		$record = static function ($sender, $param) use (&$outcome, $task) {
			if ($param->getTask() === $task) {
				$outcome = $param;
			}
		};
		$module->attachEventHandler('OnTaskSkipped', $record);
		$module->attachEventHandler('OnTaskEnd', $record);
		if ($module->getEnableStatus() === null) {
			$module->setEnableStatus(true);
		}
		try {
			$module->runTaskNow($task);
		} finally {
			$module->detachEventHandler('OnTaskSkipped', $record);
			$module->detachEventHandler('OnTaskEnd', $record);
		}
		if ($outcome === null || $outcome->getReason() !== null) {
			$this->_outWriter->writeError("Cron task '{$name}' did not run: " . ($outcome?->getReason() ?? 'unknown') . '.');
			return true;
		}
		$this->_outWriter->writeLine("\nCron task '{$name}' ran: " . $this->formatResult($outcome->getResult()) . ' in ' . $this->formatSeconds((float) $outcome->getDuration()) . "\n");
		return true;
	}

	/**
	 * Pauses a task through {@see TCronModule::pauseTask}: its triggers pass without runs until resumed.
	 * @param string[] $args the arguments to the command line action; the task name
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionPause($args)
	{
		return $this->setTaskPaused($args[1] ?? '', true);
	}

	/**
	 * Resumes a paused task through {@see TCronModule::resumeTask}.
	 * @param string[] $args the arguments to the command line action; the task name
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionResume($args)
	{
		return $this->setTaskPaused($args[1] ?? '', false);
	}

	/**
	 * Pauses or resumes a task the user may read, and reports it.
	 * @param string $name the task name
	 * @param bool $paused whether to pause the task
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	protected function setTaskPaused(string $name, bool $paused): bool
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		if ($name === '' || !isset($this->getReadableTasks($module)[$name])) {
			$this->_outWriter->writeError("Cron task '{$name}' is not found.");
			return true;
		}
		$paused ? $module->pauseTask($name) : $module->resumeTask($name);
		$this->_outWriter->writeLine("\nCron task '{$name}' is " . ($paused ? 'paused' : 'resumed') . ".\n", TShellWriter::GREEN);
		return true;
	}

	/**
	 * Shows the next trigger times of the tasks the user may read, soonest first: one per task, or with
	 * a task name filter five per task; the count argument sets the number.  A time is shown in the
	 * task's TimeZone.
	 * @param string[] $args the arguments to the command line action; an optional task name filter, where
	 *   `_` is a wild card and `%` a wild string, and count
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionNext($args)
	{
		$module = $this->getCronModule();
		if (!$module || ($tasks = $this->getReadableTasks($module)) === null) {
			return true;
		}
		$name = (isset($args[1]) && $args[1] !== '') ? $args[1] : null;
		if ($name !== null) {
			$tasks = array_intersect_key($tasks, $module->getTasks($name) ?? []);
		}
		$count = (isset($args[2]) && is_numeric($args[2])) ? max(1, (int) $args[2]) : (($name !== null) ? 5 : 1);
		$now = $this->time();
		$times = [];
		foreach ($tasks as $tname => $task) {
			if ($list = $this->getNextTriggerTimes($task, $count, $now)) {
				$times[$tname] = $list;
			}
		}
		uasort($times, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
		if (!$times) {
			$this->_outWriter->writeLine("\n     **  No task has a next trigger.  **\n");
			return true;
		}
		$rows = [];
		foreach ($times as $tname => $list) {
			$task = $tasks[$tname];
			$zone = new \DateTimeZone($task->getScheduleTimeZone() ?? date_default_timezone_get());
			foreach ($list as $i => $time) {
				$rows[] = [($i === 0) ? $tname : '', ($i === 0) ? $task->getMode() : '',
					(new \DateTimeImmutable('@' . $time))->setTimezone($zone)->format('Y-m-d H:i:s T'),
					($time <= $now) ? $this->_outWriter->format('due', [TShellWriter::GREEN, TShellWriter::BOLD]) : $this->formatSeconds($time - $now)];
			}
		}
		$this->_outWriter->writeLine();
		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Name', 'Mode', 'Trigger', 'In'], 'rows' => $rows]));
		$this->_outWriter->writeLine();
		return true;
	}

	/**
	 * Returns a task's next trigger times: its pending or next trigger, then those after it or after now.
	 * @param TCronTask $task the task
	 * @param int $count the number of times
	 * @param int $now the current time
	 * @return int[] the trigger times, ascending
	 * @since 4.4.0
	 */
	protected function getNextTriggerTimes($task, int $count, int $now): array
	{
		$first = $task->getNextTriggerTime();
		if ($first === null) {
			return [];
		}
		$first = (int) $first;
		return array_merge([$first], ($count > 1) ? $task->getNextTriggerTimes($count - 1, max($first, $now)) : []);
	}

	/**
	 * Shows the cron processes of the module, the running tasks, the next triggers, and the last
	 * results.  In a terminal the view redraws every interval until interrupted or the count of
	 * views is shown; otherwise it is shown once unless a count is given.
	 * @param string[] $args the arguments to the command line action; the optional seconds between
	 *   views (default 1) and count of views
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionTop($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$interval = (isset($args[1]) && is_numeric($args[1])) ? max(0.1, (float) $args[1]) : 1.0;
		$live = $this->isInteractive();
		$count = (isset($args[2]) && is_numeric($args[2])) ? max(1, (int) $args[2]) : ($live ? 0 : 1);
		for ($i = 1; ; $i++) {
			$view = $this->renderTop($module);
			if ($view === null) {
				return true;
			}
			if ($live) {
				$this->_outWriter->write("\033[H\033[2J");
			}
			$this->_outWriter->write($view);
			if ($live) {
				$this->flushView();
			}
			if ($count && $i >= $count) {
				break;
			}
			$this->sleep($interval);
		}
		return true;
	}

	/**
	 * Renders one view of {@see actionTop}: a line for each cron process, then the running tasks, the
	 * next triggers, and the last results of the tasks the user may read.
	 * @param TCronModule $module the cron module
	 * @return ?string the view, or null when the user may not read the tasks
	 * @since 4.4.0
	 */
	public function renderTop($module): ?string
	{
		$tasks = $this->getReadableTasks($module);
		if ($tasks === null) {
			return null;
		}
		$w = $this->_outWriter;
		$now = $this->microtime();
		$out = $w->format('Cron ' . $module->getID(), [TShellWriter::BOLD]) . '  ' . date('Y-m-d H:i:s', (int) $now) . "\n";

		$statuses = $module->getStatuses();
		$running = [];
		$children = 0;
		$alive = 0;
		foreach ($statuses as $status) {
			$out .= '  ' . $this->formatStatus($status, $now) . "\n";
			if (!$status['alive']) {
				continue;
			}
			$alive++;
			foreach ($status['running'] as $run) {
				if (isset($tasks[$run['name'] ?? ''])) {
					$running[] = $run + ['owner' => $status['pid']];
					$children += (($run['mode'] ?? '') === TCronTaskMode::Process) ? 1 : 0;
				}
			}
		}
		if (!$alive) {
			$out .= '  No cron process is running' . ($module->getDaemon() ? '; the next cron/run tick starts the daemon.' : '.') . "\n";
		}
		$out .= '  Concurrency ' . $module->getConcurrency() . ' · ' . (count($running) - $children) . ' running in process · ' . $children . " child process(es)\n\n";

		if ($running) {
			$rows = [];
			foreach ($running as $run) {
				$started = (float) ($run['started'] ?? $now);
				$deadline = $run['deadline'] ?? null;
				$rows[] = [$run['name'], $run['mode'] ?? '-', (string) ($run['pid'] ?? '-'), '@' . ($run['user'] ?? '-'), date('H:i:s', (int) $started),
					$this->formatSeconds($now - $started), ($deadline === null) ? '-' : $this->formatSeconds(max(0.0, $deadline - $now))];
			}
			$out .= $w->format('  Running', [TShellWriter::BOLD]) . "\n" . $w->tableWidget(['headers' => ['Task', 'Mode', 'Process', 'User', 'Started', 'Elapsed', 'Timeout in'], 'rows' => $rows]);
		}

		$rows = [];
		$paused = array_flip($module->getPausedTasks());
		foreach ($module->getNextTriggers(PHP_INT_MAX, true) as $name => $trigger) {
			if (!isset($tasks[$name])) {
				continue;
			}
			$rows[] = [$name, $tasks[$name]->getMode(), date((abs($trigger - $now) > 86400) ? 'Y-m-d H:i:s' : 'H:i:s', $trigger),
				isset($paused[$name]) ? $w->format('paused', [TShellWriter::DARK_GRAY]) : (($trigger <= $now) ? $w->format('due', [TShellWriter::GREEN, TShellWriter::BOLD]) : $this->formatSeconds($trigger - $now))];
			if (count($rows) >= static::TOP_ROWS) {
				break;
			}
		}
		if ($rows) {
			$out .= $w->format('  Next', [TShellWriter::BOLD]) . "\n" . $w->tableWidget(['headers' => ['Task', 'Mode', 'Trigger', 'In'], 'rows' => $rows]);
		}

		$results = array_filter(array_intersect_key($module->getTaskResults(true), $tasks), static fn (array $r): bool => $r['result'] !== null);
		uasort($results, static fn (array $a, array $b): int => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));
		$rows = [];
		foreach (array_slice($results, 0, static::TOP_ROWS, true) as $name => $r) {
			$rows[] = [$name, $tasks[$name]->getMode(), ($r['time'] === null) ? '-' : date((abs($r['time'] - $now) > 86400) ? 'Y-m-d H:i:s' : 'H:i:s', $r['time']),
				($r['duration'] === null) ? '-' : $this->formatSeconds($r['duration']), $this->formatResult($r['result']), (string) $r['count']];
		}
		if ($rows) {
			$out .= $w->format('  Last results', [TShellWriter::BOLD]) . "\n" . $w->tableWidget(['headers' => ['Task', 'Mode', 'Last Run', 'Took', 'Result', 'Run #'], 'rows' => $rows]);
		}
		return $out;
	}

	/**
	 * Formats the line of a cron process in {@see renderTop}.
	 * @param array $status the process's status from {@see TCronModule::getStatuses}
	 * @param float $now the current time
	 * @return string the line
	 * @since 4.4.0
	 */
	protected function formatStatus(array $status, float $now): string
	{
		$w = $this->_outWriter;
		$daemon = $status['daemon'] ?? null;
		$label = match ($status['role'] ?? '') {
			TCronModule::STATUS_ROLE_DAEMON => 'Daemon',
			TCronModule::STATUS_ROLE_MANUAL => 'Manual',
			default => 'Sweep ',
		};
		$parts = [$w->format($label, [TShellWriter::BOLD]) . ' pid ' . ($status['pid'] ?? '?') . ' on ' . ($status['host'] ?? '?')];
		if (!$status['alive']) {
			$parts[] = $w->format('ended without removing its status', [TShellWriter::RED]);
			return implode(' · ', $parts);
		}
		$parts[] = 'up ' . $this->formatSeconds($now - (float) ($status['started'] ?? $now));
		if ($daemon !== null) {
			$parts[] = (string) ($daemon['state'] ?? '');
			if ($daemon['transport'] ?? null) {
				$parts[] = substr(strrchr('\\' . $daemon['transport'], '\\'), 1) . (($daemon['watching'] ?? false) ? ' watching' : ' not watching');
			}
			if (($wake = $daemon['wake'] ?? null) !== null) {
				$parts[] = 'next sweep in ' . $this->formatSeconds(max(0.0, (float) $wake - $now));
			}
			$parts[] = ($daemon['sweeps'] ?? 0) . ' sweeps, ' . ($daemon['tasks'] ?? 0) . ' tasks';
		}
		return implode(' · ', $parts);
	}

	/**
	 * Returns the tasks the shell user may read: all tasks with {@see TCronModule::PERM_CRON_READ_TASKS}, else the user's own.
	 * @param TCronModule $module the cron module
	 * @return ?array<string, TCronTask> the tasks by name, or null when access is denied
	 * @since 4.4.0
	 */
	protected function getReadableTasks($module): ?array
	{
		if ($this->_user && !$this->_user->can(TCronModule::PERM_CRON_READ_TASKS)) {
			return $module->getUserTasks($this->_user->getName());
		}
		return $module->getTasks();
	}

	/**
	 * Returns the running state of each task in the live cron processes: `running`, or `pid <id>` for
	 * a Process task's child process.
	 * @param TCronModule $module the cron module
	 * @return array<string, string> the states, by task name
	 * @since 4.4.0
	 */
	protected function getRunningStates($module): array
	{
		$states = [];
		foreach ($module->getStatuses() as $status) {
			if (!$status['alive']) {
				continue;
			}
			foreach ($status['running'] as $run) {
				$state = (($run['mode'] ?? '') === TCronTaskMode::Process) ? 'pid ' . ($run['pid'] ?? '?') : 'running';
				$name = $run['name'] ?? '';
				$states[$name] = isset($states[$name]) ? $states[$name] . ', ' . $state : $state;
			}
		}
		return $states;
	}

	/**
	 * @param ?string $result a run's result
	 * @return string the result, colored: green for ok, red otherwise
	 * @since 4.4.0
	 */
	protected function formatResult(?string $result): string
	{
		if ($result === null) {
			return '-';
		}
		return $this->_outWriter->format($result, ($result === TCronModule::RESULT_OK) ? [TShellWriter::GREEN] : [TShellWriter::RED]);
	}

	/**
	 * @param float $seconds a span of seconds
	 * @return string the span: `0.12s` under ten seconds, `42s` under a minute, `5m 3s` under an hour, else `2h 5m`
	 * @since 4.4.0
	 */
	protected function formatSeconds(float $seconds): string
	{
		if ($seconds < 10) {
			return sprintf('%.2fs', $seconds);
		}
		$seconds = (int) round($seconds);
		if ($seconds < 60) {
			return $seconds . 's';
		}
		if ($seconds < 3600) {
			return intdiv($seconds, 60) . 'm ' . ($seconds % 60) . 's';
		}
		return intdiv($seconds, 3600) . 'h ' . intdiv($seconds % 3600, 60) . 'm';
	}

	/**
	 * @return bool whether the output is a terminal, where {@see actionTop} redraws
	 * @since 4.4.0
	 */
	protected function isInteractive(): bool
	{
		return defined('STDOUT') && @stream_isatty(STDOUT);
	}

	/**
	 * Sends a view of {@see actionTop} to the terminal.
	 * @since 4.4.0
	 */
	protected function flushView(): void
	{
		$this->_outWriter->flush();
	}

	/**
	 * Waits between the views of {@see actionTop}.
	 * @param float $seconds the seconds to wait
	 * @since 4.4.0
	 */
	protected function sleep(float $seconds): void
	{
		usleep((int) ($seconds * 1_000_000));
	}

	/**
	 * Returns the current Unix timestamp with microseconds.
	 * @return float the current time
	 * @since 4.4.0
	 */
	protected function microtime(): float
	{
		return \microtime(true);
	}

	/**
	 * Shows configured tasks and their run status.  For TDbCronManager, this also shows the
	 * database tasks as well.
	 * @param mixed $args
	 */
	public function actionTasks($args)
	{
		$module = $this->getCronModule();

		$time = $module->getLastCronTime();
		$this->_outWriter->writeLine("\nThe system time is " . date('Y-m-d H:i:s TP'));
		$this->_outWriter->writeLine(" Last cron run was " . ($time == 0 ? 'never' : date('Y-m-d H:i:s', (int) $time)) . "\n");

		$taskName = null;
		$username = null;
		if ($this->_user && !$this->_user->can(TCronModule::PERM_CRON_READ_TASKS)) {
			$username = $this->_user->getName();
		}
		$c = count($args) - 1;
		while ($c--) {
			if (($arg = $args[$c + 1] ?? false)) {
				if ($arg[0] == '@') {
					$username = substr($arg, 1);
				} else {
					$taskName = $arg;
				}
			}
		}
		if ($taskName && $username) {
			$filter = ", filtered by task name '{$taskName}' and @{$username}";
		} elseif ($taskName) {
			$filter = ", filtered by task name '{$taskName}'";
		} elseif ($username) {
			$filter = ", filtered by @{$username}";
		} else {
			$filter = '';
		}
		if ($username) {
			$tasks = $module->getUserTasks($username, $taskName);
		} else {
			$tasks = $module->getTasks($taskName);
		}
		if ($tasks === null) {
			return true;
		}
		$count = count($tasks);

		$this->_outWriter->write("  Application Cron Tasks: {$count} tasks found", [TShellWriter::BOLD]);
		$this->_outWriter->writeLine($filter . "\n");
		if (!$count) {
			$this->_outWriter->writeLine("     **  There are no configured Cron Tasks.  **\n");
			return true;
		}
		$rows = [];
		$states = $this->getRunningStates($module);
		foreach ($module->getPausedTasks() as $paused) {
			$states[$paused] = isset($states[$paused]) ? 'paused, ' . $states[$paused] : 'paused';
		}
		$results = $module->getTaskResults();
		foreach ($tasks as $task) {
			$schedule = $task->getSchedule();
			if (strlen($schedule) > 1 && $schedule[0] == '@') {
				$trigger = substr($schedule, 1);
				if (is_numeric($trigger)) {
					if (abs($trigger - $this->time()) > 86400) {
						$f = 'Y-m-d H:i:s';
					} else {
						$f = 'H:i:s';
					}
					$schedule = '@(' . date($f, $trigger) . ')';
				}
			}

			$lastExecTime = $task->getLastExecTime();
			$count = $task->getProcessCount();
			if ($lastExecTime === null) {
				$lastrun = '-';
			} else {
				if (abs($this->time() - $lastExecTime) > 86400) {
					$f = 'Y-m-d H:i:s';
				} else {
					$f = 'H:i:s';
				}
				$lastrun = date($f, $lastExecTime);
				if (!$count) {
					$lastrun = '- (' . $lastrun . ')';
				}
			}

			$trigger = $task->getNextTriggerTime();
			if ($trigger === null) {
				$nextrun = '-';
			} else {
				if (abs($trigger - $this->time()) > 86400) {
					$f = 'Y-m-d H:i:s';
				} else {
					$f = 'H:i:s';
				}
				$nextrun = date($f, $trigger) . ($task->getIsPending() ? '*' : '');
			}

			if (($user = $task->getUserName()) == null) {
				$user = $module->getDefaultUserName();
			}

			if ($task->getIsPending()) {
				$nextrun = $this->_outWriter->format($nextrun, [TShellWriter::GREEN, TShellWriter::BOLD]);
			}

			$result = $results[$task->getName()] ?? null;
			$last = '-';
			if (($result['result'] ?? null) !== null) {
				$last = $this->formatResult($result['result']) . (($result['duration'] !== null) ? ' ' . $this->formatSeconds($result['duration']) : '');
			}
			$state = $states[$task->getName()] ?? '-';
			if ($state !== '-') {
				$state = $this->_outWriter->format($state, [TShellWriter::GREEN, TShellWriter::BOLD]);
			}

			$rows[] = [$task->getName(), $schedule, $task->getTask(), $task->getMode(), $lastrun, $last, $nextrun, '@' . $user, $count, $state];
			if (($description = $task->getDescription()) !== '') {
				$rows[] = ['span' => $this->_outWriter->format('      ' . $description, TShellWriter::DARK_GRAY)];
			}
		}
		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Name', 'Schedule', 'Task', 'Mode', 'Last Run', 'Result', 'Next Run', 'User', 'Run #', 'State'],
			'rows' => $rows]));
		$this->_outWriter->writeLine("\nAny 'next run' with a * means it is Pending\n");

		return true;
	}

	/**
	 * shows the registered tasks from the application for possible configuration
	 * or addition to TDbCronManager.
	 * @param mixed $args
	 */
	public function actionIndex($args)
	{
		$module = $this->getCronModule();

		$infos = $module->getTaskInfos(true);
		$this->_outWriter->writeLine();
		$this->_outWriter->writeLine("  Registered Cron Task Information: ", [TShellWriter::BOLD]);
		if (!count($infos)) {
			$this->_outWriter->writeLine("		(** No registered application tasks **)");
			return true;
		}
		$rows = [];
		foreach ($infos as $taskinfo) {
			$rows[] = [
					$this->_outWriter->format($taskinfo->getName(), [TShellWriter::BLUE, TShellWriter::BOLD]),
					$taskinfo->getTask(), $taskinfo->getModuleId(), $taskinfo->getTitle(),
				];
			$rows[] = ['span' => $this->_outWriter->format('      ' . $taskinfo->getDescription(), TShellWriter::DARK_GRAY)];
		}
		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Task ID', 'Task', 'Module ID', 'Title'],
			'rows' => $rows]));
		$this->_outWriter->writeLine();
		return true;
	}

	/**
	 * Shows the state of the cron daemon of the module's lease.
	 * @param string[] $args the arguments to the command line action
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionDaemon($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$lock = $module->getDaemonLockName();
		$this->_outWriter->writeLine("\n" . match ($module->getDaemonState()) {
			TCronModule::DAEMON_STATE_RUNNING => "A cron daemon holds the lease ({$lock}) and is running.",
			TCronModule::DAEMON_STATE_RETIRING => "A cron daemon holds the lease ({$lock}) and is retiring; the next cron/run tick takes it over.",
			default => "No cron daemon holds the lease ({$lock})" . ($module->getDaemon() ? '; the next cron/run tick starts one.' : '; Daemon mode is off.'),
		} . "\n");
		return true;
	}

	/**
	 * Retires the running cron daemon: it serves on and hands the lease to the next cron/run tick,
	 * which starts a new daemon on the current code and configuration without a gap.
	 * @param string[] $args the arguments to the command line action; an optional acknowledgment timeout in seconds
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionRetire($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$result = $module->requestDaemonRetire($this->requestTimeout($args));
		if ($result === null) {
			$this->_outWriter->writeLine("\nNo cron daemon is running.\n");
		} elseif ($result) {
			$this->_outWriter->writeLine("\nThe cron daemon is retiring; the next cron/run tick starts a new one with no gap.\n", TShellWriter::GREEN);
		} else {
			$this->_outWriter->writeError('The cron daemon did not acknowledge the retire request in time.');
		}
		return true;
	}

	/**
	 * Stops the running cron daemon at once; the next cron/run tick starts a new one.
	 * @param string[] $args the arguments to the command line action; an optional acknowledgment timeout in seconds
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionStop($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$result = $module->requestDaemonStop($this->requestTimeout($args));
		if ($result === null) {
			$this->_outWriter->writeLine("\nNo cron daemon is running.\n");
		} elseif ($result) {
			$this->_outWriter->writeLine("\nThe cron daemon stopped; the next cron/run tick starts a new one.\n", TShellWriter::GREEN);
		} else {
			$this->_outWriter->writeError('The cron daemon did not stop within the timeout.');
		}
		return true;
	}

	/**
	 * @param string[] $args the command arguments
	 * @return ?float the acknowledgment timeout in seconds from the first argument, or null for the module default
	 * @since 4.4.0
	 */
	protected function requestTimeout($args): ?float
	{
		return (isset($args[1]) && is_numeric($args[1])) ? max(0.0, (float) $args[1]) : null;
	}

	/**
	 * Returns the current Unix timestamp. Wraps the global {@see \time()} as a single
	 * overridable seam so that subclasses and tests can control the clock.
	 * @return int the current Unix timestamp
	 * @since 4.4.0
	 */
	protected function time(): int
	{
		return \time();
	}
}
