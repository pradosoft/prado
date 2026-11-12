# Util/Cron/TCronTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronTask`**

## Class Info
**Location:** `framework/Util/Cron/TCronTask.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TCronTask is the abstract base class for all cron tasks. Subclasses must implement the `execute()` method to perform the actual task logic. It extends TApplicationComponent and provides scheduling, timing, and persistence capabilities for tasks.

## Key Properties/Methods

- `getName()` / `setName($name)` - The unique name of the task
- `getSchedule()` / `setSchedule($schedule)` - The cron-style schedule expression; setting it clears the cached next trigger time
- `getUserName()` / `setUserName($username)` - The user ID executing the task
- `getModuleId()` / `setModuleId($moduleId)` - The utility module for the task
- `getModule()` - Gets the module instance by module ID
- `getAutoRemove()` / `setAutoRemove(?bool $autoRemove)` - Whether `TDbCronManager` removes the task from its table after it runs; `TCronModule` ignores it; `null` (default) lets a runtime task default to `true` (4.4.0)
- `getProcessCount()` / `setProcessCount($count)` - Number of times the task has run
- `getLastExecTime()` / `setLastExecTime($v)` - Last execution timestamp; setting it clears the cached next trigger time
- `getNextTriggerTime(?int $time = null)` - The next trigger after `$time`, computed each call; without `$time`, the next trigger after `LastExecTime`, computed once and cached (4.4.0 parameter and cache)
- `getIsPending(?int $time = null)` - Whether `$time` (default: now from the `time()` seam) is at or after the next trigger time
- `getScheduler()` - Gets the TTimeScheduler instance
- `resetTaskLastExecTime()` - Resets lastExecTime to prevent false triggers
- `execute($cronModule)` - Abstract method; implement to run task logic
- `getTimeout()` / `setTimeout($seconds)` - Seconds one run may take, or null for the module's `TaskTimeout` (4.4.0); set per job with `Timeout="60"`
- `getMode()` / `setMode($mode)` - How a sweep runs the task, a [`TCronTaskMode`](#mode-and-overlap-440) value; default `Serial` (4.4.0)
- `getOverlap()` / `setOverlap($value)` - What a trigger does while the previous run holds the run lock, a `TCronTaskOverlap` value: `Skip` (default), `Allow`, `Replace`, or `Queue`; `true` is `Allow` and `false` is `Skip` (4.4.0)
- `getProcessOutputFile()`, `getProcessPriority()`, `getProcessMemoryLimit()` and setters - For a Process task's child: the file its output and errors are appended to (null for the cron process's output), its scheduling priority (−20 to 19, POSIX nice; a Windows priority class), and its `memory_limit` (`512M`, `-1`) (4.4.0)
- `getWarnDuration()` / `setWarnDuration($seconds)` - The seconds after which a run is overdue: a warning, the run's status marked, and `onTaskOverdue` (4.4.0)
- `getTimeZone()` / `setTimeZone($timeZone)` - The time zone of the schedule, passed to the scheduler; null for the application time zone; setting it clears the cached next trigger time (4.4.0)
- `getStartingDeadline()` / `setStartingDeadline($seconds)` - The seconds after its trigger a run may start; a later triggered run is skipped with the result `missed`; null (default) for no deadline (4.4.0)
- `getDefaultTimeZone()` / `setDefaultTimeZone($timeZone)` - The zone the cron module gives a task without its own `TimeZone`; not serialized. `getScheduleTimeZone()` is `TimeZone ?? DefaultTimeZone`, the zone of the scheduler (4.4.0)
- `getJitter()` / `setJitter($seconds)` - The most seconds each trigger is delayed: `crc32(name . "\0" . trigger) % (Jitter + 1)`, so every process computes the same time; keep it below the time between triggers. `getNextTriggerTimes($count, $time)` applies it (4.4.0)
- `getRetries()`, `getRetryDelay()`, `getRetryBackoff()` and setters - A failed run is tried again up to `Retries` times (default 0); `getRetryWait($retry)` is `RetryDelay` (default 0) × `RetryBackoff` (default 2)^(retry−1) seconds (4.4.0)
- `getBetween()` / `setBetween($window)`, `getUnlessBetween()` / `setUnlessBetween($window)` - `HH:MM-HH:MM` (or with seconds) times of day in the schedule zone that the runs are limited to, or kept out of; a start after the end spans midnight, and the end is inside. `getIsInWindow($time)` tests a time (4.4.0)
- `getDescription()` / `setDescription($text)` - What the task does, shown by `cron/tasks` (4.4.0)
- `getPingBeforeUrl()`, `getPingAfterUrl()`, `getPingSuccessUrl()`, `getPingFailureUrl()` and their setters - The URLs pinged before a run, after every run, after a run without an exception, and after a failed run; `getPingUrl($ping)` reads one by `PING_BEFORE`, `PING_AFTER`, `PING_SUCCESS`, or `PING_FAILURE` (4.4.0). See [TCronModule](TCronModule.md#task-events-and-pings-440).
- `getCancellationToken()` / `setCancellationToken($token)` - The `TCancellationToken` of the current run, set by `TCronModule::runTask()` and cleared after; null outside a run or without a deadline (4.4.0). Pass it to `TFuture::await()` or call `throwIfCancelled()` at checkpoints.
- `time()` - Protected clock seam wrapping `\time()`; override in subclasses and tests to control "now" (4.4.0)

## Mode and Overlap (4.4.0)

`TCronTaskMode` (`Prado\Util\Cron\TCronTaskMode`, a `TEnumerable`) is the task's `Mode`; the value is case-insensitive in configuration (`Mode="parallel"`):

| Mode | Runs |
|---|---|
| `Serial` | alone in the cron process; it starts after the running tasks finish, and the sweep waits for it |
| `Parallel` | in a fiber beside the other Parallel tasks, at most the module's `Concurrency` at once; the sweep waits for it |
| `Process` | in a child `prado-cli cron/exec <task>` process; the sweep does not wait, the cron process waits before it exits |

`TCronTaskOverlap` is the task's `Overlap`. Unless it is `Allow`, a run holds the task's run lock (`TCronModule::getTaskLockName()`); a trigger that comes while another run holds it:

| Overlap | The trigger |
|---|---|
| `Skip` | is skipped: the task advances to its next trigger without a run |
| `Allow` | takes no lock and runs beside the previous run, which only a Process task (or another cron process) can do |
| `Replace` | stops the run of this process (a Process child is terminated, a task in the cron process is cancelled through its token) and runs; the stopped run's result is `replaced`. A run of another process is not stopped, and the trigger is skipped |
| `Queue` | is claimed, then waits for the lock (the task's `Timeout`, else `TaskTimeout`, else an hour) and runs; one trigger waits at a time |
 There is no fork mode; see [TCronModule](TCronModule.md#task-modes-the-run-lock-and-the-task-fiber-440).

`TCronModule::runTask()` runs the task in a fiber of its own as the task's user; see [TCronModule](TCronModule.md). The token, the scheduler, and the cached next trigger time are not serialized; default-valued properties, `Mode` `Serial` and `Overlap` `Skip` included, are excluded as well (`_getZappableSleepProps`).

## See Also

- [TCronModule](TCronModule.md)
- [TTimeScheduler](TTimeScheduler.md)
