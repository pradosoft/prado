<?php

/**
 * Cron daemon end-to-end test.
 *
 * Runs real `prado-cli cron/run` processes on a fixed tick, as a crontab or the Windows Task
 * Scheduler does, against a throwaway application in the temp directory, and checks the daemon
 * from the outside: one daemon at a time, lifetime handoffs without a missed or doubled trigger,
 * a killed daemon replaced by the next tick, `cron/stop`, a wake from `cron/add` in another
 * process reaching the waiting daemon through the default TCronWakeSocket, and a Process task
 * run by a `cron/exec` child while the daemon serves on, shown by its status, `cron/top`, and
 * `cron/tasks`.  The `features` scenario runs one daemon over every task feature (pings through HTTP
 * and the `fxCronTaskPing` seam, failures and retries, pause and resume, the starting deadline, a time
 * window, an excepted date, jitter, priority, a chain, a compound task, a command task, an HTTP task,
 * an `after end` schedule, a Process task's warning, output, priority, and memory limit, and the
 * monitoring endpoint served by a PHP web server); the `overlap` scenario checks Replace and Queue on
 * Process tasks.  The `tick` scenario runs no daemon: overlapping `cron/run` crontab ticks each serve the
 * rest of their minute at the trigger seconds, and each trigger of a five-second task runs once.
 *
 * Usage: php tests/cron-daemon/run.php [handoff|wake|process|features|overlap|tick|all]
 * Exit code 0 when every check passes, 1 otherwise.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

const TICK = 3.0;            // seconds between cron/run ticks
const SLACK = 1.5;           // allowed lateness of a run, in seconds
const QUICK_EXIT = 10.0;     // a tick that is not elected exits within this many seconds

final class CronDaemonE2E
{
	/** @var string[] check failures */
	private array $failures = [];
	/** @var array<int, array{proc: resource, pid: int, started: float, ended: ?float, out: string}> */
	private array $procs = [];
	private string $dir = '';
	private string $log = '';
	private string $cli;
	private bool $windows;
	/** @var ?array{proc: resource, port: int} the PHP web server of a scenario */
	private ?array $server = null;

	public function __construct()
	{
		$this->cli = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'prado-cli.php';
		$this->windows = PHP_OS_FAMILY === 'Windows';
	}

	public function run(array $scenarios): int
	{
		foreach ($scenarios as $scenario) {
			$this->line("== {$scenario} (" . PHP_OS_FAMILY . ', PHP ' . PHP_VERSION . ')');
			$before = count($this->failures);
			try {
				$this->{'scenario' . ucfirst($scenario)}();
			} catch (\Throwable $e) {
				$this->fail($scenario . ' threw ' . $e::class . ': ' . $e->getMessage());
			}
			$this->stopServer();
			$this->finish();
			if (count($this->failures) > $before) {
				$this->dump();
				$this->line("   kept for inspection: {$this->dir}");
			} else {
				$this->line("   {$scenario} passed");
				$this->removeApp($this->dir);
			}
		}
		foreach ($this->failures as $failure) {
			$this->line(($this->ci() ? '::error::' : 'FAIL: ') . $failure);
		}
		return $this->failures ? 1 : 0;
	}

	/**
	 * Lifetime handoffs, a killed daemon, and cron/stop, with a task every 2 seconds.
	 */
	private function scenarioHandoff(): void
	{
		$this->makeApp('DaemonLifetime="6" DaemonIdle="5" DaemonHandoffTimeout="10"', '<job name="every2" schedule="*:*/2 * * * *" task="Application.CronE2ETask" />');
		$start = microtime(true);
		$killAt = $start + 19.0;
		$killed = null;
		$nextTick = $start;
		while (($now = microtime(true)) < $start + 36.0) {
			if ($now >= $nextTick) {
				$this->tick();
				$nextTick += TICK;
			}
			if ($killed === null && $now >= $killAt && ($pid = $this->runningDaemon()) !== null) {
				$killed = ['pid' => $pid, 't' => microtime(true), 'graceful' => $this->kill($pid)];
				$this->line("   killed daemon {$pid}" . ($killed['graceful'] ? ' with SIGTERM' : ' (terminated)'));
			}
			$this->reap();
			usleep(100000);
		}
		$this->stop();

		$events = $this->events();
		$starts = $this->select($events, 'start');
		$ends = $this->select($events, 'end');
		$runs = $this->select($events, 'run');
		$this->transport($starts);
		$this->check(count($starts) >= 4, 'at least four daemons ran (lifetime handoffs and a replaced kill); ' . count($starts) . ' started');
		$this->check(count(array_unique(array_column($starts, 'pid'))) === count($starts), 'each daemon is a process of its own');
		$this->check($killed !== null, 'a running daemon was found to kill');
		$reasons = array_count_values(array_map(fn ($e) => (string) $e['reason'], $ends));
		$this->line('   end reasons: ' . json_encode($reasons));
		$this->check(($reasons['handoff'] ?? 0) >= 2, 'at least two daemons handed the lease to the next tick');
		$this->check(($reasons['stop'] ?? 0) === 1, 'cron/stop ended the last daemon');
		if ($killed !== null && $killed['graceful']) {
			$this->check(($reasons['signal'] ?? 0) === 1, 'SIGTERM ended a daemon with the signal reason');
		}
		if ($killed !== null) {
			$this->check(count(array_filter($starts, fn ($e) => $e['t'] > $killed['t'])) >= 1, 'a tick replaced the killed daemon');
		}
		$this->checkOneDaemonAtATime($starts, $ends, $killed);
		$this->checkRuns($runs, 2.0, $killed);
		$this->checkQuickTicks($starts);
	}

	/**
	 * A waiting daemon with a long idle runs a task that cron/add schedules from another process
	 * at its second, which only the wake transport can make it do.
	 */
	private function scenarioWake(): void
	{
		$this->makeApp('DaemonLifetime="0" DaemonIdle="30"', '<job name="never" schedule="0 0 1 1 ? 2099" task="Application.CronE2ETask" />');
		$this->cli(['cron/migrate']);
		$start = microtime(true);
		$nextTick = $start;
		$daemonUp = null;
		$adds = [];
		while (($now = microtime(true)) < $start + 45.0 && count($adds) < 3) {
			if ($now >= $nextTick) {
				$this->tick();
				$nextTick += TICK;
			}
			$this->reap();
			if ($daemonUp === null && $this->runningDaemon() !== null) {
				$daemonUp = $now;
			}
			// Add a task once the daemon has been waiting for a while, inside one minute.
			$last = $adds ? end($adds)['at'] + 3 : ($daemonUp ?? INF) + 2;
			if ($now >= $last && (int) date('s') <= 50) {
				$at = time() + 4;
				$name = 'wake' . (count($adds) + 1);
				$this->cli(['cron/add', $name, 'probe', '@' . $at]);
				$adds[] = ['name' => $name, 'at' => $at];
			}
			usleep(100000);
		}
		$deadline = microtime(true) + 6.0;
		while (microtime(true) < $deadline) {
			$this->reap();
			usleep(100000);
		}
		$this->stop();

		$events = $this->events();
		$starts = $this->select($events, 'start');
		$ends = $this->select($events, 'end');
		$runs = $this->select($events, 'run');
		$this->transport($starts);
		$this->check(count($starts) === 1, 'one daemon served the whole scenario; ' . count($starts) . ' started');
		$this->check(($starts[0]['watching'] ?? false) === true, 'the daemon watches its wake transport');
		$this->check(count($adds) === 3, 'three tasks were added; ' . count($adds) . ' added');
		foreach ($adds as $add) {
			$ran = array_values(array_filter($runs, fn ($e) => $e['task'] === $add['name']));
			if (!$ran) {
				$this->fail("{$add['name']} (due " . date('H:i:s', $add['at']) . ') never ran');
				continue;
			}
			$late = $ran[0]['t'] - $add['at'];
			$this->line(sprintf('   %s ran %+.2fs from its trigger', $add['name'], $late));
			$this->check($late >= -0.05 && $late <= SLACK, "{$add['name']} ran at its trigger second ({$late}s), so the wake reached the waiting daemon");
			$this->check(count($ran) === 1, "{$add['name']} ran once");
		}
		$this->check(count(array_filter($ends, fn ($e) => $e['reason'] === 'stop')) === 1, 'cron/stop ended the daemon');
		$this->checkQuickTicks($starts);
	}

	/**
	 * A Process task longer than its period runs in `cron/exec` children, one at a time, while a
	 * Serial task keeps its schedule in the daemon; the daemon's status, `cron/top`, and `cron/tasks`
	 * show the running child.
	 */
	private function scenarioProcess(): void
	{
		$this->makeApp('DaemonLifetime="0" DaemonIdle="2"', '<job name="slow" schedule="*:*/3 * * * *" task="Application.CronE2ETask" Mode="Process" Sleep="5" />' .
			'<job name="quick" schedule="*:*/2 * * * *" task="Application.CronE2ETask" />');
		// the runtime path is a directory per configuration file and version in protected/runtime
		$statusGlob = $this->dir . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'cronstatus' . DIRECTORY_SEPARATOR . '*.json';
		$start = microtime(true);
		$nextTick = $start;
		$seen = null;
		$shown = false;
		while (($now = microtime(true)) < $start + 24.0) {
			if ($now >= $nextTick) {
				$this->tick();
				$nextTick += TICK;
			}
			$this->reap();
			if ($seen === null) {
				foreach (glob($statusGlob) ?: [] as $file) {
					$status = json_decode((string) @file_get_contents($file), true);
					foreach ((array) ($status['running'] ?? []) as $run) {
						if (($run['name'] ?? '') === 'slow' && ($run['pid'] ?? null) !== ($status['pid'] ?? null)) {
							$seen = ['status' => $status, 'run' => $run];
						}
					}
				}
			} elseif (!$shown) {
				$shown = true;
				$top = $this->cli(['cron/top']);
				$this->check(str_contains($top, 'Daemon') && str_contains($top, 'Running') && str_contains($top, 'pid ' . $seen['status']['pid']), 'cron/top shows the daemon and the running child');
				$tasks = $this->cli(['cron/tasks']);
				$this->check(str_contains($tasks, 'Process') && preg_match('/pid \d+/', $tasks) === 1, 'cron/tasks shows the Process mode and the running child');
			}
			usleep(100000);
		}
		$this->stop();
		$tasks = $this->cli(['cron/tasks', 'slow']);

		$events = $this->events();
		$starts = $this->select($events, 'start');
		$runs = $this->select($events, 'run');
		$this->transport($starts);
		$this->check(count($starts) === 1, 'one daemon served the scenario; ' . count($starts) . ' started');
		$daemon = $starts[0]['pid'] ?? null;
		$this->check($seen !== null, 'the daemon status listed the running child of slow');
		if ($seen !== null) {
			$this->check(($seen['run']['mode'] ?? '') === 'Process', 'the status lists slow as a Process task');
			$this->check(($seen['status']['role'] ?? '') === 'daemon' && ($seen['status']['pid'] ?? null) === $daemon, 'the daemon published the status');
		}
		$slow = array_values(array_filter($runs, fn ($e) => $e['task'] === 'slow'));
		$done = array_values(array_filter($this->select($events, 'done'), fn ($e) => $e['task'] === 'slow'));
		$this->line('   slow ran ' . count($slow) . ' time(s) in process(es) ' . implode(',', array_column($slow, 'pid')));
		$this->check(count($slow) >= 2, 'slow ran at least twice; ' . count($slow) . ' runs');
		$this->check(count($done) === count($slow), 'each run of slow finished; the daemon waited for its child before it ended');
		foreach ($slow as $i => $run) {
			$this->check($run['pid'] !== $daemon, "run {$i} of slow ran in a child process");
			if (isset($slow[$i + 1], $done[$i])) {
				$this->check($slow[$i + 1]['t'] >= $done[$i]['t'] - 0.05, "run {$i} of slow ended before the next began; the run lock skips a trigger while it runs");
			}
		}
		$quick = array_values(array_filter($runs, fn ($e) => $e['task'] === 'quick'));
		$this->check(count($quick) >= 8, 'quick ran on its schedule beside the child; ' . count($quick) . ' runs');
		foreach ($quick as $run) {
			$late = $run['t'] - floor($run['t'] / 2.0) * 2.0;
			$this->check($run['pid'] === $daemon && $late <= SLACK, sprintf('quick ran on time in the daemon while slow ran (%.2fs late)', $late));
		}
		$this->check(str_contains($tasks, 'ok'), 'cron/tasks shows the last result of slow');
		$this->check(!glob($statusGlob), 'the daemon removed its status when it ended');
		$this->checkQuickTicks($starts);
	}

	/**
	 * Every task feature under one daemon, read from the task events the probe records and the
	 * requests the web server records.
	 */
	private function scenarioFeatures(): void
	{
		$start = time();
		$this->makeApp('DaemonLifetime="0" DaemonIdle="1" LogCronTasks="true"', '', <<<XML
			<service id="json" class="Prado\\Web\\Services\\TJsonService">
				<json id="cron" class="Prado\\Util\\Cron\\TCronMonitorResponse" />
				<json id="metrics" class="Prado\\Util\\Cron\\TCronMonitorResponse" Format="prometheus" />
			</service>
			XML);
		$port = $this->startServer();
		$url = "http://127.0.0.1:{$port}";
		$protected = $this->dir . DIRECTORY_SEPARATOR . 'protected';
		file_put_contents($protected . DIRECTORY_SEPARATOR . 'cmd.php', '<?php file_put_contents(__DIR__ . "/../command.out", "x", FILE_APPEND);');
		$today = date('m-d');
		$window = sprintf('%02d:00-%02d:59', ((int) gmdate('G') + 2) % 24, ((int) gmdate('G') + 3) % 24);
		$out = $this->dir . DIRECTORY_SEPARATOR . 'proc.out';
		$task = 'Application.CronE2ETask';
		$this->writeJobs(<<<XML
			<job name="pinged" schedule="*:*/3 * * * *" task="{$task}" PingBeforeUrl="{$url}/ping/pinged/start" PingSuccessUrl="{$url}/ping/pinged/ok" PingAfterUrl="http://probe.invalid/after" />
			<job name="failing" schedule="*:*/4 * * * *" task="{$task}" FailTimes="1000" PingFailureUrl="{$url}/ping/failing/fail" />
			<job name="flaky" schedule="@{$this->at($start + 4)}" task="{$task}" FailTimes="2" Retries="2" RetryDelay="0.2" />
			<job name="paused" schedule="*:*/2 * * * *" task="{$task}" />
			<job name="late" schedule="@{$this->at($start - 120)}" task="{$task}" StartingDeadline="10" />
			<job name="windowed" schedule="*:*/2 * * * *" task="{$task}" TimeZone="UTC" Between="{$window}" />
			<job name="excepted" schedule="*:*/2 * * * * except {$today}" task="{$task}" />
			<job name="jittered" schedule="*:*/6 * * * *" task="{$task}" Jitter="3" />
			<job name="prioLast" schedule="*:*/5 * * * *" task="{$task}" Priority="20" />
			<job name="prioFirst" schedule="*:*/5 * * * *" task="{$task}" Priority="1" />
			<job name="head" schedule="*:*/7 * * * *" task="{$task}" Then="chained" />
			<job name="chained" schedule="@never" task="{$task}" />
			<job name="compound" schedule="*:*/7 * * * *" task="Prado\\Util\\Cron\\TCompoundCronTask" Tasks="{$task}; @stepRef" />
			<job name="stepRef" schedule="@never" task="{$task}" />
			<job name="command" schedule="*:*/5 * * * *" task="Prado\\Util\\Cron\\TCommandCronTask" Command="@php {$protected}/cmd.php" />
			<job name="hook" schedule="*:*/5 * * * *" task="Prado\\Util\\Cron\\THttpCronTask" Url="{$url}/hook" Method="POST" Body="hello" />
			<job name="delayed" schedule="every 3 seconds after end from @{$this->at($start)}" task="{$task}" Sleep="1" />
			<job name="slowproc" schedule="*:*/6 * * * *" task="{$task}" Mode="Process" Sleep="2.5" WarnDuration="1" Echo="hello" ProcessOutputFile="{$out}" ProcessPriority="5" ProcessMemoryLimit="96M" />
			<job name="zoned" schedule="0 9 * * *" task="{$task}" TimeZone="Asia/Tokyo" />
			XML);
		$this->cli(['cron/migrate']);
		$this->cli(['cron/pause', 'paused']);
		$t0 = microtime(true);
		$nextTick = $t0;
		$resumed = null;
		$checked = false;
		while (($now = microtime(true)) < $t0 + 30.0) {
			if ($now >= $nextTick) {
				$this->tick();
				$nextTick += TICK;
			}
			$this->reap();
			if ($resumed === null && $now >= $t0 + 12) {
				$this->cli(['cron/resume', 'paused']);
				$resumed = microtime(true);
			}
			if (!$checked && $now >= $t0 + 20) {
				$checked = true;
				$report = json_decode((string) $this->httpGet("/index.php?json=cron"), true);
				$this->check(is_array($report) && ($report['daemon']['role'] ?? null) === 'daemon', 'the monitor report shows the daemon');
				$this->check(is_array($report) && isset($report['tasks']['slowproc']) && $report['tasks']['slowproc']['mode'] === 'Process', 'the monitor report lists the tasks');
				$metrics = (string) $this->httpGet("/index.php?json=metrics");
				$this->check(str_contains($metrics, 'prado_cron_daemon_up{module="cron"} 1'), 'the monitor metrics show the daemon up');
				$this->check(str_contains($metrics, 'prado_cron_task_runs_total{module="cron",task="pinged"}'), 'the monitor metrics count the runs');
				$this->check(str_contains($this->cli(['cron/next', 'zoned']), 'JST'), 'cron/next shows a task in its time zone');
				$this->check(str_contains($this->cli(['cron/top']), 'Daemon'), 'cron/top shows the daemon');
				$cliReport = json_decode($this->cli(['--quiet=1', 'cron/report']), true);
				$this->check(is_array($cliReport) && ($cliReport['daemon']['role'] ?? null) === 'daemon' && isset($cliReport['tasks']['pinged']), 'cron/report prints the monitor report');
				$this->check(str_contains($this->cli(['--quiet=1', 'cron/report', 'prometheus']), 'prado_cron_daemon_up{module="cron"} 1'), 'cron/report prints the metrics');
			}
			usleep(100000);
		}
		$this->stop();
		$log = $this->cli(['cron/log']);
		$this->stopServer();

		$events = $this->events();
		$starts = $this->select($events, 'start');
		$daemon = $starts[0]['pid'] ?? null;
		$this->check(count($starts) === 1, 'one daemon served the scenario; ' . count($starts) . ' started');
		$runs = fn (string $name) => array_values(array_filter($events, fn ($e) => $e['e'] === 'run' && $e['task'] === $name));
		$ends = fn (string $name) => array_values(array_filter($events, fn ($e) => $e['e'] === 'tend' && $e['task'] === $name));
		$skips = fn (string $name) => array_values(array_filter($events, fn ($e) => $e['e'] === 'tskip' && $e['task'] === $name));
		$http = fn (string $path) => array_values(array_filter($events, fn ($e) => $e['e'] === 'http' && $e['path'] === $path));

		$ok = array_filter($ends('pinged'), fn ($e) => $e['result'] === 'ok');
		$this->check(count($ok) >= 5, 'pinged ran on its schedule; ' . count($ok) . ' runs');
		$this->check(count($http('/ping/pinged/start')) === count($ends('pinged')), 'each run of pinged pinged before it started');
		$this->check(count($http('/ping/pinged/ok')) === count($ok), 'each successful run of pinged pinged its success URL');
		$seam = array_filter($events, fn ($e) => $e['e'] === 'tping' && $e['task'] === 'pinged' && $e['ping'] === 'after' && $e['result'] === 'ok');
		$this->check(count($seam) === count($ok), 'the fxCronTaskPing handler delivered the after ping of each run');

		$failed = array_filter($ends('failing'), fn ($e) => $e['result'] === 'error');
		$this->check(count($failed) >= 4 && count($failed) === count($ends('failing')), 'failing failed each run');
		$this->check(count($http('/ping/failing/fail')) === count($failed), 'each failed run pinged its failure URL');

		$retries = array_column(array_filter($events, fn ($e) => $e['e'] === 'tretry' && $e['task'] === 'flaky'), 'attempt');
		$flaky = $ends('flaky');
		$this->check($retries === [1, 2], 'flaky was tried again twice; ' . json_encode($retries));
		$this->check(count($flaky) === 1 && $flaky[0]['result'] === 'ok' && $flaky[0]['attempt'] === 3, 'flaky succeeded on its third attempt as one run');

		$pausedSkips = array_filter($skips('paused'), fn ($e) => $e['reason'] === 'paused');
		$this->check(count($pausedSkips) >= 3, 'the paused task skipped its triggers; ' . count($pausedSkips));
		$this->check($resumed !== null && !array_filter($runs('paused'), fn ($e) => $e['t'] < $resumed), 'the paused task did not run before it was resumed');
		$this->check(count($runs('paused')) >= 3, 'the resumed task ran; ' . count($runs('paused')));

		$this->check(array_column($skips('late'), 'reason') === ['deadline'] && !$runs('late'), 'a trigger past its starting deadline was skipped once');
		$this->check(count($skips('windowed')) >= 5 && !array_diff(array_column($skips('windowed'), 'reason'), ['window']) && !$runs('windowed'), 'a task outside its time window was skipped');
		$this->check(!$runs('excepted') && !$skips('excepted'), 'a task on an excepted date never came due');

		$jitter = $runs('jittered');
		$this->check(count($jitter) >= 3, 'jittered ran; ' . count($jitter));
		foreach ($jitter as $run) {
			$offset = $run['t'] - floor($run['t'] / 6.0) * 6.0;
			$this->check($offset <= 3 + SLACK, sprintf('a jittered run is within its jitter (%.2fs)', $offset));
		}

		// A sweep runs its tasks in priority order; the runs of the daemon between two sweep starts are one sweep's.
		$sweeps = [];
		$current = -1;
		foreach ($events as $e) {
			if ($e['e'] === 'sweep' && $e['pid'] === $daemon) {
				$sweeps[++$current] = [];
			} elseif ($current >= 0 && $e['e'] === 'run' && in_array($e['task'], ['prioFirst', 'prioLast'], true)) {
				$sweeps[$current][] = $e['task'];
			}
		}
		$together = array_filter($sweeps, fn ($names) => count($names) === 2);
		$this->check(count($together) >= 3, 'the priority tasks ran in the same sweeps; ' . count($together));
		$this->check(!array_filter($together, fn ($names) => $names !== ['prioFirst', 'prioLast']), 'the lower priority runs first in its sweep');

		$chained = array_filter($events, fn ($e) => $e['e'] === 'tstart' && $e['task'] === 'chained');
		$this->check(count($chained) === count($ends('head')) && count($chained) >= 3, 'chained ran after each run of head');
		$this->check(!array_filter($chained, fn ($e) => $e['chain'] !== ['head'] || !$e['manual']), 'a chained run carries its chain');

		$this->check(count($runs('compound#1')) === count($ends('compound')) && count($runs('stepRef')) === count($ends('compound')) && count($ends('compound')) >= 3, 'the compound task ran both of its steps in each run');
		$this->check(!array_filter($ends('compound'), fn ($e) => $e['result'] !== 'ok') && !$ends('stepRef'), 'the compound runs succeeded as one run each');

		$this->check(count($ends('command')) >= 4 && !array_filter($ends('command'), fn ($e) => $e['result'] !== 'ok'), 'the command task ran');
		$this->check(strlen((string) @file_get_contents($this->dir . DIRECTORY_SEPARATOR . 'command.out')) === count($ends('command')), 'each command run wrote its output');
		$hooks = $http('/hook');
		$this->check(count($hooks) === count($ends('hook')) && count($hooks) >= 4 && !array_filter($hooks, fn ($e) => $e['method'] !== 'POST' || $e['body'] !== 'hello'), 'the HTTP task posted its body each run');

		$delayed = $runs('delayed');
		$done = array_values(array_filter($events, fn ($e) => $e['e'] === 'done' && $e['task'] === 'delayed'));
		$this->check(count($delayed) >= 5, 'delayed ran; ' . count($delayed));
		for ($i = 1; $i < count($delayed); $i++) {
			$gap = $delayed[$i]['t'] - $done[$i - 1]['t'];
			// The end is stored in whole seconds, so the next trigger is 2 to 3 s after the end.
			$this->check($gap >= 1.95 && $gap <= 3 + SLACK, sprintf('an after end run starts 3 s after the second the previous run ended (%.2fs)', $gap));
		}

		$slow = $runs('slowproc');
		$this->check(count($slow) >= 3 && !array_filter($slow, fn ($e) => $e['pid'] === $daemon), 'slowproc ran in child processes');
		$this->check(!array_filter($slow, fn ($e) => $e['memory'] !== '96M'), 'the child had its memory limit');
		if (!$this->windows) {
			$this->check(!array_filter($slow, fn ($e) => ($e['nice'] ?? null) !== 5), 'the child had its priority; ' . json_encode(array_column($slow, 'nice')));
		}
		$overdue = array_values(array_filter($events, fn ($e) => $e['e'] === 'toverdue' && $e['task'] === 'slowproc'));
		$slowDone = array_values(array_filter($events, fn ($e) => $e['e'] === 'done' && $e['task'] === 'slowproc'));
		$this->check(count($overdue) === count($ends('slowproc')), 'each slowproc run was overdue once');
		for ($i = 0; $i < min(count($overdue), count($slowDone)); $i++) {
			$this->check($overdue[$i]['t'] < $slowDone[$i]['t'], 'the warning came while the run was running');
		}
		$this->check(substr_count((string) @file_get_contents($out), 'hello') === count($slow), 'the child wrote its output to the ProcessOutputFile');

		$this->check(str_contains($log, 'error') && str_contains($log, 'ok'), 'cron/log shows the results of the runs');
		$this->checkQuickTicks($starts);
	}

	/**
	 * Replace and Queue on Process tasks under one daemon.
	 */
	private function scenarioOverlap(): void
	{
		$this->makeApp('DaemonLifetime="0" DaemonIdle="1"', '<job name="replacer" schedule="*:*/3 * * * *" task="Application.CronE2ETask" Mode="Process" Sleep="8" Overlap="Replace" />' .
			'<job name="queued" schedule="*:*/2 * * * *" task="Application.CronE2ETask" Mode="Process" Sleep="3" Overlap="Queue" />');
		$t0 = microtime(true);
		$nextTick = $t0;
		while (($now = microtime(true)) < $t0 + 20.0) {
			if ($now >= $nextTick) {
				$this->tick();
				$nextTick += TICK;
			}
			$this->reap();
			usleep(100000);
		}
		$this->stop();

		$events = $this->events();
		$starts = $this->select($events, 'start');
		$this->check(count($starts) === 1, 'one daemon served the scenario; ' . count($starts) . ' started');
		$results = array_count_values(array_column(array_filter($events, fn ($e) => $e['e'] === 'tend' && $e['task'] === 'replacer'), 'result'));
		$this->line('   replacer results: ' . json_encode($results));
		$this->check(($results['replaced'] ?? 0) >= 4, 'each replacer trigger replaced the running child');
		$this->check(!array_filter(array_filter($events, fn ($e) => $e['e'] === 'done' && $e['task'] === 'replacer'), fn ($e) => $e['t'] < $t0 + 19), 'a replaced child did not finish its work');

		$runs = array_values(array_filter($events, fn ($e) => $e['e'] === 'run' && $e['task'] === 'queued'));
		$done = array_values(array_filter($events, fn ($e) => $e['e'] === 'done' && $e['task'] === 'queued'));
		$this->check(count($runs) >= 4, 'queued ran back to back; ' . count($runs) . ' runs');
		$this->check(!array_filter(array_filter($events, fn ($e) => $e['e'] === 'tskip' && $e['task'] === 'queued'), fn ($e) => $e['reason'] !== 'running'), 'a queued trigger is skipped only while another waits');
		for ($i = 1; $i < count($runs); $i++) {
			$gap = $runs[$i]['t'] - ($done[$i - 1]['t'] ?? INF);
			$this->check($gap >= -0.05 && $gap <= SLACK + 0.5, sprintf('a queued run starts when the previous one ends (%.2fs)', $gap));
		}
		$this->checkQuickTicks($starts);
	}

	/**
	 * Crontab ticks without a daemon: each `cron/run` (with `--cron`, so the runner's TERM does not
	 * matter) serves the rest of its minute at the trigger seconds; the ticks overlap, and the claim and
	 * the run lock let one of them run each trigger.
	 */
	private function scenarioTick(): void
	{
		$this->makeApp('Daemon="false"', '<job name="every5" schedule="*:*/5 * * * *" task="Application.CronE2ETask" />');
		$start = microtime(true);
		$nextTick = $start;
		while (($now = microtime(true)) < $start + 24.0) {
			if ($now >= $nextTick) {
				$this->tick(['--cron']);
				$nextTick += TICK;
			}
			$this->reap();
			usleep(100000);
		}
		// A tick serves until its minute ends.
		$deadline = microtime(true) + 70.0;
		while (microtime(true) < $deadline && array_filter($this->procs, fn ($p) => $p['ended'] === null)) {
			$this->reap();
			usleep(200000);
		}
		foreach ($this->procs as $p) {
			if ($p['ended'] === null) {
				$this->fail("cron/run tick {$p['pid']} still runs after its minute");
			}
		}

		$events = $this->events();
		$this->check(!$this->select($events, 'start'), 'no daemon started');
		$runs = array_values(array_filter($this->select($events, 'run'), fn ($e) => $e['task'] === 'every5'));
		$pids = array_unique(array_column($runs, 'pid'));
		$this->line('   every5 ran ' . count($runs) . ' time(s) in ' . count($pids) . ' tick(s)');
		$this->check(count($pids) >= 2, 'more than one tick ran the task');
		$this->checkRuns($runs, 5.0, null, 6);
		$skips = array_count_values(array_column(array_filter($events, fn ($e) => $e['e'] === 'tskip' && $e['task'] === 'every5'), 'reason'));
		$this->line('   skipped triggers: ' . json_encode($skips));
		$this->check(!array_diff(array_keys($skips), ['claimed']), 'an overlapping tick only finds a trigger claimed by another');
	}

	// ---------------------------------------------------------------- checks

	private function checkOneDaemonAtATime(array $starts, array $ends, ?array $killed): void
	{
		$endOf = [];
		foreach ($ends as $e) {
			$endOf[$e['pid']] = $e['t'];
		}
		if ($killed !== null && !$killed['graceful']) {
			$endOf[$killed['pid']] ??= $killed['t'];
		}
		for ($i = 1; $i < count($starts); $i++) {
			$prev = $starts[$i - 1]['pid'];
			// The previous daemon releases the lease before its end event; the new one starts after acquiring it.
			$this->check(isset($endOf[$prev]), "daemon {$prev} ended");
		}
	}

	/**
	 * Assigns each run to its trigger, the period grid second at or before it, and checks that no
	 * trigger runs twice, none is late, and none is skipped; a run after a killed daemon may catch
	 * up a trigger the kill missed.
	 */
	private function checkRuns(array $runs, float $period, ?array $killed, int $minRuns = 10): void
	{
		$this->check(count($runs) >= $minRuns, 'the task ran on its schedule; ' . count($runs) . ' runs');
		$inKillWindow = fn (float $t) => $killed !== null && $t >= $killed['t'] && $t <= $killed['t'] + 2 * TICK + SLACK;
		$seen = [];
		$previous = null;
		foreach ($runs as $run) {
			$trigger = floor($run['t'] / $period) * $period;
			$late = $run['t'] - $trigger;
			$at = date('H:i:s', (int) $trigger);
			$this->check(!isset($seen[(string) $trigger]), "the trigger at {$at} ran once (again by {$run['pid']})");
			$seen[(string) $trigger] = true;
			if (!$inKillWindow($run['t'])) {
				$this->check($late <= SLACK, sprintf('the trigger at %s ran on time (%.2fs late by %d)', $at, $late, $run['pid']));
				if ($previous !== null) {
					$this->check($trigger - $previous <= $period, sprintf('no trigger was skipped before %s (previous %s)', $at, date('H:i:s', (int) $previous)));
				}
			}
			$previous = $trigger;
		}
	}

	private function checkQuickTicks(array $starts): void
	{
		$daemons = array_flip(array_column($starts, 'pid'));
		$slow = [];
		foreach ($this->procs as $p) {
			if (!isset($daemons[$p['pid']]) && $p['ended'] !== null && $p['ended'] - $p['started'] > QUICK_EXIT) {
				$slow[] = $p['pid'];
			}
		}
		$this->check(!$slow, 'a tick that is not elected exits at once; slow: ' . implode(',', $slow));
	}

	private function transport(array $starts): void
	{
		if ($starts) {
			$this->line(sprintf('   wake transport: %s (%s), watching: %s', $starts[0]['wake'] ?? 'none', $starts[0]['transport'] ?? '-', json_encode($starts[0]['watching'])));
		}
	}

	// ---------------------------------------------------------------- processes

	private function makeApp(string $daemonAttributes, string $jobs, string $services = ''): void
	{
		$base = getenv('RUNNER_TEMP') ?: sys_get_temp_dir();
		$this->dir = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'pce' . bin2hex(random_bytes(3));
		$protected = $this->dir . DIRECTORY_SEPARATOR . 'protected';
		mkdir($protected . DIRECTORY_SEPARATOR . 'runtime', 0777, true);
		foreach (['CronE2EProbe.php', 'CronE2ETask.php'] as $file) {
			copy(__DIR__ . '/app/' . $file, $protected . DIRECTORY_SEPARATOR . $file);
		}
		$this->log = $this->dir . DIRECTORY_SEPARATOR . 'events.jsonl';
		$db = $this->dir . DIRECTORY_SEPARATOR . 'cron.db';
		$services = ($services === '') ? '' : "<services>{$services}</services>";
		$daemonAttributes = str_contains($daemonAttributes, 'Daemon=') ? $daemonAttributes : 'Daemon="true" ' . $daemonAttributes;
		file_put_contents($protected . DIRECTORY_SEPARATOR . 'application.xml', <<<XML
			<?xml version="1.0" encoding="utf-8"?>
			<application id="crone2e" mode="Debug">
				<modules>
					<module id="db" class="Prado\\Data\\TDataSourceConfig">
						<database ConnectionString="sqlite:{$db}" />
					</module>
					<module id="probe" class="Application.CronE2EProbe" LogFile="{$this->log}" />
					<module id="cron" class="Prado\\Util\\Cron\\TDbCronManager" ConnectionID="db" {$daemonAttributes}>
						{$jobs}<!--jobs-->
					</module>
				</modules>
				{$services}
			</application>
			XML);
		$autoload = var_export(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php', true);
		file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'index.php', "<?php\nrequire {$autoload};\n(new Prado\\TApplication(__DIR__ . '/protected'))->run();\n");
		$this->procs = [];
		$this->line("   app: {$protected}");
	}

	/** Adds jobs to the cron module of the application {@see makeApp} wrote. */
	private function writeJobs(string $jobs): void
	{
		$file = $this->dir . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'application.xml';
		file_put_contents($file, str_replace('<!--jobs-->', $jobs, (string) file_get_contents($file)));
	}

	/** Returns a Unix time for a schedule. */
	private function at(int|float $time): int
	{
		return (int) $time;
	}

	/**
	 * Starts a PHP web server on the application directory whose router records each request as an
	 * `http` event and serves `/index.php` as the application.
	 */
	private function startServer(): int
	{
		$probe = stream_socket_server('tcp://127.0.0.1:0');
		$name = (string) stream_socket_get_name($probe, false);
		fclose($probe);
		$port = (int) substr((string) strrchr($name, ':'), 1);
		$log = var_export($this->log, true);
		file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'router.php', <<<PHP
			<?php
			\$path = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);
			if (\$path === '/index.php') {
				return false;
			}
			file_put_contents({$log}, json_encode(['e' => 'http', 'path' => \$path, 'method' => \$_SERVER['REQUEST_METHOD'], 'body' => file_get_contents('php://input'), 'pid' => getmypid(), 't' => microtime(true)]) . "\\n", FILE_APPEND | LOCK_EX);
			echo 'ok';
			PHP);
		$out = $this->dir . DIRECTORY_SEPARATOR . 'server.out';
		$handle = fopen($out, 'wb');
		$proc = proc_open([PHP_BINARY, '-S', "127.0.0.1:{$port}", 'router.php'], [0 => $this->devNull('r'), 1 => $handle, 2 => $handle], $pipes, $this->dir);
		fclose($handle);
		$this->server = ['proc' => $proc, 'port' => $port];
		$deadline = microtime(true) + 10;
		while (microtime(true) < $deadline && !($socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2))) {
			usleep(100000);
		}
		if (empty($socket)) {
			$this->fail('the PHP web server did not start');
		} else {
			fclose($socket);
		}
		return $port;
	}

	private function stopServer(): void
	{
		if ($this->server !== null) {
			proc_terminate($this->server['proc']);
			proc_close($this->server['proc']);
			$this->server = null;
		}
	}

	/** Requests a path of the scenario's web server. */
	private function httpGet(string $path): ?string
	{
		$body = @file_get_contents('http://127.0.0.1:' . ($this->server['port'] ?? 0) . $path, false, stream_context_create(['http' => ['timeout' => 20]]));
		if ($body === false) {
			$this->fail("GET {$path} failed");
			return null;
		}
		return $body;
	}

	/** Removes a passed scenario's application directory, which {@see makeApp} created. */
	private function removeApp(string $dir): void
	{
		if ($dir === '' || !str_starts_with(basename($dir), 'pce') || !is_dir($dir)) {
			return;
		}
		$items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($items as $item) {
			$item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
		}
		@rmdir($dir);
	}

	/** Starts a cron/run tick in the background, as a crontab entry does, with options before the action. */
	private function tick(array $options = []): void
	{
		$n = count($this->procs);
		$out = $this->dir . DIRECTORY_SEPARATOR . "tick{$n}.out";
		$handle = fopen($out, 'wb'); // one handle for both streams: two append handles overwrite each other on Windows
		$proc = proc_open(array_merge([PHP_BINARY, $this->cli, '-d=' . $this->dir], $options, ['cron/run']), [0 => $this->devNull('r'), 1 => $handle, 2 => $handle], $pipes, $this->cronCwd(), $this->cronEnv());
		fclose($handle);
		if (!is_resource($proc)) {
			$this->fail('a cron/run tick did not start');
			return;
		}
		$this->procs[] = ['proc' => $proc, 'pid' => proc_get_status($proc)['pid'], 'started' => microtime(true), 'ended' => null, 'out' => $out];
	}

	/** Runs a prado-cli command to completion and returns its output. */
	private function cli(array $args, float $timeout = 30.0): string
	{
		$out = $this->dir . DIRECTORY_SEPARATOR . 'cli-' . str_replace('/', '-', $args[0]) . '-' . bin2hex(random_bytes(2)) . '.out';
		$handle = fopen($out, 'wb');
		$proc = proc_open(array_merge([PHP_BINARY, $this->cli, '-d=' . $this->dir], $args), [0 => $this->devNull('r'), 1 => $handle, 2 => $handle], $pipes, $this->cronCwd(), $this->cronEnv());
		fclose($handle);
		$deadline = microtime(true) + $timeout;
		while (($status = proc_get_status($proc))['running'] && microtime(true) < $deadline) {
			$this->reap();
			usleep(50000);
		}
		if ($status['running']) {
			proc_terminate($proc);
			$this->fail(implode(' ', $args) . " did not finish in {$timeout}s");
		}
		proc_close($proc);
		$text = (string) @file_get_contents($out);
		$this->line('   $ prado-cli ' . implode(' ', $args) . ' -> ' . substr(trim(preg_replace('/\e\[[0-9;]*m|\s+/', ' ', $text)), 0, 600));
		if (preg_match('/Fatal|Uncaught|Stack trace/i', $text)) {
			$this->fail(implode(' ', $args) . ' failed: ' . $text);
		}
		return $text;
	}

	private function reap(): void
	{
		foreach ($this->procs as $i => $p) {
			if ($p['ended'] === null && !proc_get_status($p['proc'])['running']) {
				$this->procs[$i]['ended'] = microtime(true);
			}
		}
	}

	/** Stops the daemon through cron/stop and waits for every tick to exit. */
	private function stop(): void
	{
		$this->cli(['cron/stop', '10']);
		$deadline = microtime(true) + 15.0;
		while (microtime(true) < $deadline && array_filter($this->procs, fn ($p) => $p['ended'] === null)) {
			$this->reap();
			usleep(100000);
		}
		foreach ($this->procs as $p) {
			if ($p['ended'] === null) {
				$this->fail("cron/run process {$p['pid']} still runs after cron/stop");
			}
		}
	}

	private function finish(): void
	{
		foreach ($this->procs as $i => $p) {
			if ($p['ended'] === null) {
				proc_terminate($p['proc'], 9);
			}
			proc_close($p['proc']);
			$text = implode("\n", preg_grep('/planned failure|exited with code|outlived its timeout/', explode("\n", (string) @file_get_contents($p['out'])), PREG_GREP_INVERT));
			if (preg_match('/Fatal|Exception|Stack trace|Warning|Deprecated/i', $text)) {
				$this->fail("cron/run process {$p['pid']} reported an error:\n" . $text);
			}
		}
	}

	private function runningDaemon(): ?int
	{
		$running = null;
		foreach ($this->events() as $e) {
			if ($e['e'] === 'start') {
				$running = $e['pid'];
			} elseif ($e['e'] === 'end' && $e['pid'] === $running) {
				$running = null;
			}
		}
		return $running;
	}

	/** Kills a daemon: SIGTERM where POSIX signals exist, otherwise process termination. */
	private function kill(int $pid): bool
	{
		if (function_exists('posix_kill')) {
			return posix_kill($pid, 15);
		}
		foreach ($this->procs as $p) {
			if ($p['pid'] === $pid) {
				proc_terminate($p['proc']);
			}
		}
		return false;
	}

	/** A crontab runs its command with a minimal environment, outside the project directory. */
	private function cronEnv(): ?array
	{
		if ($this->windows) {
			return null; // the Task Scheduler passes the account's environment
		}
		return ['PATH' => '/usr/bin:/bin:/usr/local/bin', 'HOME' => (string) (getenv('HOME') ?: '/'), 'SHELL' => '/bin/sh', 'LOGNAME' => (string) getenv('USER')];
	}

	private function cronCwd(): string
	{
		return $this->windows ? (string) getenv('SystemRoot') : (string) (getenv('HOME') ?: '/');
	}

	private function devNull(string $mode): array
	{
		return ['file', $this->windows ? 'NUL' : '/dev/null', $mode];
	}

	// ---------------------------------------------------------------- log

	private function events(): array
	{
		$events = [];
		foreach (@file($this->log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
			if (is_array($e = json_decode($line, true))) {
				$events[] = $e;
			}
		}
		return $events;
	}

	private function select(array $events, string $type): array
	{
		return array_values(array_filter($events, fn ($e) => $e['e'] === $type));
	}

	private function dump(): void
	{
		$t0 = null;
		$this->line('   --- events');
		foreach ($this->events() as $e) {
			$t0 ??= $e['t'];
			$this->line(sprintf('   %7.2f %6d %s', $e['t'] - $t0, $e['pid'], json_encode(array_diff_key($e, ['t' => 0, 'pid' => 0]))));
		}
		$this->line('   --- processes');
		foreach ($this->procs as $p) {
			$this->line(sprintf('   %6d %.2fs', $p['pid'], ($p['ended'] ?? microtime(true)) - $p['started']));
		}
	}

	// ---------------------------------------------------------------- output

	private function check(bool $ok, string $message): void
	{
		if (!$ok) {
			$this->fail($message);
		}
	}

	private function fail(string $message): void
	{
		$this->failures[] = $message;
		$this->line('   FAIL ' . $message);
	}

	private function line(string $text): void
	{
		fwrite(STDOUT, $text . PHP_EOL);
	}

	private function ci(): bool
	{
		return getenv('GITHUB_ACTIONS') === 'true';
	}
}

$arg = $argv[1] ?? 'all';
$scenarios = $arg === 'all' ? ['handoff', 'wake', 'process', 'features', 'overlap', 'tick'] : [$arg];
if (array_diff($scenarios, ['handoff', 'wake', 'process', 'features', 'overlap', 'tick'])) {
	fwrite(STDERR, "Usage: php tests/cron-daemon/run.php [handoff|wake|process|features|overlap|tick|all]\n");
	exit(2);
}
exit((new CronDaemonE2E())->run($scenarios));
