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
 * `cron/tasks`.
 *
 * Usage: php tests/cron-daemon/run.php [handoff|wake|process|all]
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
	private function checkRuns(array $runs, float $period, ?array $killed): void
	{
		$this->check(count($runs) >= 10, 'the task ran on its schedule; ' . count($runs) . ' runs');
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

	private function makeApp(string $daemonAttributes, string $jobs): void
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
		file_put_contents($protected . DIRECTORY_SEPARATOR . 'application.xml', <<<XML
			<?xml version="1.0" encoding="utf-8"?>
			<application id="crone2e" mode="Debug">
				<modules>
					<module id="db" class="Prado\\Data\\TDataSourceConfig">
						<database ConnectionString="sqlite:{$db}" />
					</module>
					<module id="probe" class="Application.CronE2EProbe" LogFile="{$this->log}" />
					<module id="cron" class="Prado\\Util\\Cron\\TDbCronManager" ConnectionID="db" Daemon="true" {$daemonAttributes}>
						{$jobs}
					</module>
				</modules>
			</application>
			XML);
		$this->procs = [];
		$this->line("   app: {$protected}");
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

	/** Starts a cron/run tick in the background, as a crontab entry does. */
	private function tick(): void
	{
		$n = count($this->procs);
		$out = $this->dir . DIRECTORY_SEPARATOR . "tick{$n}.out";
		$proc = proc_open([PHP_BINARY, $this->cli, '-d=' . $this->dir, 'cron/run'], [0 => $this->devNull('r'), 1 => ['file', $out, 'w'], 2 => ['file', $out, 'a']], $pipes, $this->cronCwd(), $this->cronEnv());
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
		$proc = proc_open(array_merge([PHP_BINARY, $this->cli, '-d=' . $this->dir], $args), [0 => $this->devNull('r'), 1 => ['file', $out, 'w'], 2 => ['file', $out, 'a']], $pipes, $this->cronCwd(), $this->cronEnv());
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
		$this->line('   $ prado-cli ' . implode(' ', $args) . ' -> ' . trim(preg_replace('/\e\[[0-9;]*m|\s+/', ' ', $text)));
		if (preg_match('/Fatal|Exception|Stack trace/i', $text)) {
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
			$text = (string) @file_get_contents($p['out']);
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
$scenarios = $arg === 'all' ? ['handoff', 'wake', 'process'] : [$arg];
if (array_diff($scenarios, ['handoff', 'wake', 'process'])) {
	fwrite(STDERR, "Usage: php tests/cron-daemon/run.php [handoff|wake|process|all]\n");
	exit(2);
}
exit((new CronDaemonE2E())->run($scenarios));
