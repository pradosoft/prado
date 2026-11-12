<?php

/**
 * TCronModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Exception;
use Prado\Caching\ICacheDependency;
use Prado\Caching\TChainedCacheDependency;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TNotSupportedException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TFiberContext;
use Prado\IO\Async\TFuture;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\Process\TProcess;
use Prado\Prado;
use Prado\Security\IUser;
use Prado\Security\IUserManager;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\Shell\TShellApplication;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Util\Clock\IClock;
use Prado\Util\Clock\TClockTrait;
use Prado\Util\Clock\TMonotonicClockAwareTrait;
use Prado\Util\Cron\Wake\ICronWakeTransport;
use Prado\Util\Cron\Wake\TCronWakeSocket;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\Log\TLogger;
use Prado\Util\Mutex\IMutex;
use Prado\Util\Mutex\TFileMutex;
use Prado\Util\Traits\TInitializedTrait;
use Prado\Xml\TXmlElement;
use Prado\Xml\TXmlDocument;

/**
 * TCronModule class.
 *
 * TCronModule runs time based services for the application. It runs a task at a
 * given time. A task is a task class, or a module Id followed by '->' followed by
 * a method with or without parameters.
 *
 * XML configuration style:
 * ```xml
 * <module id="cron" class="Prado\Util\Cron\TCronModule" DefaultUserName="admin" Daemon="true">
 *   <wake class="Prado\Util\Cron\Wake\TCronWakeFile" PollInterval="0.5" />
 *   <mutex class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
 *   <job Name="cronclean" Schedule="0 0 1 * * *" Task="Prado\Util\Cron\TDbCronCleanLogTask" UserName="cron" />
 *   <job Name="dbcacheclean" Schedule="* * * * *" Task="dbcache->flushCacheExpired(true)" />
 *   <job Schedule="0 * * * *" Task="mymoduleid->taskmethod" />
 * </module>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cron' => [
 *             'class' => 'Prado\Util\Cron\TCronModule',
 *             'properties' => [
 *                 'DefaultUserName' => 'admin',
 *                 'Daemon' => true,
 *             ],
 *             'wake' => ['class' => 'Prado\Util\Cron\Wake\TCronWakeFile', 'properties' => ['PollInterval' => 0.5]],
 *             'mutex' => ['class' => 'Prado\Util\Mutex\TDbMutex', 'properties' => ['ConnectionID' => 'db']],
 *             'jobs' => [
 *                 ['name' => 'cronclean', 'schedule' => '0 0 1 * * *', 'task' => 'Prado\Util\Cron\TDbCronCleanLogTask', 'username' => 'cron'],
 *                 ['name' => 'dbcacheclean', 'schedule' => '* * * * *', 'task' => 'dbcache->flushCacheExpired(true)'],
 *                 ['schedule' => '0 * * * *', 'task' => 'mymoduleid->taskmethod'],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 * XML `<job>` attribute names are case-insensitive. PHP job keys are lowercase:
 * `name`, `schedule`, `task`, and `username`.
 *
 * The `<wake>` element configures the {@see setWakeTransport WakeTransport} and the
 * `<mutex>` element the {@see setDaemonMutex DaemonMutex} in place, for a transport or
 * mutex only cron uses: the component of `class` takes the other attributes as properties
 * and is initialized with its element, without a module ID.  The PHP form is the `wake`
 * or `mutex` key with `class` and `properties`, or a class name alone.  A transport or
 * mutex shared with other modules is a module of its own named by the property.  Each
 * `<dependency>` element, or the `dependency` key with one element or a list, is a cache
 * dependency of the {@see setDaemonDependency DaemonDependency}; several are chained.
 *
 * The schedule is formatted like a linux crontab schedule expression.
 * {@see \Prado\Util\Cron\TTimeScheduler} parses the schedule and supports 8
 * different languages. Advanced options, like `@daily`, `@hourly`, and
 * `@1668165071`, are supported.
 *
 * This module is designed to be run as a system Crontab prado-cli every
 * minute.  The application then decides what tasks to execute, or not, and
 * when.
 *
 * The following is an example for your system cron tab to run the PRADO
 * application cron.
 * ```
 *		* * * * *  php /dir_to_/vendor/bin/prado-cli app /dir_to_app/ cron/run
 * ```
 *
 * Per-task user context requires an {@see \Prado\Security\IUserManager} module. The module
 * is taken from {@see setUserManager UserManager}, or auto-discovered from the application
 * when not set. Without an IUserManager, each task's {@see \Prado\Util\Cron\TCronTask::getUserName UserName}
 * is ignored and tasks run as the ambient application user.
 *
 * The default cron user is set with {@see setDefaultUserName DefaultUserName} and
 * defaults to the 'cron' user. The default user is used when no task specific user
 * is specified. The 'cron' user should exist in the TUserManager to run the task
 * as that user.
 *
 * {@see runTask()} runs each task in a fiber of its own.  The task's user is the
 * fiber's user in {@see \Prado\IO\Async\TFiberContext}, which
 * {@see \Prado\TApplication::getUser()} returns inside the task; the application
 * user is unchanged.  The task's {@see \Prado\Util\Cron\TCronTask::getMode() Mode}
 * ({@see \Prado\Util\Cron\TCronTaskMode}) is how {@see runTasks} runs it: Serial alone,
 * Parallel beside other Parallel tasks with at most {@see setConcurrency Concurrency}
 * (default 1) at once, or Process in a child `cron/exec` process the sweep does not
 * wait for.  Without the task's Overlap, a run holds a run lock ({@see getTaskLockName}) in
 * the {@see setDaemonMutex DaemonMutex}, and a trigger that comes while it is held is
 * skipped.  An exception from a task is logged ({@see logCronTaskError}) and the sweep
 * continues; each run's result and seconds are stored ({@see getTaskResults}).  A paused task
 * ({@see pauseTask}) and a task outside its time of day or declined by a behavior's `dyShouldRunTask`
 * skip their triggers; a failed run is tried again up to the task's Retries.  The module's
 * {@see setTimeZone TimeZone} is the zone of the tasks without their own.
 * {@see setTaskTimeout TaskTimeout} and a task's own {@see \Prado\Util\Cron\TCronTask::setTimeout Timeout}
 * bound a run with a {@see \Prado\IO\Async\TCancellationToken} the task observes.
 *
 * With {@see setDaemon Daemon}, the crontab's `cron/run` tick elects a daemon instead of sweeping:
 * the process acquires the {@see setDaemonMutex DaemonMutex} lease and, when another process holds
 * it, exits at once; otherwise it runs the due tasks, waits on its event loop for the next trigger,
 * a wake from the {@see setWakeTransport WakeTransport}, or {@see setDaemonIdle DaemonIdle}, and
 * repeats.  After {@see setDaemonLifetime DaemonLifetime} the daemon retires: it keeps serving until a
 * tick's process waits for the lease, then hands it over without a gap, so the new process picks up
 * deployed code.  A changed configuration file ({@see setDaemonWatchConfig DaemonWatchConfig}), a
 * changed {@see setDaemonDependency DaemonDependency}, or {@see retireDaemon} retires the daemon the
 * same way; SIGTERM, SIGINT, or {@see stopDaemon} end it
 * at once.  {@see onDaemonStart}, {@see onDaemonIdle}
 * (before each wait, with the wait a handler changes), and {@see onDaemonEnd} are raised with a
 * {@see \Prado\Util\Cron\TCronDaemonEventParameter}.  A web request wakes a waiting process,
 * per-minute or daemon, through {@see signalWake} when it schedules an earlier task.
 *
 * Each sweep raises {@see onSweepStart}, whose {@see \Prado\Util\Cron\TCronSweepEventParameter} lets a
 * handler change the tasks about to run, and {@see onSweepEnd} with their results.  Each run raises
 * {@see onTaskStart}, which a handler cancels, then {@see onTaskEnd} and on failure {@see onTaskFailure};
 * a trigger that does not run raises {@see onTaskSkipped}, all with a
 * {@see \Prado\Util\Cron\TCronTaskEventParameter}.  A task's ping URLs go through {@see onTaskPing},
 * which a module answers by delivering the ping itself, else through the {@see setHttpClient HttpClient}.
 * Every `on` event of the module is also broadcast as a global `fx` event (`fxCronSweepStart`,
 * `fxCronSweepEnd`, `fxCronDaemonStart`, `fxCronDaemonIdle`, `fxCronDaemonEnd`, `fxCronTaskStart`,
 * `fxCronTaskEnd`, `fxCronTaskFailure`, `fxCronTaskRetry`, `fxCronTaskOverdue`, `fxCronTaskSkipped`,
 * `fxCronTaskPing`), so a module declaring the method is called without configuration.  A component
 * raises `fxRetireCronDaemon` or `fxStopCronDaemon` to retire or stop the daemon from inside its process; {@see requestDaemonRetire} and
 * {@see requestDaemonStop} do the same from another process, as the `cron/retire` and `cron/stop`
 * shell commands do.
 *
 * With {@see setEnableStatus EnableStatus}, which the `cron/run` shell command turns on, a cron
 * process publishes its live status (role, daemon state, running tasks and children, recent runs)
 * to a file in {@see setStatusPath StatusPath}; {@see getStatuses} reads them for the `cron/top`
 * shell command.
 *
 * TCronModule is an {@see \Prado\Util\Clock\IClock} through {@see \Prado\Util\Clock\TClockTrait}:
 * scheduling reads "now" through {@see time()} and {@see microtime()}, so a subclass or test harness
 * overrides the clock to drive scheduling deterministically.
 *
 * When TPermissionsManager is a module in your app, there are four permissions
 * to control user access to its function:
 *  - TCronModule::PERM_CRON_SHELL 'cron_shell' enables the shell commands.
 *  - TCronModule::PERM_CRON_READ_TASKS 'cron_read_tasks' can read cron tasks and self owned tasks.
 *  - TCronModule::PERM_CRON_RUN_PENDING 'cron_run_pending' can run pending cron tasks.
 *  - TCronModule::PERM_CRON_RUN_TASK 'cron_run_task' enables cron tasks for the user.
 *
 * The default cron user (default: 'cron') should have these permissions to run tasks.
 *
 * The following gives user "cron" and all users with "Administrators" role the
 * permission to access cron shell and run cron tasks
 * ```xml
 *	 <role name="cron_shell" children="cron_read_tasks, cron_run_pending, cron_run_task" />
 *   <permissionrule name="cron_shell" action="allow" users="cron" />
 *   <permissionrule name="cron_shell" action="allow" roles="Administrators" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method void dyLogCron($numtasks)
 * @method void dyLogCronTask($task, $username)
 * @method void dyLogCronTaskEnd($task)
 * @method void dyLogCronTaskError($task, $exception)
 * @method void dyLogCronSleep($seconds)
 * @method void dyLogCronDaemonStart($lockName, $watching)
 * @method void dyLogCronDaemonEnd($numtasks)
 * @method bool dyShouldRunTask(bool $run, \Prado\Util\Cron\TCronTask $task)
 * @method void onDaemonStart(TCronDaemonEventParameter $param)
 * @method void onDaemonIdle(TCronDaemonEventParameter $param)
 * @method void onDaemonEnd(TCronDaemonEventParameter $param)
 * @method void onSweepStart(TCronSweepEventParameter $param)
 * @method void onSweepEnd(TCronSweepEventParameter $param)
 * @method bool dyRegisterShellAction($returnValue)
 * @method \Prado\Shell\TShellWriter getOutputWriter()
 * @see \Prado\Security\Permissions\TPermissionsBehavior::dyLogPermissionFailed
 * @see https://crontab.guru For more info on Crontab Schedule Expressions.
 */
class TCronModule extends \Prado\TModule implements IPermissions, IClock
{
	use TInitializedTrait;
	use TClockTrait;
	use TMonotonicClockAwareTrait;

	/** The default seconds a daemon runs before it exits for the next tick to elect a new one. */
	public const DEFAULT_DAEMON_LIFETIME = 3600;

	/** The default maximum seconds a daemon waits between sweeps. */
	public const DEFAULT_DAEMON_IDLE = 60;

	/** The least seconds a daemon waits between sweeps, so a task it cannot run does not spin it. */
	protected const DAEMON_MIN_WAIT = 0.05;

	/** The default seconds a tick's process waits for a retiring daemon to hand over the lease. */
	public const DEFAULT_DAEMON_HANDOFF_TIMEOUT = 10;

	/** The default seconds {@see requestDaemonRetire} and {@see requestDaemonStop} wait for the daemon's acknowledgment. */
	public const DEFAULT_DAEMON_REQUEST_TIMEOUT = 10;

	/** {@see getDaemonState}: a daemon holds the lease and serves. */
	public const DAEMON_STATE_RUNNING = 'running';

	/** {@see getDaemonState}: a daemon holds the lease and waits to hand it to the next tick. */
	public const DAEMON_STATE_RETIRING = 'retiring';

	/** A run's result: the task ran without an exception. */
	public const RESULT_OK = 'ok';

	/** A run's result: the task threw, or its child process failed. */
	public const RESULT_ERROR = 'error';

	/** A run's result: the task's user lacks {@see PERM_CRON_RUN_TASK}. */
	public const RESULT_DENIED = 'denied';

	/** A run's result: the task's child process outlived its timeout and was terminated. */
	public const RESULT_TIMEOUT = 'timeout';

	/** A trigger's result: the run would start later than the task's StartingDeadline and was skipped. */
	public const RESULT_MISSED = 'missed';

	/** A run's result: a trigger of the task with Overlap Replace stopped the run. */
	public const RESULT_REPLACED = 'replaced';

	/** The seconds a Replace trigger waits for the stopped run to release the run lock. */
	public const REPLACE_TIMEOUT = 10;

	/** The seconds a Queue trigger waits for the running run, without a task Timeout or TaskTimeout. */
	public const DEFAULT_QUEUE_TIMEOUT = 3600;

	/** The `cron/exec` exit code when the task threw. */
	public const EXIT_ERROR = 1;

	/** The `cron/exec` exit code when the task is not found. */
	public const EXIT_NO_TASK = 2;

	/** The `cron/exec` exit code when the task's user lacks {@see PERM_CRON_RUN_TASK}. */
	public const EXIT_DENIED = 3;

	/** The seconds a Process task's child may run past the task's timeout before it is terminated. */
	public const PROCESS_TIMEOUT_GRACE = 5;

	/** The microseconds between the exit polls of a Process task's child. */
	protected const PROCESS_POLL_INTERVAL = 50000;

	/** The directory of the status files in the runtime path. */
	public const STATUS_DIRECTORY = 'cronstatus';

	/** The number of finished runs a status file lists. */
	public const STATUS_RECENT = 20;

	/** The seconds past its expected update that the status of a process on another host is stale. */
	public const STATUS_STALE = 120;

	/** A status role: the process is the daemon. */
	public const STATUS_ROLE_DAEMON = 'daemon';

	/** A status role: the process sweeps the pending tasks once. */
	public const STATUS_ROLE_SWEEP = 'sweep';

	/** A status role: the process runs one task on request (`cron/now`). */
	public const STATUS_ROLE_MANUAL = 'manual';

	/** The file of the paused task names of TCronModule in the runtime path, followed by the module ID. */
	public const PAUSED_FILE_PREFIX = 'cronpaused-';

	/** The daemon state of a status whose daemon released the lease and waits for its Process tasks. */
	public const STATUS_DAEMON_ENDING = 'ending';

	/** The behavior name for the Shell Log behavior */
	public const SHELL_LOG_BEHAVIOR = 'shellLog';

	public const TASK_KEY = 'task';

	public const SCHEDULE_KEY = 'schedule';

	public const NAME_KEY = 'name';

	public const USERNAME_KEY = 'username';

	/** The configuration element and PHP key of the {@see setWakeTransport WakeTransport} */
	public const WAKE_KEY = 'wake';

	/** The configuration element and PHP key of the {@see setDaemonMutex DaemonMutex} */
	public const MUTEX_KEY = 'mutex';

	/** The configuration element and PHP key of the {@see setDaemonDependency DaemonDependency} */
	public const DEPENDENCY_KEY = 'dependency';

	/** Key to global state of the last time SystemCron was run */
	public const LAST_CRON_TIME = 'prado:cron:lastcron';

	/** Key to global state of each task, lastExecTime and ProcessCount */
	public const TASKS_INFO = 'prado:cron:tasksinfo';

	/** The name of the cron user */
	public const DEFAULT_CRON_USER = 'cron';

	/** The separator for TCronMethodTask */
	public const METHOD_SEPARATOR = '->';

	/** The permission for the cron shell */
	public const PERM_CRON_SHELL = 'cron_shell';

	/** The permission for reading cron tasks */
	public const PERM_CRON_READ_TASKS = 'cron_read_tasks';

	/** The permission for running pending cron tasks */
	public const PERM_CRON_RUN_PENDING = 'cron_run_pending';

	/** The permission for running individual cron tasks */
	public const PERM_CRON_RUN_TASK = 'cron_run_task';

	/** @var IUserManager user manager instance */
	private $_userManager;

	/** @var TCronTaskInfo[] The info for tasks in the system. */
	private $_tasksInfo;

	/** @var bool whether or not the $_tasks is properties or TCronTask  */
	private $_tasksInstanced = false;

	/** @var array[]|TCronTask[] the tasks */
	private $_tasks = [];

	/** @var ?string The user Id of the tasks without users; null for {@see DEFAULT_CRON_USER} */
	private ?string $_defaultUserName = null;

	/** @var bool enable cron on requests, default false */
	private $_enableRequestCron = false;

	/** @var numeric probability that a request cron will trigger [0.0 to 100.0], default 1.0 (for 1%) */
	private $_requestCronProbability = 1.0;

	/** @var ?bool is the app running in cron shell mode or not, or null for auto-detect */
	private $_inCronShell;

	/** @var string the cli class to instance for CLI command line actions; this changes for TDbCronManager */
	protected $_shellClass = \Prado\Util\Cron\TShellCronAction::class;

	/** @var array|TXmlDocument any additional tasks to install from properties */
	private $_additionalCronTasks = [];

	/** @var int the number of tasks that run at once, default 1 */
	private int $_concurrency = 1;

	/** @var ?float the seconds a task run may take unless the task has its own Timeout, default null for no deadline */
	private ?float $_taskTimeout = null;

	/** @var null|ICronWakeTransport|string the wake transport, or its module ID, or null for the default */
	private $_wakeTransport;

	/** @var bool whether `cron/run` elects a daemon instead of sweeping once, default false */
	private bool $_daemon = false;

	/** @var ?float the seconds a daemon runs before it exits; 0 for no limit; null for {@see DEFAULT_DAEMON_LIFETIME} */
	private ?float $_daemonLifetime = null;

	/** @var ?float the maximum seconds a daemon waits between sweeps; null for {@see DEFAULT_DAEMON_IDLE} */
	private ?float $_daemonIdle = null;

	/** @var null|IMutex|string the mutex of the daemon lease, or its module ID, or null for a file mutex */
	private $_daemonMutex;

	/** @var bool whether the running daemon stops after the current sweep */
	private bool $_daemonStop = false;

	/** @var bool whether a wake arrived since the daemon's last sweep */
	private bool $_daemonWake = false;

	/** @var ?\Closure resumes the daemon's current wait, when it is waiting */
	private ?\Closure $_daemonResume = null;

	/** @var ?string why the running daemon is ending, a TCronDaemonEventParameter::REASON_* constant */
	private ?string $_daemonReason = null;

	/** @var ?float the seconds a tick's process waits for a retiring daemon's lease; null for {@see DEFAULT_DAEMON_HANDOFF_TIMEOUT} */
	private ?float $_daemonHandoffTimeout = null;

	/** @var bool whether the running daemon's lifetime passed and it holds the retiring lock */
	private bool $_daemonRetiring = false;

	/** @var bool whether {@see retireDaemon} asked the running daemon to retire */
	private bool $_daemonRetireRequested = false;

	/** @var bool whether the daemon retires when a configuration file changes, default true */
	private bool $_daemonWatchConfig = true;

	/** @var ?string[] the configuration files the daemon watches, or null for the application configuration file */
	private ?array $_daemonConfigFiles = null;

	/** @var ?string the configuration fingerprint the running daemon started with */
	private ?string $_daemonFingerprint = null;

	/** @var ?ICacheDependency the dependency whose change retires the daemon */
	private ?ICacheDependency $_daemonDependency = null;

	/** @var TFuture[] the runs of Process tasks whose child processes may still run */
	private array $_processRuns = [];

	/** @var ?bool whether this process publishes its live status; null until set, which `cron/run` turns on */
	private ?bool $_enableStatus = null;

	/** @var ?string the directory of the status files, or null for {@see STATUS_DIRECTORY} in the runtime path */
	private ?string $_statusPath = null;

	/** @var ?array the live status of this process while it is published */
	private ?array $_status = null;

	/** @var int the last run ID of the status */
	private int $_statusRunId = 0;

	/** @var ?THttpClient the client that sends the task pings */
	private ?THttpClient $_httpClient = null;

	/** @var ?string the time zone of the tasks without their own, or null for the application time zone */
	private ?string $_timeZone = null;

	/** @var array<string, array{token: ?TCancellationToken, process: ?TProcess, replaced: bool}> the runs holding their run lock in this process, by task name */
	private array $_activeRuns = [];

	/** @var array<string, true> the tasks with a Queue trigger waiting in this process, by name */
	private array $_queuedRuns = [];

	/**
	 * Initializes the module.  Read the configuration, installs Shell Actions,
	 * and checks if Request-cron should be activated.  This lazy loads the
	 * cron job tasks to minimize computing when cron isn't used.  The properties left unset take
	 * the class constants through `static::`, so a subclass overrides a default by redefining
	 * {@see DEFAULT_CRON_USER}, {@see DEFAULT_DAEMON_LIFETIME}, or {@see DEFAULT_DAEMON_IDLE}.
	 * @param null|array|\Prado\Xml\TXmlElement $config
	 * @throws TConfigurationException when the user manage doesn't exist or is invalid
	 */
	public function init($config)
	{
		$app = $this->getApplication();
		$this->_defaultUserName ??= static::DEFAULT_CRON_USER;
		$this->_daemonLifetime ??= (float) static::DEFAULT_DAEMON_LIFETIME;
		$this->_daemonIdle ??= (float) static::DEFAULT_DAEMON_IDLE;
		$this->_daemonHandoffTimeout ??= (float) static::DEFAULT_DAEMON_HANDOFF_TIMEOUT;

		//Get the IUserManager module from the string
		if (is_string($this->_userManager)) {
			if (($users = $app->getModule($this->_userManager)) === null) {
				throw new TConfigurationException('cron_usermanager_nonexistent', $this->_userManager);
			}
			if (!($users instanceof IUserManager)) {
				throw new TConfigurationException('cron_usermanager_invalid', $this->_userManager);
			}
			$this->_userManager = $users;
		}

		// Otherwise manually look for the IUserManager
		if ($this->_userManager === null) {
			$users = $app->getModulesByType(\Prado\Security\IUserManager::class);
			foreach ($users as $id => $module) {
				$userManager = $app->getModule($id);
				if ($userManager instanceof IUserManager) {
					$this->_userManager = $userManager;
					break;
				}
			}
		}
		$this->_tasksInstanced = false;

		//read the wake transport and daemon mutex elements
		$this->readComponentConfiguration($config);

		//read config tasks and schedule
		$this->readConfiguration($config);

		//Read additional Config from Property
		$this->readConfiguration($this->_additionalCronTasks);
		$app->attachEventHandler('onAuthenticationComplete', [$this, 'registerShellAction']);

		if ($app instanceof \Prado\Shell\TShellApplication) {
			$app->registerOption('cron', [$this, 'setInCronShell'], 'If run in crontab, set this flag.  It limits tasks to the current minute, default auto-detect');
			$app->registerOptionAlias('c', 'cron');
		}

		if ($this->php_sapi_name() !== 'cli' && $this->getEnableRequestCron()) {
			if (100.0 * ((float) ($this->mt_rand()) / (float) (\mt_getrandmax())) <= $this->getRequestCronProbability()) {
				$app->attachEventHandler('OnEndRequest', [$this, 'processPendingTasks'], 20);
			}
		}
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * This is the IPermissions implementation to put a permission on
	 * dyRegisterShellAction, dyGetTasks, dyGetUserTasks, dyGetPendingTasks, and dyRunTask.
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager)
	{
		$userIsOwnerAllowedRule = Prado::createComponent(TUserOwnerRule::class);
		return [
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_SHELL, 'Activates cron shell commands.', 'dyRegisterShellAction'),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_READ_TASKS, 'Cron read tasks.', ['dyGetTasks', 'dyGetUserTasks'], $userIsOwnerAllowedRule),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_RUN_PENDING, 'Process/get pending cron tasks.', 'dyGetPendingTasks'),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_RUN_TASK, 'Run individual cron tasks.', 'dyRunTask'),
		];
	}

	/**
	 * This reads the configuration and stores the specified tasks, for lazy loading, until needed.
	 * Job property keys are case-insensitive and stored in lowercase.
	 * @param array|\Prado\Xml\TXmlElement $config the settings for cron
	 * @throws TConfigurationException when a PHP configuration is not an array or two jobs have the same name.
	 */
	protected function readConfiguration($config)
	{
		$isXml = false;
		if (!$config) {
			return;
		}
		if ($config instanceof \Prado\Xml\TXmlElement) {
			$isXml = true;
			$config = $config->getElementsByTagName('job');
		} elseif (is_array($config)) {
			unset($config[self::WAKE_KEY], $config[self::MUTEX_KEY], $config[self::DEPENDENCY_KEY]);
			if (isset($config['jobs'])) {
				$config = $config['jobs'];
			}
		}
		foreach ($config as $properties) {
			if ($isXml) {
				$properties = array_change_key_case($properties->getAttributes()->toArray());
			} else {
				if (!is_array($properties)) {
					throw new TConfigurationException('cron_task_as_array_required');
				}
				$properties = array_change_key_case($properties);
			}
			if (!($properties[self::NAME_KEY] ?? null)) {
				$class = $properties[self::TASK_KEY] ?? '';
				$schedule = $properties[self::SCHEDULE_KEY] ?? '';
				$name = $properties[self::NAME_KEY] = substr(sha1($schedule . $class), 0, 7);
			} else {
				$name = $properties[self::NAME_KEY];
			}
			if (isset($this->_tasks[$name])) {
				throw new TConfigurationException('cron_duplicate_task_name', $name);
			}
			$this->validateTask($properties);
			$this->_tasks[$name] = $properties;
		}
	}

	/**
	 * Creates the {@see setWakeTransport WakeTransport} of the `<wake>` element, the
	 * {@see setDaemonMutex DaemonMutex} of the `<mutex>` element, and the
	 * {@see setDaemonDependency DaemonDependency} of the `<dependency>` elements, or of the `wake`,
	 * `mutex`, and `dependency` keys of a PHP configuration.  Several dependencies are chained in a
	 * {@see \Prado\Caching\TChainedCacheDependency}.  An absent element leaves the property as set.
	 * @param null|array|\Prado\Xml\TXmlElement $config the settings for cron
	 * @throws TConfigurationException when an element has no class, or its class is not a wake transport, mutex, or cache dependency
	 * @since 4.4.0
	 */
	protected function readComponentConfiguration($config)
	{
		if (($wake = $this->createConfigComponent($config, self::WAKE_KEY, ICronWakeTransport::class, 'cron_waketransport_invalid')) !== null) {
			$this->setWakeTransport($wake);
		}
		if (($mutex = $this->createConfigComponent($config, self::MUTEX_KEY, IMutex::class, 'cron_daemonmutex_invalid')) !== null) {
			$this->setDaemonMutex($mutex);
		}
		$dependencies = [];
		foreach ($this->getConfigElements($config, self::DEPENDENCY_KEY, true) as $element) {
			$dependencies[] = $this->createElementComponent($element, self::DEPENDENCY_KEY, ICacheDependency::class, 'cron_daemondependency_invalid');
		}
		if (count($dependencies) === 1) {
			$this->setDaemonDependency($dependencies[0]);
		} elseif ($dependencies) {
			$chain = Prado::createComponent(TChainedCacheDependency::class);
			foreach ($dependencies as $dependency) {
				$chain->getDependencies()->add($dependency);
			}
			$this->setDaemonDependency($chain);
		}
	}

	/**
	 * Validates that schedule and task are present.
	 * Subclasses overload this method to add their own validation.
	 * @param array $properties the task as an array of properties
	 * @throws TConfigurationException when the schedule or task doesn't exist
	 */
	public function validateTask($properties)
	{
		$schedule = $properties[self::SCHEDULE_KEY] ?? null;
		$task = $properties[self::TASK_KEY] ?? null;
		if (!$schedule) {
			throw new TConfigurationException('cron_schedule_required');
		}
		if (!$task) {
			throw new TConfigurationException('cron_task_required');
		}
	}

	/**
	 * @param object $sender sender of this event handler
	 * @param mixed $param parameter for the event
	 */
	public function registerShellAction($sender, $param)
	{
		if ($this->dyRegisterShellAction(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => $this->_shellClass, 'CronModule' => $this]);
		}
	}

	/**
	 * makes the tasks from the configuration array into task objects.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return \Prado\Util\Cron\TCronTask[]
	 */
	protected function ensureTasks()
	{
		if (!$this->_tasksInstanced) {
			foreach ($this->_tasks as $name => $properties) {
				if (is_array($properties)) {
					$this->_tasks[$name] = $this->constructTask($properties);
				}
			}
			$this->_tasksInstanced = true;
		}
		return $this->_tasks;
	}

	/**
	 * This lazy loads the tasks from configuration array to instance.
	 * This calls {@see ensureTasks} to get the tasks and their persistent data.
	 * @param ?string $name name of tasks, _ is wild card and % is wild string, default null.
	 * @param int $pageSize max number of returning tasks, default 0 for unlimited
	 * @param int $offset offset in the tasks to return, default 0 for all.
	 * @param bool $sortingAsc sort by ascending name, default true
	 * @return ?\Prado\Util\Cron\TCronTask[] currently active cron tasks, null when access is denied
	 */
	public function getTasks($name = null, $pageSize = 0, $offset = 0, $sortingAsc = true)
	{
		if ($this->dyGetTasks(false, $name) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron tasks \'' . $name . '\'');
			return null;
		}
		$this->ensureTasks();
		$result = [];
		if ($name) {
			$name = '/^' . str_replace(['_', '%'], ['.', '.*'], $name) . '$/';
		}
		foreach ($this->_tasks as $tname => $task) {
			if (!$name || preg_match($name, $tname)) {
				$result[$tname] = $task;
			}
		}
		if ($sortingAsc) {
			ksort($result);
		} else {
			krsort($result);
		}
		$result = array_slice($result, $offset, $pageSize ? $pageSize : null);
		return $result;
	}

	/**
	 * Gets the number of tasks filtered by $name
	 * @param ?string $name task names where _ is wild card and % is wild string, default null.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?int the number of tasks, null when access is denied
	 * @since 4.4.0
	 */
	public function getTasksCount($name = null)
	{
		$tasks = $this->getTasks($name);
		return ($tasks === null) ? null : count($tasks);
	}

	/**
	 * Gets the cron tasks for a specific username.
	 * @param string $username name of the user.
	 * @param ?string $name task name where _ is wild card and % is wild string, default null.
	 * @param int $pageSize size of each page of data, default 0
	 * @param int $offset offset for the page of data, default 0
	 * @param ?bool $sortingAsc sort by descending name, default true.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?TCronTask[] cron tasks for a specific user, null when access is denied
	 * @since 4.4.0
	 */
	public function getUserTasks($username, $name = null, $pageSize = 0, $offset = 0, $sortingAsc = true)
	{
		if ($this->dyGetUserTasks(false, $username, $name, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron tasks \'' . $name . '\'@' . $username);
			return null;
		}
		$this->ensureTasks();
		$result = [];
		$usernameRegex = '/^' . str_replace(['_', '%'], ['.', '.*'], preg_quote((string) $username, '/')) . '$/';
		$nameRegex = null;
		if ($name) {
			$nameRegex = '/^' . str_replace(['_', '%'], ['.', '.*'], $name) . '$/';
		}
		$checkNull = preg_match($usernameRegex, $this->getDefaultUserName());
		foreach ($this->_tasks as $tname => $task) {
			$uname = $task->getUserName();

			if ((($checkNull && $uname === null) || preg_match($usernameRegex, (string) $uname)) && (!$nameRegex || preg_match($nameRegex, $tname))) {
				$result[$tname] = $task;
			}
		}
		if ($sortingAsc) {
			ksort($result);
		} else {
			krsort($result);
		}
		$result = array_slice($result, $offset, $pageSize ? $pageSize : null);
		return $result;
	}

	/**
	 * Gets the number of tasks for a specific user.
	 * @param string $username
	 * @param ?string $name task name filter with _ as a wild and and % as a wild string, default null.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?int number of tasks, null when access is denied
	 * @since 4.4.0
	 */
	public function getUserTasksCount($username, $name = null)
	{
		$tasks = $this->getUserTasks($username, $name);
		return ($tasks === null) ? null : count($tasks);
	}

	/**
	 * These are the tasks specified in the configuration and getAdditionalCronTasks
	 * until {@see ensureTasks} is called.
	 * @return array[]|TCronTask[] currently active cron tasks
	 */
	public function getRawTasks()
	{
		return $this->_tasks;
	}

	/**
	 * This lazy loads the tasks.
	 * @param string $name name of the task to retrieve.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?TCronTask cron task with a specific name, null when absent or access is denied
	 */
	public function getTask($name)
	{
		if (isset($this->_tasks[$name])) {
			if (is_array($this->_tasks[$name])) {
				$this->_tasks[$name] = $this->constructTask($this->_tasks[$name]);
			}
			if ($this->dyGetUserTasks(false, $username = $this->_tasks[$name]->getUserName(), $name, ['extra' => ['username' => $username]]) === true) {
				$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron task \'' . $name . '\'');
				return null;
			}
			return $this->_tasks[$name];
		}
		return null;
	}

	/**
	 * Instances a TCronTask subclass or TCronMethodTask based upon the task
	 * being executed.
	 * @param string $taskExec the class name or "module->method('param1')" to place
	 * into a {@see TCronMethodTask}.
	 * @return TCronTask the instance of $taskExec
	 */
	public function instanceTask($taskExec)
	{
		if (($pos = strpos($taskExec, self::METHOD_SEPARATOR)) !== false) {
			$module = substr($taskExec, 0, $pos);
			$method = substr($taskExec, $pos + 2); //String Length of self::METHOD_SEPARATOR
			$task = Prado::createComponent(TCronMethodTask::class, $module, $method);
		} else {
			$task = Prado::createComponent($taskExec);
			if (!$task instanceof \Prado\Util\Cron\TCronTask) {
				throw new TInvalidDataTypeException("cron_not_a_crontask", $taskExec);
			}
		}
		return $task;
	}

	/**
	 * Inputs an array of task properties and creates a TCronTask.
	 * @param array $properties the properties to construct the task with.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return TCronTask
	 * @since 4.4.0
	 */
	protected function constructTask($properties)
	{
		$name = $properties[self::NAME_KEY];
		$task = $properties[self::TASK_KEY];
		unset($properties[self::NAME_KEY]);
		unset($properties[self::TASK_KEY]);

		$task = $this->instanceTask($task);

		$task->setName($name);
		foreach ($properties as $key => $value) {
			$task->$key = $value;
		}
		$this->prepareTask($task);
		$this->setPersistentData($name, $task);
		return $task;
	}

	/**
	 * Prepares a task this module runs: gives it the module's {@see getTimeZone TimeZone} as its
	 * {@see \Prado\Util\Cron\TCronTask::setDefaultTimeZone DefaultTimeZone}.
	 * @param TCronTask $task the task
	 * @since 4.4.0
	 */
	protected function prepareTask($task): void
	{
		$task->setDefaultTimeZone($this->_timeZone);
	}

	/**
	 * when instancing and then loading the tasks, this sets the persisting data of
	 * the task from the global state.  When there is no instance in the global state,
	 * the lastExecTime is initialized.
	 * @param string $name name of the task.
	 * @param TCronTask $task the task object.
	 * @return bool updated the taskInfo with persistent data.
	 */
	protected function setPersistentData($name, $task)
	{
		$tasksInfo = $this->getApplication()->getGlobalState(self::TASKS_INFO, []);
		if (isset($tasksInfo[$name])) {
			$task->setLastExecTime($tasksInfo[$name]['lastExecTime']);
			$task->setProcessCount($tasksInfo[$name]['processCount']);
			return true;
		} else {
			$task->resetTaskLastExecTime();
			$task->setProcessCount(0);
			$tasksInfo[$name]['lastExecTime'] = $task->getLastExecTime();
			$tasksInfo[$name]['processCount'] = 0;
			$this->getApplication()->setGlobalState(self::TASKS_INFO, $tasksInfo, []);
		}
		return false;
	}

	/**
	 * taskExists checks for a task name.
	 * @param string $name task to check
	 * @return bool whether the task name exists
	 * @since 4.4.0
	 */
	public function taskExists($name)
	{
		return !empty($this->getTask($name));
	}

	/**
	 * taskExistsInternal checks for a task name without permissions.
	 * @param string $name task to check
	 * @return bool whether the task name exists
	 * @since 4.4.0
	 */
	protected function taskExistsInternal($name)
	{
		return isset($this->_tasks[$name]);
	}

	/**
	 * @param bool $futureSeconds false gets only pending tasks , true gets tasks to the 59th second of
	 * the current minute too. default false.
	 * @return ?TCronTask[] the tasks that are pending, null when access is denied
	 */
	public function getPendingTasks($futureSeconds = false)
	{
		if ($this->dyGetPendingTasks(false) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_RUN_PENDING, 'getting pending cron tasks');
			return null;
		}
		$this->ensureTasks();

		$time = $this->time();
		if ($futureSeconds) {
			$time = floor($time / 60) * 60 + 59;
		}
		$pendingtasks = [];

		if ($futureSeconds) {
			foreach ($this->_tasks as $name => $task) {
				if ($task->getIsPending($time)) {
					$pendingtasks[$task->getNextTriggerTime()][$name] = $task;
				}
			}
			if (count($pendingtasks)) {
				ksort($pendingtasks);
				$t = [];
				foreach ($pendingtasks as $time => $tasks) {
					$t = array_merge($t, $tasks);
				}
				$pendingtasks = $t;
			}
		} else {
			foreach ($this->_tasks as $name => $task) {
				if ($task->getIsPending($time)) {
					$pendingtasks[$name] = $task;
				}
			}
		}
		return $pendingtasks;
	}

	/**
	 * Filters the Tasks for a specific class.
	 * @template T of TCronTask
	 * @param class-string<T> $type
	 * @param ?string $name task names to search, default null for all
	 * _ is wild card and % is wild string.
	 * @param int $pageSize the number of tasks to return
	 * @param int $offset the offset of tasks
	 * @param bool $sortingAsc sort by ascending name, default true
	 * @return array<string, T> the tasks of $type, keyed by task name
	 */
	public function getTasksByType($type, $name = null, $pageSize = 0, $offset = 0, $sortingAsc = true)
	{
		$result = [];
		foreach ($this->getTasks($name) ?? [] as $tname => $task) {
			if ($task->isa($type)) {
				$result[$tname] = $task;
			}
		}
		if ($sortingAsc) {
			ksort($result);
		} else {
			krsort($result);
		}
		$result = array_slice($result, $offset, $pageSize ? $pageSize : null);
		return $result;
	}

	/**
	 * processPendingTasks executes pending tasks through {@see runTasks}.  In a crontab shell
	 * no task starts after the minute the sweep began.  With {@see getSecondResolution} in a
	 * crontab shell, the tasks due through the end of the minute run at their trigger second
	 * through {@see runPendingTasksWithWake}.  The call returns after the Process tasks it started
	 * finish.  With {@see getDaemon Daemon} in the CLI the call is {@see runDaemon}.
	 * @return int number of tasks that were pending, or that the daemon ran
	 */
	public function processPendingTasks()
	{
		if ($this->getDaemon() && $this->php_sapi_name() === 'cli') {
			return $this->runDaemon();
		}
		$status = $this->beginStatus(static::STATUS_ROLE_SWEEP);
		try {
			return $this->sweepPendingTasks();
		} finally {
			$this->awaitProcessRuns();
			if ($status) {
				$this->endStatus();
			}
		}
	}

	/**
	 * Sweeps the pending tasks once, as {@see processPendingTasks} describes without the daemon.
	 * @return int the number of tasks that were pending
	 * @since 4.4.0
	 */
	protected function sweepPendingTasks(): int
	{
		$inCronTab = (($inCron = $this->getInCronShell()) !== null) ? $inCron : TShellApplication::detectCronTabShell();
		$sleep = $inCronTab && $this->getSecondResolution();

		$this->filterStaleTasks();
		$pendingTasks = $this->getPendingTasks($sleep);
		if ($pendingTasks === null) {
			return 0;
		}
		$sweep = new TCronSweepEventParameter($pendingTasks, false);
		$this->onSweepStart($sweep);
		$pendingTasks = $sweep->getTasks();
		$numtasks = count($pendingTasks);
		$startMinute = (int) floor($this->time() / 60);

		$this->logCron($numtasks);
		$results = [];
		if ($numtasks) {
			if ($sleep) {
				$results = $this->runPendingTasksWithWake($startMinute, $pendingTasks);
			} else {
				$results = $this->runTasks($pendingTasks, $inCronTab ? $startMinute : null);
			}
		}
		$this->onSweepEnd($sweep->setResults($results));

		return $numtasks;
	}

	/**
	 * Raises the `OnSweepStart` event, and `fxCronSweepStart` globally, once the pending tasks of a sweep
	 * are collected and before any runs.  A handler changes the tasks about to run through
	 * {@see TCronSweepEventParameter::setTasks}.
	 * @param TCronSweepEventParameter $param the sweep parameter
	 * @since 4.4.0
	 */
	public function onSweepStart($param)
	{
		$this->raiseEvent('OnSweepStart', $this, $param);
		$this->raiseEvent('fxCronSweepStart', $this, $param);
	}

	/**
	 * Raises the `OnSweepEnd` event, and `fxCronSweepEnd` globally, after the last task of a sweep
	 * finished, with each task's result in {@see TCronSweepEventParameter::getResults}.
	 * @param TCronSweepEventParameter $param the sweep parameter
	 * @since 4.4.0
	 */
	public function onSweepEnd($param)
	{
		$this->raiseEvent('OnSweepEnd', $this, $param);
		$this->raiseEvent('fxCronSweepEnd', $this, $param);
	}

	/**
	 * Second-resolution scheduling loop. Repeatedly polls for the earliest pending task and waits until
	 * its trigger time through {@see waitForWake}, which returns early when a wake kick arrives. After
	 * each wait it re-polls, so a task added or rescheduled mid-minute (a kick that makes a new task the
	 * earliest, or a natural wake that reveals a later same-minute task) is honored. The loop ends when
	 * the serviced minute rolls over or nothing remains pending. {@see runTask} advances each task's next
	 * execution time before running it, so an overlapping next-minute cron process never re-runs a task.
	 * @param int $startMinute the minute (floor(timestamp / 60)) this invocation services
	 * @param array<string, TCronTask> $tasks the tasks the sweep may run, by name; a task removed by an
	 * `onSweepStart` handler is left pending for the next sweep
	 * @return array<string, bool> whether each task that ran did so without an exception, by name
	 * @since 4.4.0
	 */
	protected function runPendingTasksWithWake(int $startMinute, array $tasks): array
	{
		$results = [];
		try {
			while ($startMinute == floor($this->time() / 60)) {
				$pending = array_intersect_key($this->getPendingTasks(true) ?? [], $tasks);
				if (empty($pending)) {
					break;
				}
				$pending = $this->sortTasks($pending, true);
				$task = reset($pending);
				$trigger = $task->getNextTriggerTime();
				if (($now = $this->microtime()) < $trigger) {
					$this->dyLogCronSleep($trigger - $now);
					$this->waitForWake($trigger);
					continue;
				}
				$results += $this->runTasks([$task]);
			}
		} finally {
			// Release the wake transport held for this minute so nothing is bound into the next minute.
			if ($this->_wakeTransport instanceof ICronWakeTransport) {
				$this->_wakeTransport->close();
			}
		}
		return $results;
	}

	/**
	 * Runs tasks, each in a fiber of its own through {@see runTask}, by the task's
	 * {@see \Prado\Util\Cron\TCronTask::getMode() Mode}:
	 *  - Serial: starts after the running tasks finish and runs alone.
	 *  - Parallel: runs beside the other Parallel tasks, at most {@see getConcurrency} at once.
	 *  - Process: starts a child process and is not waited for; {@see awaitProcessRuns} waits for it.
	 * The tasks start in the order of their {@see \Prado\Util\Cron\TCronTask::getPriority() Priority},
	 * lower first; equal priorities keep their order ({@see sortTasks}).  A task removed since it was
	 * collected ({@see taskExistsInternal}) is skipped.  Returns once every Serial and Parallel task has finished.
	 * @param TCronTask[] $tasks the tasks to run, in order
	 * @param ?int $startMinute the minute (`time() / 60`) after which no further task starts, or null for no limit
	 * @return array<string, bool> whether each Serial and Parallel task that ran did so without an exception, by task name
	 * @since 4.4.0
	 */
	protected function runTasks(array $tasks, ?int $startMinute = null): array
	{
		$running = [];
		$futures = [];
		$this->awaitTasks($this->_processRuns, PHP_INT_MAX);
		foreach ($this->sortTasks($tasks) as $task) {
			if ($startMinute !== null && $startMinute != floor($this->time() / 60)) {
				break;
			}
			if (!$this->taskExistsInternal($task->getName())) {
				continue;
			}
			$mode = $task->getMode();
			if ($mode === TCronTaskMode::Process) {
				$this->_processRuns[] = TAsync::run([$this, 'runTask'], $task);
				continue;
			}
			$this->awaitTasks($running, ($mode === TCronTaskMode::Parallel) ? $this->getConcurrency() - 1 : 0);
			$running[] = $futures[$task->getName()] = TAsync::run([$this, 'runTask'], $task);
			if ($mode !== TCronTaskMode::Parallel) {
				$this->awaitTasks($running, 0);
			}
		}
		$this->awaitTasks($running, 0);
		$results = [];
		foreach ($futures as $name => $future) {
			$results[$name] = (bool) $future->await();
		}
		return $results;
	}

	/**
	 * Orders tasks by their {@see \Prado\Util\Cron\TCronTask::getPriority() Priority}, lower first, keeping
	 * the order of equal priorities.
	 * @param TCronTask[] $tasks the tasks
	 * @param bool $byTrigger whether the next trigger time orders them first, then the priority
	 * @return TCronTask[] the ordered tasks, with their keys
	 * @since 4.4.0
	 */
	protected function sortTasks(array $tasks, bool $byTrigger = false): array
	{
		uasort($tasks, static fn ($a, $b): int => ($byTrigger ? [(int) $a->getNextTriggerTime(), $a->getPriority()] : [$a->getPriority()])
			<=> ($byTrigger ? [(int) $b->getNextTriggerTime(), $b->getPriority()] : [$b->getPriority()]));
		return $tasks;
	}

	/**
	 * Waits until the Process tasks this process started have finished: their child processes
	 * exited and their results are recorded.
	 * @since 4.4.0
	 */
	public function awaitProcessRuns(): void
	{
		$this->awaitTasks($this->_processRuns, 0);
	}

	/**
	 * @return int the number of Process tasks of this process whose child processes still run
	 * @since 4.4.0
	 */
	public function getProcessRunCount(): int
	{
		$this->awaitTasks($this->_processRuns, PHP_INT_MAX);
		return count($this->_processRuns);
	}

	/**
	 * Waits on the event loop until at most a number of task futures are still running, and
	 * drops the finished ones from the list.
	 * @param TFuture[] $futures the futures of running tasks, by reference
	 * @param int $limit the number of futures that may remain pending
	 * @since 4.4.0
	 */
	protected function awaitTasks(array &$futures, int $limit)
	{
		$pending = static fn (TFuture $future): bool => !$future->isComplete();
		$futures = array_filter($futures, $pending);
		while (count($futures) > $limit) {
			TAsync::any($futures)->await();
			$futures = array_filter($futures, $pending);
		}
	}

	/**
	 * @param int $numtasks number of tasks being processed
	 */
	protected function logCron($numtasks)
	{
		Prado::log("Processing {$numtasks} Cron Tasks", TLogger::INFO, TCronModule::class);
		$this->dyLogCron($numtasks);
		$this->setLastCronTime($this->microtime());
	}

	/**
	 * This removes any stale tasks in the global state.
	 */
	protected function filterStaleTasks()
	{
		// Filter out any stale tasks in the global state that aren't in config
		$app = $this->getApplication();
		$tasksInfo = $app->getGlobalState(self::TASKS_INFO, []);
		$count = count($tasksInfo);
		$tasksInfo = array_intersect_key($tasksInfo, $this->_tasks);
		if ($count != count($tasksInfo)) {
			$app->setGlobalState(self::TASKS_INFO, $tasksInfo, []);
		}
	}

	/**
	 * Runs a task in the current fiber as its user ({@see resolveTaskUser}), which is the
	 * fiber's user in {@see \Prado\IO\Async\TFiberContext} for the run:
	 *  - A `dyRunTask` permission denial ({@see PERM_CRON_RUN_TASK}) is logged, advances the task's
	 *    info like a run, and records {@see RESULT_DENIED}.
	 *  - A triggered run is skipped when {@see getTaskSkipReason} gives a reason: the task is paused, its
	 *    {@see \Prado\Util\Cron\TCronTask::getStartingDeadline() StartingDeadline} passed (recorded as
	 *    {@see RESULT_MISSED}), the time is outside its time of day, or a behavior declines it.
	 *  - Unless the task's {@see \Prado\Util\Cron\TCronTask::getOverlap() Overlap} is Allow, the run holds the
	 *    task's run lock ({@see getTaskLockName}).  When another run holds it, the trigger is skipped (Skip),
	 *    stops the run of this process and runs (Replace, {@see stopActiveRun}), or is claimed and waits for
	 *    the lock (Queue, {@see getQueueTimeout}).
	 *  - {@see onTaskStart} is raised; a handler cancels the run.
	 *  - {@see updateTaskInfo} claims the run; when another process claimed it, the task is skipped.
	 *  - A Process task runs in a child process ({@see runTaskProcess}), any other in this fiber
	 *    ({@see invokeTask}); a failed attempt is tried again up to the task's Retries ({@see onTaskRetry});
	 *    a run past the task's WarnDuration raises {@see onTaskOverdue}; the result and seconds are recorded
	 *    by {@see updateTaskResult}, and
	 *    {@see onTaskEnd}, on failure {@see onTaskFailure}, and the task's pings ({@see pingTask}) follow.
	 * A skipped trigger advances the task without counting a run and raises {@see onTaskSkipped}.
	 * After the run, the task's chained tasks run ({@see runChain}).
	 * @param \Prado\Util\Cron\TCronTask $task the task to run.
	 * @param bool $manual whether the run is asked for (`cron/now`) instead of triggered; it is not skipped by {@see getTaskSkipReason}
	 * @return bool whether the task ran without an exception
	 */
	public function runTask($task, bool $manual = false)
	{
		return $this->runChain($task, $manual, []);
	}

	/**
	 * Runs a task as its user, then the tasks it chains: after a run without an exception the task's
	 * {@see \Prado\Util\Cron\TCronTask::getThen() Then}, after a failed run its
	 * {@see \Prado\Util\Cron\TCronTask::getThenOnFailure() ThenOnFailure}.  A chained task runs as a manual
	 * run, after the run lock of the task before it is released; a paused chained task is skipped, and a
	 * task already in the chain is not run again.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @param bool $manual whether the run is asked for or chained instead of triggered
	 * @param string[] $chain the names of the tasks that ran before it in the chain
	 * @return bool whether the task ran without an exception
	 * @since 4.4.0
	 */
	protected function runChain($task, bool $manual, array $chain): bool
	{
		$ran = false;
		$ok = $this->runAsTaskUser($task, function (string $username) use ($task, $manual, $chain, &$ran): bool {
			return $this->runTaskAsUser($task, $username, $manual, $chain, $ran);
		});
		if (!$ran) {
			return $ok;
		}
		$chain[] = $task->getName();
		foreach ($ok ? $task->getThen() : $task->getThenOnFailure() as $name) {
			if (in_array($name, $chain, true)) {
				Prado::log('Cron task (' . $name . ') is already in the chain ' . implode(' > ', $chain) . '; it is not run again', TLogger::WARNING, TCronModule::class);
				continue;
			}
			$this->ensureTasks();
			if (!$this->taskExistsInternal($name) || ($next = $this->getTask($name)) === null) {
				Prado::log('Cron task (' . $task->getName() . ') chains the unknown task (' . $name . ')', TLogger::WARNING, TCronModule::class);
				continue;
			}
			if ($this->getIsTaskPaused($name)) {
				$this->onTaskSkipped((new TCronTaskEventParameter($next, true))->setChain($chain)->setReason(TCronTaskEventParameter::REASON_PAUSED));
				continue;
			}
			Prado::log('Cron task (' . $name . ') runs after (' . $task->getName() . ')', TLogger::INFO, TCronModule::class);
			$this->runChain($next, true, $chain);
		}
		return $ok;
	}

	/**
	 * Runs one task now in this process, as its user, without claiming the run: the `cron/exec`
	 * shell command of a Process task's child process.  The parent process claimed the run, holds the
	 * task's run lock, raises the task events, and sends the pings.
	 * @param \Prado\Util\Cron\TCronTask $task the task to run
	 * @return string the result: {@see RESULT_OK}, {@see RESULT_ERROR}, or {@see RESULT_DENIED}
	 * @since 4.4.0
	 */
	public function executeTask($task): string
	{
		return $this->runAsTaskUser($task, function () use ($task): string {
			if ($this->dyRunTask(false, $task, ['extra' => ['username' => $task->getUserName()]]) === true) {
				$this->dyLogPermissionFailed(static::PERM_CRON_RUN_TASK, 'running cron task \'' . $task->getName() . '\'');
				return static::RESULT_DENIED;
			}
			return ($this->invokeTask($task) === null) ? static::RESULT_OK : static::RESULT_ERROR;
		});
	}

	/**
	 * Calls a callback with the task's user ({@see resolveTaskUser}) as the current fiber's user,
	 * and restores the prior fiber user after.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @param callable $callback `(string $username): mixed`, given the user name looked up
	 * @return mixed the callback's result
	 * @since 4.4.0
	 */
	protected function runAsTaskUser($task, callable $callback): mixed
	{
		$app = $this->getApplication();
		[$user, $username] = $this->resolveTaskUser($task);
		$hadFiberUser = TFiberContext::has(TApplication::USER_CONTEXT_KEY);
		$priorFiberUser = TFiberContext::get(TApplication::USER_CONTEXT_KEY);
		if ($user !== null) {
			TFiberContext::set(TApplication::USER_CONTEXT_KEY, $user);
			$app->onSetUser($user);
		}
		try {
			return $callback($username);
		} finally {
			if ($user !== null) {
				if ($hadFiberUser) {
					TFiberContext::set(TApplication::USER_CONTEXT_KEY, $priorFiberUser);
				} else {
					TFiberContext::remove(TApplication::USER_CONTEXT_KEY);
				}
				if (($restored = $app->getUser()) !== null) {
					$app->onSetUser($restored);
				}
			}
		}
	}

	/**
	 * Runs a task as its user: checks the permission and the starting deadline, holds the run lock,
	 * raises the task events, claims the run, runs the task by its Mode, records the result, and sends
	 * the pings.  See {@see runTask}.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @param string $username the user name the task runs as
	 * @param bool $manual whether the run is asked for instead of triggered
	 * @param string[] $chain the names of the tasks that ran before it in a chain
	 * @param ?bool $ran set to whether the task ran, so its chain follows
	 * @return bool whether the task ran without an exception
	 * @since 4.4.0
	 */
	protected function runTaskAsUser($task, string $username, bool $manual = false, array $chain = [], ?bool &$ran = null): bool
	{
		$ran = false;
		$name = $task->getName();
		$param = (new TCronTaskEventParameter($task, $manual))->setChain($chain);
		if ($this->dyRunTask(false, $task, ['extra' => ['username' => $task->getUserName()]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_RUN_TASK, 'running cron task \'' . $name . '\'');
			$this->updateTaskInfo($task);
			$this->updateTaskResult($task, static::RESULT_DENIED, 0.0);
			$this->onTaskSkipped($param->setReason(TCronTaskEventParameter::REASON_DENIED));
			return false;
		}
		if (!$manual && ($reason = $this->getTaskSkipReason($task)) !== null) {
			Prado::log('Cron task (' . $name . ') is skipped (' . $reason . ')', TLogger::INFO, TCronModule::class);
			$this->updateTaskInfo($task, false);
			if ($reason === TCronTaskEventParameter::REASON_DEADLINE) {
				$this->updateTaskResult($task, static::RESULT_MISSED, 0.0);
			}
			$this->onTaskSkipped($param->setReason($reason));
			return false;
		}
		$overlap = $task->getOverlap();
		$mutex = $this->getDaemonMutex();
		$lockName = ($overlap === TCronTaskOverlap::Allow) ? null : $this->getTaskLockName($name);
		$locked = $queued = false;
		if ($lockName !== null && !($locked = $mutex->acquire($lockName))) {
			if ($overlap === TCronTaskOverlap::Replace && $this->stopActiveRun($name)) {
				Prado::log('Cron task (' . $name . ') stops its running run to replace it', TLogger::INFO, TCronModule::class);
				$locked = $mutex->acquire($lockName, static::REPLACE_TIMEOUT);
			} elseif ($overlap === TCronTaskOverlap::Queue && !isset($this->_queuedRuns[$name])) {
				$queued = $this->_queuedRuns[$name] = true;
			}
			if (!$locked && !$queued) {
				Prado::log('Cron task (' . $name . ') is still running; this trigger is skipped', TLogger::INFO, TCronModule::class);
				$this->updateTaskInfo($task, false);
				$this->onTaskSkipped($param->setReason(TCronTaskEventParameter::REASON_RUNNING));
				return false;
			}
		}
		try {
			$this->onTaskStart($param);
			if ($param->getCancel()) {
				Prado::log('Cron task (' . $name . ') was cancelled by an onTaskStart handler', TLogger::INFO, TCronModule::class);
				$this->updateTaskInfo($task, false);
				$this->onTaskSkipped($param->setReason(TCronTaskEventParameter::REASON_CANCELLED));
				return false;
			}
			if (!$this->updateTaskInfo($task)) {
				Prado::log('Cron task (' . $name . ') was claimed by another process', TLogger::INFO, TCronModule::class);
				$this->onTaskSkipped($param->setReason(TCronTaskEventParameter::REASON_CLAIMED));
				return false;
			}
			if ($queued) {
				Prado::log('Cron task (' . $name . ') waits for its running run', TLogger::INFO, TCronModule::class);
				$locked = $mutex->acquire($lockName, $this->getQueueTimeout($task));
				unset($this->_queuedRuns[$name]);
				$queued = false;
				if (!$locked) {
					Prado::log('Cron task (' . $name . ') waited too long for its running run; this trigger is skipped', TLogger::WARNING, TCronModule::class);
					$this->onTaskSkipped($param->setReason(TCronTaskEventParameter::REASON_RUNNING));
					return false;
				}
			}
			if ($locked) {
				$this->_activeRuns[$name] = ['token' => null, 'process' => null, 'replaced' => false];
			}
			$logId = $this->logCronTask($task, $username);
			$runId = $this->beginRunStatus($task, $username);
			$this->pingTask($task, TCronTask::PING_BEFORE, $param);
			$start = $this->getMonotonicClock()->seconds();
			$warn = $task->getWarnDuration();
			$overdue = false;
			$timer = ($warn === null) ? null : TAsync::getLoop()->delay($warn, function () use (&$overdue, $task, $param, $runId, $start) {
				$overdue = true;
				$this->markTaskOverdue($task, $param, $runId, $this->getMonotonicClock()->seconds() - $start);
			});
			try {
				for ($attempt = 1; ; $attempt++) {
					$exception = null;
					if ($task->getMode() === TCronTaskMode::Process) {
						$result = $this->runTaskProcess($task, $runId);
					} else {
						$exception = $this->invokeTask($task);
						$result = ($exception === null) ? static::RESULT_OK : static::RESULT_ERROR;
					}
					if ($this->_activeRuns[$name]['replaced'] ?? false) {
						$result = static::RESULT_REPLACED;
						break;
					}
					if ($result === static::RESULT_OK || $result === static::RESULT_DENIED || $attempt > $task->getRetries()) {
						break;
					}
					$wait = $task->getRetryWait($attempt);
					Prado::log('Cron task (' . $name . ') ' . $result . '; retry ' . $attempt . ' of ' . $task->getRetries() . ' in ' . $wait . 's', TLogger::WARNING, TCronModule::class);
					$this->onTaskRetry($param->setAttempt($attempt)->setResult($result)->setException($exception)->setDuration($this->getMonotonicClock()->seconds() - $start));
					if ($wait > 0) {
						TAsync::sleep($wait);
					}
				}
			} finally {
				if ($timer !== null) {
					TAsync::getLoop()->cancel($timer);
				}
			}
			$duration = $this->getMonotonicClock()->seconds() - $start;
			if ($warn !== null && !$overdue && $duration > $warn) {
				$this->markTaskOverdue($task, $param, $runId, $duration);
			}
			if ($result === static::RESULT_OK) {
				$this->logCronTaskEnd($task);
			}
			$this->updateTaskResult($task, $result, $duration, $logId);
			if ($task->getScheduler()->getIsAfterEnd()) {
				$this->updateTaskEnd($task);
			}
			$this->endRunStatus($runId, $result, $duration);
			$param->setAttempt($attempt)->setResult($result)->setDuration($duration)->setException($exception);
			$this->onTaskEnd($param);
			if ($result !== static::RESULT_OK) {
				$this->onTaskFailure($param);
			}
			$this->pingTask($task, TCronTask::PING_AFTER, $param);
			$this->pingTask($task, ($result === static::RESULT_OK) ? TCronTask::PING_SUCCESS : TCronTask::PING_FAILURE, $param);
			$ran = true;
			return $result === static::RESULT_OK;
		} finally {
			if ($queued) {
				unset($this->_queuedRuns[$name]);
			}
			if ($locked) {
				unset($this->_activeRuns[$name]);
				$mutex->release($lockName);
			}
		}
	}

	/**
	 * Stops the run of a task that holds its run lock in this process, for a trigger with Overlap
	 * Replace: a Process task's child is terminated, and a task in this process is cancelled through
	 * its {@see \Prado\Util\Cron\TCronTask::getCancellationToken() CancellationToken}.  The run ends with
	 * {@see RESULT_REPLACED}.
	 * @param string $name the task name
	 * @return bool whether a run of the task holds its lock in this process
	 * @since 4.4.0
	 */
	protected function stopActiveRun(string $name): bool
	{
		if (!isset($this->_activeRuns[$name])) {
			return false;
		}
		$this->_activeRuns[$name]['replaced'] = true;
		if (($process = $this->_activeRuns[$name]['process']) !== null) {
			$process->terminate();
		} elseif (($token = $this->_activeRuns[$name]['token']) !== null) {
			$token->cancel();
		}
		return true;
	}

	/**
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return float the seconds a Queue trigger waits for the running run: the task's Timeout, else
	 *   {@see getTaskTimeout TaskTimeout}, else {@see DEFAULT_QUEUE_TIMEOUT}
	 * @since 4.4.0
	 */
	protected function getQueueTimeout($task): float
	{
		return $task->getTimeout() ?? $this->getTaskTimeout() ?? (float) static::DEFAULT_QUEUE_TIMEOUT;
	}

	/**
	 * Warns that a run passed its task's {@see \Prado\Util\Cron\TCronTask::getWarnDuration() WarnDuration}:
	 * a warning log entry, the run's status entry marked overdue, and {@see onTaskOverdue}.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @param TCronTaskEventParameter $param the run's event parameter
	 * @param ?int $runId the run's status entry
	 * @param float $elapsed the seconds the run has taken
	 * @since 4.4.0
	 */
	protected function markTaskOverdue($task, TCronTaskEventParameter $param, ?int $runId, float $elapsed): void
	{
		Prado::log('Cron task (' . $task->getName() . ') has run ' . round($elapsed, 3) . 's, past its WarnDuration of ' . $task->getWarnDuration() . 's', TLogger::WARNING, TCronModule::class);
		if ($runId !== null && isset($this->_status['running'][$runId])) {
			$this->_status['running'][$runId]['overdue'] = true;
			$this->writeStatus();
		}
		$this->onTaskOverdue($param->setDuration($elapsed));
	}

	/**
	 * Tells whether a run would start later than the task's
	 * {@see \Prado\Util\Cron\TCronTask::getStartingDeadline() StartingDeadline} after its trigger.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return bool whether the deadline passed
	 * @since 4.4.0
	 */
	protected function isTaskPastDeadline($task): bool
	{
		$deadline = $task->getStartingDeadline();
		$trigger = ($deadline === null) ? null : $task->getNextTriggerTime();
		return $trigger !== null && $this->time() - $trigger > $deadline;
	}

	/**
	 * Returns why a triggered run does not start, in order: the task is paused ({@see pauseTask}), its
	 * StartingDeadline passed, the time is outside its {@see \Prado\Util\Cron\TCronTask::getIsInWindow() time of day},
	 * or a behavior's `dyShouldRunTask($run, $task)` returns false.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return ?string a {@see TCronTaskEventParameter} `REASON_*` constant, or null when the run starts
	 * @since 4.4.0
	 */
	protected function getTaskSkipReason($task): ?string
	{
		if ($this->getIsTaskPaused($task->getName())) {
			return TCronTaskEventParameter::REASON_PAUSED;
		}
		if ($this->isTaskPastDeadline($task)) {
			return TCronTaskEventParameter::REASON_DEADLINE;
		}
		if (!$task->getIsInWindow($this->time())) {
			return TCronTaskEventParameter::REASON_WINDOW;
		}
		if ($this->dyShouldRunTask(true, $task) === false) {
			return TCronTaskEventParameter::REASON_CONDITION;
		}
		return null;
	}

	/**
	 * Pauses a task: its triggers pass without runs until {@see resumeTask}.  A manual run (`cron/now`)
	 * still runs it.  The paused tasks are stored for every process: in a JSON file in the runtime path
	 * ({@see getPausedFile}), or the `paused` column of TDbCronManager.
	 * @param string $name the task name
	 * @return bool whether the task exists
	 * @since 4.4.0
	 */
	public function pauseTask(string $name): bool
	{
		$this->ensureTasks();
		if (!$this->taskExistsInternal($name)) {
			return false;
		}
		$this->writeTaskPaused($name, true);
		return true;
	}

	/**
	 * Resumes a paused task: its next trigger runs.
	 * @param string $name the task name
	 * @return bool whether the task exists
	 * @since 4.4.0
	 */
	public function resumeTask(string $name): bool
	{
		$this->ensureTasks();
		if (!$this->taskExistsInternal($name)) {
			return false;
		}
		$this->writeTaskPaused($name, false);
		return true;
	}

	/**
	 * @param string $name the task name
	 * @return bool whether the task is paused
	 * @since 4.4.0
	 */
	public function getIsTaskPaused(string $name): bool
	{
		return in_array($name, $this->getPausedTasks(), true);
	}

	/**
	 * @return string[] the names of the paused tasks, read from the store each call
	 * @since 4.4.0
	 */
	public function getPausedTasks(): array
	{
		$file = $this->getPausedFile();
		if (!is_file($file)) {
			return [];
		}
		$names = json_decode((string) @file_get_contents($file), true);
		return is_array($names) ? array_values(array_filter($names, 'is_string')) : [];
	}

	/**
	 * Stores whether a task is paused in the {@see getPausedFile paused file}, under an exclusive lock.
	 * @param string $name the task name
	 * @param bool $paused whether the task is paused
	 * @since 4.4.0
	 */
	protected function writeTaskPaused(string $name, bool $paused): void
	{
		$file = $this->getPausedFile();
		if (($handle = @fopen($file, 'c+')) === false) {
			Prado::log('Cron paused file (' . $file . ') could not be opened', TLogger::WARNING, TCronModule::class);
			return;
		}
		try {
			flock($handle, LOCK_EX);
			$names = json_decode((string) stream_get_contents($handle), true);
			$names = is_array($names) ? array_values(array_filter($names, 'is_string')) : [];
			$names = $paused ? array_values(array_unique(array_merge($names, [$name]))) : array_values(array_diff($names, [$name]));
			sort($names);
			ftruncate($handle, 0);
			rewind($handle);
			fwrite($handle, (string) json_encode($names));
			fflush($handle);
			flock($handle, LOCK_UN);
		} finally {
			fclose($handle);
		}
	}

	/**
	 * @return string the file of the paused task names: {@see PAUSED_FILE_PREFIX} and the module ID in the runtime path
	 * @since 4.4.0
	 */
	protected function getPausedFile(): string
	{
		return $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . static::PAUSED_FILE_PREFIX . preg_replace('/[^\w.-]+/', '_', (string) $this->getID()) . '.json';
	}

	/**
	 * Runs a task at once, as {@see runTask} with `$manual`, and publishes the live status of the run
	 * with {@see getEnableStatus EnableStatus}, so `cron/top` shows it; the `cron/now` shell command.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return bool whether the task ran without an exception
	 * @since 4.4.0
	 */
	public function runTaskNow($task): bool
	{
		$status = $this->beginStatus(static::STATUS_ROLE_MANUAL);
		try {
			return $this->runTask($task, true);
		} finally {
			$this->awaitProcessRuns();
			if ($status) {
				$this->endStatus();
			}
		}
	}

	/**
	 * Executes a task in the current fiber.  A deadline from the task's Timeout or the module's
	 * {@see getTaskTimeout} becomes the task's
	 * {@see \Prado\Util\Cron\TCronTask::getCancellationToken() CancellationToken}; a run holding its run
	 * lock gets a token without a deadline too, which a Replace trigger cancels.  An exception from
	 * the task is logged by {@see logCronTaskError} and does not leave this method.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return ?\Throwable the exception the task threw, or null when it ran without one
	 * @since 4.4.0
	 */
	protected function invokeTask($task): ?\Throwable
	{
		$timeout = $task->getTimeout() ?? $this->getTaskTimeout();
		$name = $task->getName();
		$token = ($timeout !== null || isset($this->_activeRuns[$name])) ? new TCancellationToken($timeout) : null;
		$task->setCancellationToken($token);
		if (isset($this->_activeRuns[$name])) {
			$this->_activeRuns[$name]['token'] = $token;
		}
		try {
			$task->execute($this);
			return null;
		} catch (\Throwable $e) {
			$this->logCronTaskError($task, $e);
			return $e;
		} finally {
			$task->setCancellationToken(null);
			if (isset($this->_activeRuns[$name])) {
				$this->_activeRuns[$name]['token'] = null;
			}
		}
	}

	/**
	 * Sends a task's ping of a point in a run when the task has its URL.  {@see onTaskPing} is raised
	 * first; when no handler delivered the ping, {@see sendPing} sends it.  A failed ping is a warning
	 * log entry and does not affect the run.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @param string $ping {@see \Prado\Util\Cron\TCronTask::PING_BEFORE}, `PING_AFTER`, `PING_SUCCESS`, or `PING_FAILURE`
	 * @param TCronTaskEventParameter $run the run's event parameter, with its result and seconds after the run
	 * @since 4.4.0
	 */
	protected function pingTask($task, string $ping, TCronTaskEventParameter $run): void
	{
		if (($url = $task->getPingUrl($ping)) === null) {
			return;
		}
		$param = (new TCronTaskEventParameter($task, $run->getManual()))->setPing($ping)->setUrl($url)
			->setResult($run->getResult())->setDuration($run->getDuration());
		try {
			$this->onTaskPing($param);
			if (!$param->getHandled()) {
				$this->sendPing($param);
			}
		} catch (\Throwable $e) {
			Prado::log('Cron task (' . $task->getName() . ') ' . $ping . ' ping to ' . parse_url($url, PHP_URL_HOST) . ' failed: ' . $e->getMessage(), TLogger::WARNING, TCronModule::class);
		}
	}

	/**
	 * Sends a ping as an HTTP GET request through the {@see getHttpClient HttpClient}.  A response
	 * other than 2xx is a warning log entry.
	 * @param TCronTaskEventParameter $param the ping, with its task, point, and URL
	 * @throws \Prado\IO\HttpClient\THttpClientException when the request fails
	 * @since 4.4.0
	 */
	protected function sendPing(TCronTaskEventParameter $param): void
	{
		$response = $this->getHttpClient()->download('GET', (string) $param->getUrl());
		if (!$response->isSuccess()) {
			Prado::log('Cron task (' . $param->getTask()->getName() . ') ' . $param->getPing() . ' ping to ' . parse_url((string) $param->getUrl(), PHP_URL_HOST) . ' answered ' . $response->getStatusCode(), TLogger::WARNING, TCronModule::class);
		}
	}

	/**
	 * @throws \Prado\IO\HttpClient\THttpClientException when no HTTP transport is available
	 * @return THttpClient the client that sends the task pings; defaults to {@see THttpClient::create()}
	 * @since 4.4.0
	 */
	public function getHttpClient(): THttpClient
	{
		return $this->_httpClient ??= THttpClient::create();
	}

	/**
	 * @param ?THttpClient $client the client that sends the task pings; null restores the default
	 * @since 4.4.0
	 */
	public function setHttpClient(?THttpClient $client): void
	{
		$this->_httpClient = $client;
	}

	/**
	 * Raises the `OnTaskStart` event, and `fxCronTaskStart` globally, before a run is claimed.  A handler
	 * sets {@see TCronTaskEventParameter::setCancel Cancel} to skip the run.
	 * @param TCronTaskEventParameter $param the task and whether the run is manual
	 * @since 4.4.0
	 */
	public function onTaskStart($param)
	{
		$this->raiseEvent('OnTaskStart', $this, $param);
		$this->raiseEvent('fxCronTaskStart', $this, $param);
	}

	/**
	 * Raises the `OnTaskEnd` event, and `fxCronTaskEnd` globally, after a run with its result, seconds, and exception.
	 * @param TCronTaskEventParameter $param the finished run
	 * @since 4.4.0
	 */
	public function onTaskEnd($param)
	{
		$this->raiseEvent('OnTaskEnd', $this, $param);
		$this->raiseEvent('fxCronTaskEnd', $this, $param);
	}

	/**
	 * Raises the `OnTaskFailure` event, and `fxCronTaskFailure` globally, after `onTaskEnd` for a run
	 * whose result is not {@see RESULT_OK}.
	 * @param TCronTaskEventParameter $param the failed run
	 * @since 4.4.0
	 */
	public function onTaskFailure($param)
	{
		$this->raiseEvent('OnTaskFailure', $this, $param);
		$this->raiseEvent('fxCronTaskFailure', $this, $param);
	}

	/**
	 * Raises the `OnTaskOverdue` event, and `fxCronTaskOverdue` globally, once for a run that passes its
	 * task's WarnDuration: while the run waits on the event loop, or after a run that blocked.
	 * @param TCronTaskEventParameter $param the run, with the seconds it has taken
	 * @since 4.4.0
	 */
	public function onTaskOverdue($param)
	{
		$this->raiseEvent('OnTaskOverdue', $this, $param);
		$this->raiseEvent('fxCronTaskOverdue', $this, $param);
	}

	/**
	 * Raises the `OnTaskRetry` event, and `fxCronTaskRetry` globally, after a failed attempt that is tried
	 * again, with the {@see TCronTaskEventParameter::getAttempt Attempt}, its result, and its exception.
	 * @param TCronTaskEventParameter $param the failed attempt
	 * @since 4.4.0
	 */
	public function onTaskRetry($param)
	{
		$this->raiseEvent('OnTaskRetry', $this, $param);
		$this->raiseEvent('fxCronTaskRetry', $this, $param);
	}

	/**
	 * Raises the `OnTaskSkipped` event, and `fxCronTaskSkipped` globally, for a trigger that does not run,
	 * with the {@see TCronTaskEventParameter::getReason Reason}.
	 * @param TCronTaskEventParameter $param the skipped trigger
	 * @since 4.4.0
	 */
	public function onTaskSkipped($param)
	{
		$this->raiseEvent('OnTaskSkipped', $this, $param);
		$this->raiseEvent('fxCronTaskSkipped', $this, $param);
	}

	/**
	 * Raises the `OnTaskPing` event, and `fxCronTaskPing` globally, before a task's ping is sent.  A handler
	 * that delivers the ping itself, such as a webhook module, sets {@see TCronTaskEventParameter::setHandled Handled}
	 * so {@see sendPing} does not send it.
	 * @param TCronTaskEventParameter $param the ping: the task, the point of the run, the URL, and after the run its result and seconds
	 * @since 4.4.0
	 */
	public function onTaskPing($param)
	{
		$this->raiseEvent('OnTaskPing', $this, $param);
		$this->raiseEvent('fxCronTaskPing', $this, $param);
	}

	/**
	 * Runs a Process task in a child process ({@see openTaskProcess}) and waits for it in the current
	 * fiber.  A child that outlives the task's timeout by {@see PROCESS_TIMEOUT_GRACE} seconds is
	 * terminated.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @param ?int $runId the run's status entry ({@see beginRunStatus}), given the child's process ID
	 * @return string {@see RESULT_OK} for exit code 0, {@see RESULT_DENIED} for {@see EXIT_DENIED},
	 *   {@see RESULT_TIMEOUT} when terminated, else {@see RESULT_ERROR}
	 * @since 4.4.0
	 */
	protected function runTaskProcess($task, ?int $runId = null): string
	{
		try {
			$process = $this->openTaskProcess($task);
		} catch (\Throwable $e) {
			$this->logCronTaskError($task, $e);
			return static::RESULT_ERROR;
		}
		$pid = $process->getPid();
		$this->setRunStatusPid($runId, $pid);
		$name = $task->getName();
		if (isset($this->_activeRuns[$name])) {
			$this->_activeRuns[$name]['process'] = $process;
		}
		if (($priority = $task->getProcessPriority()) !== null && $pid !== null && !$this->setTaskProcessPriority($pid, $priority)) {
			Prado::log('Cron task (' . $name . ') process ' . $pid . ' could not take the priority ' . $priority, TLogger::WARNING, TCronModule::class);
		}
		$timeout = $task->getTimeout() ?? $this->getTaskTimeout();
		$limit = ($timeout === null) ? 0 : (int) (($timeout + static::PROCESS_TIMEOUT_GRACE) * 1_000_000);
		$code = $process->wait(static::PROCESS_POLL_INTERVAL, $limit);
		if ($process->getIsRunning()) {
			Prado::log('Cron task (' . $task->getName() . ') process ' . $pid . ' outlived its timeout and is terminated', TLogger::ERROR, TCronModule::class);
			$process->terminate();
			if ($process->wait(static::PROCESS_POLL_INTERVAL, (int) (static::PROCESS_TIMEOUT_GRACE * 1_000_000)) === -1) {
				$process->terminate(9); // SIGKILL
			}
			$process->close();
			if (isset($this->_activeRuns[$name])) {
				$this->_activeRuns[$name]['process'] = null;
			}
			return static::RESULT_TIMEOUT;
		}
		$process->close();
		if (isset($this->_activeRuns[$name])) {
			$this->_activeRuns[$name]['process'] = null;
		}
		if ($code === 0) {
			return static::RESULT_OK;
		}
		if ($code === static::EXIT_DENIED) {
			return static::RESULT_DENIED;
		}
		Prado::log('Cron task (' . $task->getName() . ') process ' . $pid . ' exited with code ' . $code, TLogger::ERROR, TCronModule::class);
		return static::RESULT_ERROR;
	}

	/**
	 * Sets the scheduling priority of a Process task's child through {@see TProcessHelper::setProcessPriority()}.
	 * @param int $pid the child's process ID
	 * @param int $priority the priority, -20 (highest) to 19 (lowest)
	 * @return bool whether the priority was set
	 * @since 4.4.0
	 */
	protected function setTaskProcessPriority(int $pid, int $priority): bool
	{
		return TProcessHelper::setProcessPriority($priority, $pid);
	}

	/**
	 * Opens the child process of a Process task: {@see getTaskProcessCommand}, with no standard
	 * input, and the standard output and error of this process, or appended to the task's ProcessOutputFile.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return TProcess the running child process
	 * @since 4.4.0
	 */
	protected function openTaskProcess($task): TProcess
	{
		$null = TProcessHelper::isSystemWindows() ? 'NUL' : '/dev/null';
		$output = $task->getProcessOutputFile();
		$out = ($output === null) ? false : ['file', $output, 'a'];
		return TProcess::open($this->getTaskProcessCommand($task), [0 => ['file', $null, 'r'], 1 => $out, 2 => $out], null, null, ['bypass_shell' => true]);
	}

	/**
	 * Returns the command of a Process task's child process: `prado-cli -d=<app> --quiet=1 cron/exec <task>`
	 * on the PHP command line binary, without the shell banner; the task's ProcessMemoryLimit is the
	 * binary's `-d memory_limit=` option.  The PHP binary of a web server SAPI is replaced by `php` in
	 * {@see PHP_BINDIR}.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return string[] the command and its arguments
	 * @since 4.4.0
	 */
	public function getTaskProcessCommand($task): array
	{
		$php = ($this->php_sapi_name() === 'cli') ? PHP_BINARY : PHP_BINDIR . DIRECTORY_SEPARATOR . (TProcessHelper::isSystemWindows() ? 'php.exe' : 'php');
		$cli = dirname(Prado::getFrameworkPath()) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'prado-cli.php';
		$limit = $task->getProcessMemoryLimit();
		return array_merge([$php], ($limit === null) ? [] : ['-d', 'memory_limit=' . $limit], [$cli, '-d=' . $this->getApplication()->getBasePath(), '--quiet=1', 'cron/exec', $task->getName()]);
	}

	/**
	 * @param string $name the task name
	 * @return string the lock name a run of the task holds without Overlap, {@see getDaemonLockName} and `:task:<name>`
	 * @since 4.4.0
	 */
	public function getTaskLockName(string $name): string
	{
		return $this->getDaemonLockName() . ':task:' . $name;
	}

	/**
	 * Resolves the user a task runs as: the task's UserName, else {@see getDefaultUserName},
	 * looked up in the {@see getUserManager}; without a match, a guest copy of the
	 * application user.  Without a user manager the task runs as the current user.
	 * @param \Prado\Util\Cron\TCronTask $task the task
	 * @return array{0: ?IUser, 1: string} the user to run as, or null to keep the current user, and the user name looked up last
	 * @since 4.4.0
	 */
	protected function resolveTaskUser($task): array
	{
		$defaultUsername = $username = $this->getDefaultUserName();
		$users = $this->getUserManager();
		if (!$users) {
			return [null, $username];
		}
		if ($nusername = $task->getUserName()) {
			$username = $nusername;
		}
		$user = $users->getUser($username);
		if (!$user && $username !== $defaultUsername) {
			$user = $users->getUser($username = $defaultUsername);
		}
		if (!$user && ($current = $this->getApplication()->getUser()) !== null) {
			$user = clone $current;
			$user->setIsGuest(true);
		}
		return [$user ?: null, $username];
	}

	/**
	 * Logs the cron task being run with the system log and output on cli
	 * @param TCronTask $task
	 * @param string $username the user the task is running under.
	 * @return mixed the run's log entry ID, which {@see updateTaskResult} receives; null in TCronModule
	 */
	protected function logCronTask($task, $username)
	{
		Prado::log('Running cron task (' . $task->getName() . ', ' . $username . ')', TLogger::INFO, TCronModule::class);
		$this->dyLogCronTask($task, $username);
		return null;
	}

	/**
	 * Claims the run of a task: sets the lastExecTime to now and, for a run, increments the
	 * processCount, and saves the new data to the global state.  A skipped trigger claims without a
	 * run.  TCronModule has one process per application, so every claim succeeds; TDbCronManager
	 * claims against the database row.
	 * @param TCronTask $task
	 * @param bool $run whether the task runs; false for a skipped trigger
	 * @return bool whether this process claimed the run
	 */
	protected function updateTaskInfo($task, bool $run = true)
	{
		$tasksInfo = $this->getApplication()->getGlobalState(self::TASKS_INFO, []);
		$name = $task->getName();
		$time = $tasksInfo[$name]['lastExecTime'] = $this->time();
		$count = $tasksInfo[$name]['processCount'] = $task->getProcessCount() + ($run ? 1 : 0);
		$task->setProcessCount($count);
		$task->setLastExecTime($time);

		$this->getApplication()->setGlobalState(self::TASKS_INFO, $tasksInfo, [], true);
		return true;
	}

	/**
	 * Records the result of a run and the seconds it took in the global state.
	 * @param TCronTask $task the task
	 * @param string $result {@see RESULT_OK}, {@see RESULT_ERROR}, {@see RESULT_DENIED}, or {@see RESULT_TIMEOUT}
	 * @param float $duration the seconds the run took
	 * @param mixed $logId the run's log entry ID from {@see logCronTask}; unused in TCronModule
	 * @since 4.4.0
	 */
	protected function updateTaskResult($task, string $result, float $duration, mixed $logId = null)
	{
		$app = $this->getApplication();
		$tasksInfo = $app->getGlobalState(self::TASKS_INFO, []);
		$name = $task->getName();
		$tasksInfo[$name]['lastResult'] = $result;
		$tasksInfo[$name]['lastDuration'] = round($duration, 6);
		$app->setGlobalState(self::TASKS_INFO, $tasksInfo, [], true);
	}

	/**
	 * Sets the task's LastExecTime to the end of its run, for a schedule 'every <interval> after end', whose
	 * next trigger counts from that time; saves it to the global state.
	 * @param TCronTask $task the task
	 * @since 4.4.0
	 */
	protected function updateTaskEnd($task)
	{
		$app = $this->getApplication();
		$tasksInfo = $app->getGlobalState(self::TASKS_INFO, []);
		$task->setLastExecTime($tasksInfo[$task->getName()]['lastExecTime'] = $this->time());
		$app->setGlobalState(self::TASKS_INFO, $tasksInfo, [], true);
	}

	/**
	 * Returns the run information of each task: the time of its last run, the run count, and the
	 * result and seconds of its last finished run.
	 * @param bool $fresh whether to read the stored information instead of this process's copy
	 * @return array<string, array{time: ?int, count: int, result: ?string, duration: ?float}> the run information, by task name
	 * @since 4.4.0
	 */
	public function getTaskResults(bool $fresh = false): array
	{
		$tasksInfo = $this->readTasksInfo($fresh);
		$results = [];
		foreach ($this->ensureTasks() as $name => $task) {
			$info = $tasksInfo[$name] ?? [];
			$results[$name] = [
				'time' => isset($info['lastExecTime']) ? (int) $info['lastExecTime'] : null,
				'count' => (int) ($info['processCount'] ?? 0),
				'result' => $info['lastResult'] ?? null,
				'duration' => isset($info['lastDuration']) ? (float) $info['lastDuration'] : null,
			];
		}
		return $results;
	}

	/**
	 * Returns the soonest next triggers of the tasks.
	 * @param int $limit the number of triggers to return
	 * @param bool $fresh whether to read the stored run information instead of this process's copy
	 * @return array<string, int> the trigger times, by task name, soonest first
	 * @since 4.4.0
	 */
	public function getNextTriggers(int $limit = 10, bool $fresh = false): array
	{
		$tasksInfo = $fresh ? $this->readTasksInfo(true) : null;
		$triggers = [];
		foreach ($this->ensureTasks() as $name => $task) {
			if ($tasksInfo === null) {
				$trigger = $task->getNextTriggerTime();
			} else {
				$trigger = $task->getNextTriggerTime(isset($tasksInfo[$name]['lastExecTime']) ? (int) $tasksInfo[$name]['lastExecTime'] : 0);
			}
			if ($trigger !== null) {
				$triggers[$name] = (int) $trigger;
			}
		}
		asort($triggers);
		return array_slice($triggers, 0, max(0, $limit), true);
	}

	/**
	 * Returns the run information of the tasks from the global state.
	 * @param bool $fresh whether to read the stored global state, which another process may have
	 *   changed, instead of this process's copy
	 * @return array<string, array> the information of each task, by name
	 * @since 4.4.0
	 */
	protected function readTasksInfo(bool $fresh = false): array
	{
		$app = $this->getApplication();
		if ($fresh && ($persister = $app->getApplicationStatePersister()) !== null) {
			$state = $persister->load();
			return (is_array($state) && is_array($state[self::TASKS_INFO] ?? null)) ? $state[self::TASKS_INFO] : [];
		}
		return $app->getGlobalState(self::TASKS_INFO, []);
	}

	/**
	 * Logs the end of the task.
	 * @param TCronTask $task
	 */
	protected function logCronTaskEnd($task)
	{
		Prado::log('Ending cron task (' . $task->getName() . ', ' . $task->getTask() . ')', TLogger::INFO, TCronModule::class);
		$this->dyLogCronTaskEnd($task);
	}

	/**
	 * Logs the exception that ended a task, at the error level, and raises `dyLogCronTaskError`.
	 * @param TCronTask $task the task that threw
	 * @param \Throwable $exception the exception
	 * @since 4.4.0
	 */
	protected function logCronTaskError($task, \Throwable $exception)
	{
		Prado::log('Cron task (' . $task->getName() . ', ' . $task->getTask() . ') failed: ' . $exception::class . ': ' . $exception->getMessage(), TLogger::ERROR, TCronModule::class);
		$this->dyLogCronTaskError($task, $exception);
	}

	/**
	 * @return float time that cron last was last run
	 */
	public function getLastCronTime()
	{
		return $this->getApplication()->getGlobalState(self::LAST_CRON_TIME, 0);
	}

	/**
	 * @param float $time time that cron was last run
	 */
	public function setLastCronTime($time)
	{
		$this->getApplication()->setGlobalState(self::LAST_CRON_TIME, TPropertyValue::ensureFloat($time), 0, true);
	}

	/**
	 * Objects should handle fxGetCronTasks($sender, $param)
	 * @param bool $forceupdate if true, ignores the caching
	 * @return \Prado\Util\Cron\TCronTaskInfo[]
	 */
	public function getTaskInfos($forceupdate = false)
	{
		if (!$this->_tasksInfo || $forceupdate) {
			$this->_tasksInfo = $this->raiseEvent('fxGetCronTaskInfos', $this, null);
		}
		return $this->_tasksInfo;
	}

	/**
	 * Returns the soonest next trigger time among the tasks, which a daemon waits for.
	 * @return ?int the Unix timestamp of the next trigger, or null when no task has one
	 * @since 4.4.0
	 */
	public function getNextPendingTime(): ?int
	{
		$this->ensureTasks();
		$next = null;
		foreach ($this->_tasks as $task) {
			$trigger = $task->getNextTriggerTime();
			if ($trigger !== null && ($next === null || $trigger < $next)) {
				$next = (int) $trigger;
			}
		}
		return $next;
	}

	/**
	 * TCronModule does not support Second Resolution due to using the global state.
	 * If you need this attribute, use the TDbCronManager.
	 * @return bool returns false
	 * @since 4.4.0
	 */
	public function getSecondResolution()
	{
		return false;
	}

	/**
	 * TCronModule polls once per minute. Sub-minute polling requires the per-task next execution
	 * times of the TDbCronManager; see {@see \Prado\Util\Cron\TDbCronManager::getPollsPerMinute PollsPerMinute}.
	 * @return int returns 1
	 * @since 4.4.0
	 */
	public function getPollsPerMinute()
	{
		return 1;
	}

	/**
	 * @return string the default user id of Tasks without users ids, default {@see DEFAULT_CRON_USER}
	 */
	public function getDefaultUserName()
	{
		return $this->_defaultUserName ?? static::DEFAULT_CRON_USER;
	}

	/**
	 * @param string $id the default user id of Tasks without users ids
	 * @throws TInvalidOperationException if the module has been initialized
	 */
	public function setDefaultUserName($id)
	{
		$this->assertUninitialized('DefaultUserName');
		$this->_defaultUserName = TPropertyValue::ensureString($id);
	}

	/**
	 * @return ?IUserManager user manager instance
	 */
	public function getUserManager()
	{
		return $this->_userManager;
	}

	/**
	 * @param null|IUserManager|string $provider the user manager module ID or the user manager object
	 * @throws TInvalidOperationException if the module has been initialized
	 * @throws TConfigurationException if the user manager is not a string, not an instance of IUserManager, and not null
	 */
	public function setUserManager($provider)
	{
		$this->assertUninitialized('UserManager');
		if (!is_string($provider) && !($provider instanceof IUserManager) && $provider !== null) {
			throw new TConfigurationException('cron_usermanager_invalid', get_debug_type($provider));
		}
		$this->_userManager = $provider;
	}

	/**
	 * @return bool allow request cron task processing, default false
	 */
	public function getEnableRequestCron()
	{
		return $this->_enableRequestCron;
	}

	/**
	 * @param mixed $allow request cron task processing
	 * @throws TInvalidOperationException if the module has been initialized
	 */
	public function setEnableRequestCron($allow)
	{
		$this->assertUninitialized('EnableRequestCron');
		$this->_enableRequestCron = TPropertyValue::ensureBoolean($allow);
	}

	/**
	 * @return numeric the probability 0.0 .. 100.0 of a request triggering a cron, default 1.0 (out of 100.0).
	 */
	public function getRequestCronProbability()
	{
		return $this->_requestCronProbability;
	}

	/**
	 * @param numeric $probability the probability 0.0..100.0 of a request triggering a cron
	 * @throws TInvalidOperationException if the module has been initialized
	 */
	public function setRequestCronProbability($probability)
	{
		$this->assertUninitialized('RequestCronProbability');
		$this->_requestCronProbability = TPropertyValue::ensureFloat($probability);
	}

	/**
	 * @return ?bool is cli running from cron.
	 * @since 4.2.2
	 */
	public function getInCronShell()
	{
		return $this->_inCronShell;
	}

	/**
	 * @param ?bool $inCronShell is cli running from cron.  This limits
	 * pending tasks to only the current minute.
	 * @since 4.2.2
	 */
	public function setInCronShell($inCronShell)
	{
		$this->_inCronShell = $inCronShell === null ? null : TPropertyValue::ensureBoolean($inCronShell === '' ? true : $inCronShell);
	}

	/**
	 * @return int the number of Parallel tasks that run at once, default 1
	 * @since 4.4.0
	 */
	public function getConcurrency(): int
	{
		return $this->_concurrency;
	}

	/**
	 * @param int|string $count the number of Parallel tasks that run at once; 1 runs them in order
	 * @throws TInvalidDataValueException when the count is less than 1
	 * @since 4.4.0
	 */
	public function setConcurrency($count)
	{
		$count = TPropertyValue::ensureInteger($count);
		if ($count < 1) {
			throw new TInvalidDataValueException('cron_concurrency_invalid', $count);
		}
		$this->_concurrency = $count;
	}

	/**
	 * @return ?float the seconds a task run may take unless the task has its own Timeout, or null for no deadline
	 * @since 4.4.0
	 */
	public function getTaskTimeout(): ?float
	{
		return $this->_taskTimeout;
	}

	/**
	 * @param null|float|string $seconds the seconds a task run may take unless the task has its own Timeout, or null for no deadline
	 * @throws TInvalidDataValueException when the seconds are not greater than zero
	 * @since 4.4.0
	 */
	public function setTaskTimeout($seconds)
	{
		if ($seconds !== null && $seconds !== '') {
			$seconds = TPropertyValue::ensureFloat($seconds);
			if ($seconds <= 0) {
				throw new TInvalidDataValueException('cron_task_timeout_invalid', $seconds);
			}
		} else {
			$seconds = null;
		}
		$this->_taskTimeout = $seconds;
	}

	/**
	 * @return ?string the time zone of the tasks without their own TimeZone, or null for the application time zone
	 * @since 4.4.0
	 */
	public function getTimeZone(): ?string
	{
		return $this->_timeZone;
	}

	/**
	 * @param ?string $timeZone the time zone of the tasks without their own TimeZone; null or '' for the application time zone
	 * @throws TInvalidDataValueException when the time zone is not known
	 * @since 4.4.0
	 */
	public function setTimeZone($timeZone)
	{
		$timeZone = ($timeZone === null || $timeZone === '') ? null : TPropertyValue::ensureString($timeZone);
		if ($timeZone !== null) {
			try {
				new \DateTimeZone($timeZone);
			} catch (\Exception $e) {
				throw new TInvalidDataValueException('cron_timezone_invalid', $timeZone);
			}
		}
		$this->_timeZone = $timeZone;
		foreach ($this->_tasks as $task) {
			if ($task instanceof TCronTask) {
				$this->prepareTask($task);
			}
		}
	}

	/**
	 * @return ?bool whether this process publishes its live status to a status file ({@see getStatuses});
	 *   default null, which the `cron/run` shell command turns on and is off otherwise
	 * @since 4.4.0
	 */
	public function getEnableStatus(): ?bool
	{
		return $this->_enableStatus;
	}

	/**
	 * @param null|bool|string $value whether this process publishes its live status to a status file, or null for the default
	 * @since 4.4.0
	 */
	public function setEnableStatus($value)
	{
		$this->_enableStatus = ($value === null || $value === '') ? null : TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the directory of the status files; default {@see STATUS_DIRECTORY} in the application runtime path
	 * @since 4.4.0
	 */
	public function getStatusPath(): string
	{
		return $this->_statusPath ?? $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . static::STATUS_DIRECTORY;
	}

	/**
	 * @param ?string $path the directory of the status files, or null for the default
	 * @since 4.4.0
	 */
	public function setStatusPath($path)
	{
		$this->_statusPath = ($path === null || $path === '') ? null : TPropertyValue::ensureString($path);
	}

	/**
	 * @return array additional tasks in a list.
	 */
	public function getAdditionalCronTasks()
	{
		return $this->_additionalCronTasks;
	}

	/**
	 * This takes a string that is an array of tasks that has been
	 * through serialize(), or json_encode, or is an xml file of additional tasks.
	 * If one task is set, then it is automatically placed into an array.
	 * @param null|array|string $tasks additional tasks in an array [0..n],  or
	 * a single task.
	 * @throws TInvalidOperationException if the module has been initialized
	 */
	public function setAdditionalCronTasks($tasks)
	{
		$this->assertUninitialized('AdditionalCronTasks');
		if (is_string($tasks)) {
			if (($t = @unserialize($tasks)) !== false) {
				$tasks = $t;
			} elseif (($t = json_decode($tasks, true)) !== null) {
				$tasks = $t;
			} else {
				$xmldoc = Prado::createComponent(TXmlDocument::class, '1.0', 'utf-8');
				$xmldoc->loadFromString($tasks);
				$tasks = $xmldoc;
			}
		}
		if (is_array($tasks) && isset($tasks[self::TASK_KEY])) {
			$tasks = [$tasks];
		}
		if (!is_array($tasks) && !($tasks instanceof TXmlDocument) && $tasks !== null) {
			throw new TInvalidDataTypeException('cron_additional_tasks_invalid', $tasks);
		}
		$this->_additionalCronTasks = $tasks ?? [];
	}

	/**
	 * Delays execution until the given real Unix timestamp through {@see sleep}, so a worker fiber
	 * yields to the event loop while waiting. An overridable seam so that the second-resolution path
	 * can be controlled by subclasses and tests.
	 * @param float $timestamp the Unix timestamp to delay until
	 * @return bool whether the delay succeeded
	 * @since 4.4.0
	 */
	protected function time_sleep_until($timestamp): bool
	{
		$seconds = $timestamp - \microtime(true);
		if ($seconds > 0) {
			$this->sleep($seconds);
		}
		return true;
	}

	/**
	 * Returns a random integer. Wraps the global {@see \mt_rand()} as an overridable seam
	 * so that the request cron probability can be controlled by subclasses and tests.
	 * @return int a random integer between 0 and \mt_getrandmax()
	 * @since 4.4.0
	 */
	protected function mt_rand(): int
	{
		return \mt_rand();
	}

	/**
	 * Returns the PHP server API name. Wraps the global {@see \php_sapi_name()} as an
	 * overridable seam so that request cron activation can be controlled by tests.
	 * @return string the PHP server API name
	 * @since 4.4.0
	 */
	protected function php_sapi_name(): string
	{
		return \php_sapi_name();
	}

	/**
	 * Waits until the given timestamp, returning early when a wake arrives through the
	 * {@see getWakeTransport WakeTransport}, falling back to a plain {@see time_sleep_until()} sleep
	 * without one.
	 * @param float $until the Unix timestamp to wait until
	 * @return bool whether a wake arrived before the timestamp
	 * @since 4.4.0
	 */
	protected function waitForWake($until): bool
	{
		$transport = $this->getWakeTransport();
		if ($transport !== null) {
			$woken = $transport->wait((float) $until, $this->microtime());
			if ($woken !== null) {
				return $woken;
			}
		}
		$this->time_sleep_until($until);
		return false;
	}

	/**
	 * Sends a wake through the {@see getWakeTransport WakeTransport} to a cron process waiting for its
	 * next trigger, per-minute or daemon, so a task scheduled earlier than it expects runs on time. A
	 * missing listener is a harmless no-op.
	 * @return bool whether a wake was sent
	 * @since 4.4.0
	 */
	public function signalWake(): bool
	{
		$transport = $this->getWakeTransport();
		return $transport !== null && $transport->signal($this->microtime());
	}

	/**
	 * Returns the wake transport: the configured transport, the module of the configured ID, or a
	 * {@see TCronWakeSocket} in the application runtime directory.
	 * @throws TConfigurationException when the configured module ID is not an ICronWakeTransport
	 * @return ?ICronWakeTransport the wake transport, or null when no application is available
	 * @since 4.4.0
	 */
	public function getWakeTransport(): ?ICronWakeTransport
	{
		if (is_string($this->_wakeTransport)) {
			$module = $this->getApplication()->getModule($this->_wakeTransport);
			if (!($module instanceof ICronWakeTransport)) {
				throw new TConfigurationException('cron_waketransport_invalid', $this->_wakeTransport);
			}
			$this->_wakeTransport = $module;
		}
		if ($this->_wakeTransport === null) {
			$app = $this->getApplication();
			if ($app === null) {
				return null;
			}
			$this->_wakeTransport = new TCronWakeSocket($app->getRuntimePath());
		}
		return $this->_wakeTransport;
	}

	/**
	 * @param null|ICronWakeTransport|string $transport the wake transport, its module ID, or null for the default socket
	 * @throws TConfigurationException when the value is not a transport, a module ID, or null
	 * @since 4.4.0
	 */
	public function setWakeTransport($transport)
	{
		if ($transport !== null && !is_string($transport) && !($transport instanceof ICronWakeTransport)) {
			throw new TConfigurationException('cron_waketransport_invalid', get_debug_type($transport));
		}
		$this->_wakeTransport = ($transport === '') ? null : $transport;
	}

	/**
	 * @return bool whether `cron/run` in the CLI elects a daemon instead of sweeping once, default false
	 * @since 4.4.0
	 */
	public function getDaemon(): bool
	{
		return $this->_daemon;
	}

	/**
	 * @param mixed $value whether `cron/run` in the CLI elects a daemon instead of sweeping once
	 * @throws TInvalidOperationException if the module has been initialized
	 * @since 4.4.0
	 */
	public function setDaemon($value)
	{
		$this->assertUninitialized('Daemon');
		$this->_daemon = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return float the seconds after which a daemon retires and yields the lease to the next tick's process, default {@see DEFAULT_DAEMON_LIFETIME}; 0 for never
	 * @since 4.4.0
	 */
	public function getDaemonLifetime(): float
	{
		return $this->_daemonLifetime ?? (float) static::DEFAULT_DAEMON_LIFETIME;
	}

	/**
	 * @param float|string $seconds the seconds after which a daemon retires and yields to the next tick; 0 for never
	 * @throws TInvalidDataValueException when the seconds are negative
	 * @since 4.4.0
	 */
	public function setDaemonLifetime($seconds)
	{
		$seconds = TPropertyValue::ensureFloat($seconds);
		if ($seconds < 0) {
			throw new TInvalidDataValueException('cron_daemon_lifetime_invalid', $seconds);
		}
		$this->_daemonLifetime = $seconds;
	}

	/**
	 * @return float the maximum seconds a daemon waits between sweeps, default 60
	 * @since 4.4.0
	 */
	public function getDaemonIdle(): float
	{
		return $this->_daemonIdle ?? (float) static::DEFAULT_DAEMON_IDLE;
	}

	/**
	 * @param float|string $seconds the maximum seconds a daemon waits between sweeps
	 * @throws TInvalidDataValueException when the seconds are not greater than zero
	 * @since 4.4.0
	 */
	public function setDaemonIdle($seconds)
	{
		$seconds = TPropertyValue::ensureFloat($seconds);
		if ($seconds <= 0) {
			throw new TInvalidDataValueException('cron_daemon_idle_invalid', $seconds);
		}
		$this->_daemonIdle = $seconds;
	}

	/**
	 * @return float the seconds a tick's process waits for a retiring daemon to hand over the lease, default {@see DEFAULT_DAEMON_HANDOFF_TIMEOUT}
	 * @since 4.4.0
	 */
	public function getDaemonHandoffTimeout(): float
	{
		return $this->_daemonHandoffTimeout ?? (float) static::DEFAULT_DAEMON_HANDOFF_TIMEOUT;
	}

	/**
	 * @param float|string $seconds the seconds a tick's process waits for a retiring daemon to hand over the lease
	 * @throws TInvalidDataValueException when the seconds are negative
	 * @since 4.4.0
	 */
	public function setDaemonHandoffTimeout($seconds)
	{
		$seconds = TPropertyValue::ensureFloat($seconds);
		if ($seconds < 0) {
			throw new TInvalidDataValueException('cron_daemon_handoff_invalid', $seconds);
		}
		$this->_daemonHandoffTimeout = $seconds;
	}

	/**
	 * @return bool whether the daemon retires, handing the lease to the next tick, when a file of
	 * {@see getDaemonConfigFiles DaemonConfigFiles} changes; default true
	 * @since 4.4.0
	 */
	public function getDaemonWatchConfig(): bool
	{
		return $this->_daemonWatchConfig;
	}

	/**
	 * @param mixed $value whether the daemon retires when a configuration file changes
	 * @since 4.4.0
	 */
	public function setDaemonWatchConfig($value)
	{
		$this->_daemonWatchConfig = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Returns the configuration files the daemon watches: the configured paths, or the application
	 * configuration file.  A deployment that configures cron in an included file names it here.
	 * @return string[] the watched file paths
	 * @since 4.4.0
	 */
	public function getDaemonConfigFiles(): array
	{
		if ($this->_daemonConfigFiles !== null) {
			return $this->_daemonConfigFiles;
		}
		$file = $this->getApplication()?->getConfigurationFile();
		return ($file === null || $file === '') ? [] : [$file];
	}

	/**
	 * @param null|string|string[] $value the configuration files to watch, as paths or namespaces, in an
	 * array or separated by commas; null or '' watches the application configuration file
	 * @since 4.4.0
	 */
	public function setDaemonConfigFiles($value)
	{
		if ($value === null || $value === '' || $value === []) {
			$this->_daemonConfigFiles = null;
			return;
		}
		$files = [];
		foreach (is_array($value) ? $value : explode(',', TPropertyValue::ensureString($value)) as $file) {
			$file = trim((string) $file);
			if ($file !== '') {
				$files[] = Prado::getPathOfNamespace($file) ?? $file;
			}
		}
		$this->_daemonConfigFiles = $files;
	}

	/**
	 * Fingerprints the {@see getDaemonConfigFiles DaemonConfigFiles}: each file's path, modification
	 * time, size, and content hash.  The daemon compares it on each idle with the one it started with.
	 * @return string the SHA-256 fingerprint
	 * @since 4.4.0
	 */
	public function getDaemonConfigFingerprint(): string
	{
		$parts = [];
		foreach ($this->getDaemonConfigFiles() as $file) {
			clearstatcache(true, $file);
			$parts[] = is_file($file)
				? $file . '|' . @filemtime($file) . '|' . @filesize($file) . '|' . @hash_file('sha256', $file)
				: $file . '|missing';
		}
		return hash('sha256', implode("\n", $parts));
	}

	/**
	 * @return ?ICacheDependency the dependency whose change retires the daemon, checked on each idle;
	 * default null
	 * @since 4.4.0
	 */
	public function getDaemonDependency(): ?ICacheDependency
	{
		return $this->_daemonDependency;
	}

	/**
	 * Sets the dependency whose change retires the daemon, handing the lease to the next tick.  The
	 * daemon checks {@see \Prado\Caching\ICacheDependency::getHasChanged()} on each idle; the
	 * dependency reports a change against its own state from when it was created.
	 * @param ?ICacheDependency $dependency the dependency, or null for none
	 * @since 4.4.0
	 */
	public function setDaemonDependency(?ICacheDependency $dependency)
	{
		$this->_daemonDependency = $dependency;
	}

	/**
	 * Returns the mutex of the daemon lease: the configured mutex, the module of the configured ID,
	 * or a {@see \Prado\Util\Mutex\TFileMutex} in the application runtime directory, which excludes
	 * the processes of one host.  A database or cache mutex excludes the processes of every host.
	 * @throws TConfigurationException when the configured module ID is not an IMutex
	 * @return IMutex the mutex of the daemon lease
	 * @since 4.4.0
	 */
	public function getDaemonMutex(): IMutex
	{
		if (is_string($this->_daemonMutex)) {
			$module = $this->getApplication()->getModule($this->_daemonMutex);
			if (!($module instanceof IMutex)) {
				throw new TConfigurationException('cron_daemonmutex_invalid', $this->_daemonMutex);
			}
			$this->_daemonMutex = $module;
		}
		if ($this->_daemonMutex === null) {
			$this->_daemonMutex = Prado::createComponent(TFileMutex::class);
		}
		return $this->_daemonMutex;
	}

	/**
	 * @param null|IMutex|string $mutex the mutex of the daemon lease, its module ID, or null for a file mutex
	 * @throws TConfigurationException when the value is not a mutex, a module ID, or null
	 * @since 4.4.0
	 */
	public function setDaemonMutex($mutex)
	{
		if ($mutex !== null && !is_string($mutex) && !($mutex instanceof IMutex)) {
			throw new TConfigurationException('cron_daemonmutex_invalid', get_debug_type($mutex));
		}
		$this->_daemonMutex = ($mutex === '') ? null : $mutex;
	}

	/**
	 * @return string the lock name of the daemon lease, `cron:daemon:` and the module ID
	 * @since 4.4.0
	 */
	public function getDaemonLockName(): string
	{
		return 'cron:daemon:' . ($this->getID() ?: 'cron');
	}

	/**
	 * @return string the lock name a retiring daemon holds, {@see getDaemonLockName} and `:retiring`
	 * @since 4.4.0
	 */
	public function getDaemonRetiringLockName(): string
	{
		return $this->getDaemonLockName() . ':retiring';
	}

	/**
	 * @return string the lock name a tick's process holds while it waits for the lease, {@see getDaemonLockName} and `:handoff`
	 * @since 4.4.0
	 */
	public function getDaemonHandoffLockName(): string
	{
		return $this->getDaemonLockName() . ':handoff';
	}

	/**
	 * Runs as the cron daemon.  The process acquires the {@see getDaemonMutex DaemonMutex} lease
	 * without waiting; when another process holds it, {@see takeOverDaemon} either exits at once (the
	 * daemon is healthy) or waits for a retiring daemon to hand the lease over.  As the daemon it sweeps
	 * the pending tasks, waits on the event loop until the next trigger, a wake from the
	 * {@see getWakeTransport WakeTransport}, or {@see getDaemonIdle DaemonIdle}, and repeats.  After
	 * {@see getDaemonLifetime DaemonLifetime} it retires: it holds the retiring lock and keeps serving
	 * until a tick's process waits on the handoff lock, then releases the lease to it, so the module is
	 * never without a daemon.  {@see stopDaemon}, SIGTERM, and SIGINT end it at once.  Tasks run through
	 * {@see runTasks} by their Mode; after releasing the lease, the process waits for its Process tasks.
	 * @return int the number of tasks the daemon ran
	 * @since 4.4.0
	 */
	public function runDaemon(): int
	{
		$mutex = $this->getDaemonMutex();
		$lockName = $this->getDaemonLockName();
		if (!$mutex->acquire($lockName) && !$this->takeOverDaemon($mutex)) {
			return 0;
		}
		$loop = TAsync::getLoop();
		$start = $this->getMonotonicClock()->seconds();
		$numtasks = 0;
		$this->_daemonStop = false;
		$this->_daemonWake = false;
		$this->_daemonRetiring = false;
		$this->_daemonRetireRequested = false;
		$this->_daemonFingerprint = $this->getDaemonWatchConfig() ? $this->getDaemonConfigFingerprint() : null;
		$transport = $this->getWakeTransport();
		$watching = $transport !== null && $transport->watch($loop, function () {
			$this->wakeDaemon();
		});
		$signals = $this->watchDaemonSignals($loop);
		$this->_daemonReason = null;
		$this->logCronDaemonStart($lockName, $watching);
		$this->beginStatus(static::STATUS_ROLE_DAEMON);
		$this->updateDaemonStatus([
			'lock' => $lockName,
			'state' => static::DAEMON_STATE_RUNNING,
			'transport' => ($transport !== null) ? $transport::class : null,
			'watching' => $watching,
			'lifetime' => $this->getDaemonLifetime(),
			'idle' => $this->getDaemonIdle(),
			'sweeps' => 0,
			'tasks' => 0,
			'wake' => null,
			'next' => [],
		]);
		$sweeps = 0;
		$param = $this->newDaemonEventParameter($lockName, $watching);
		$this->onDaemonStart($param);
		try {
			while (true) {
				$this->consumeDaemonWake();
				if ($this->isDaemonDone($start)) {
					break;
				}
				$swept = $this->sweepTasks();
				$numtasks += $swept;
				$sweeps++;
				if ($this->consumeDaemonWake()) {
					continue; // a wake arrived during the sweep: sweep again
				}
				$this->checkDaemonConfig();
				$param = $this->newDaemonEventParameter($lockName, $watching)
					->setTasks($swept)
					->setElapsed($this->getMonotonicClock()->seconds() - $start)
					->setWait($this->getDaemonWait($start));
				$this->onDaemonIdle($param);
				if ($this->isDaemonDone($start)) {
					break;
				}
				$wait = max($param->getWait(), static::DAEMON_MIN_WAIT);
				$this->updateDaemonStatus([
					'state' => $this->_daemonRetiring ? static::DAEMON_STATE_RETIRING : static::DAEMON_STATE_RUNNING,
					'sweeps' => $sweeps,
					'tasks' => $numtasks,
					'wake' => $this->microtime() + $wait,
					'next' => $this->getNextTriggers(5),
				]);
				$this->waitDaemon($loop, $wait);
			}
		} catch (\Throwable $e) {
			$this->_daemonReason = TCronDaemonEventParameter::REASON_ERROR;
			throw $e;
		} finally {
			foreach ($signals as $id) {
				$loop->cancel($id);
			}
			if ($transport !== null) {
				$transport->close();
			}
			if ($this->_daemonRetiring) {
				$mutex->release($this->getDaemonRetiringLockName());
				$this->_daemonRetiring = false;
			}
			$mutex->release($lockName);
			$this->updateDaemonStatus(['state' => static::STATUS_DAEMON_ENDING, 'wake' => null]);
			$this->awaitProcessRuns();
			$this->endStatus();
			$this->logCronDaemonEnd($numtasks);
			$param = $this->newDaemonEventParameter($lockName, $watching)
				->setTasks($numtasks)
				->setElapsed($this->getMonotonicClock()->seconds() - $start)
				->setReason($this->_daemonReason ?? TCronDaemonEventParameter::REASON_LIFETIME);
			$this->onDaemonEnd($param);
		}
		return $numtasks;
	}

	/**
	 * Creates the parameter of a daemon event.
	 * @param string $lockName the lease lock name
	 * @param bool $watching whether the wake transport is watched
	 * @return TCronDaemonEventParameter the parameter
	 * @since 4.4.0
	 */
	protected function newDaemonEventParameter(string $lockName, bool $watching): TCronDaemonEventParameter
	{
		return (new TCronDaemonEventParameter())->setLockName($lockName)->setWatching($watching);
	}

	/**
	 * Tells whether the daemon loop ends: a stop or signal was requested, or the daemon is retiring and
	 * a tick's process waits on the handoff lock for the lease.  A daemon whose lifetime passed, or that
	 * {@see retireDaemon} or a configuration change asked to retire, starts retiring here by taking the
	 * retiring lock.
	 * @param float $start the monotonic seconds the daemon started at
	 * @return bool whether the daemon is done
	 * @since 4.4.0
	 */
	protected function isDaemonDone(float $start): bool
	{
		$this->checkDaemonRequests();
		if ($this->_daemonStop) {
			return true;
		}
		if (!$this->_daemonRetiring && ($this->_daemonRetireRequested || $this->isDaemonExpired($start))) {
			$this->_daemonRetiring = $this->getDaemonMutex()->acquire($this->getDaemonRetiringLockName());
			if ($this->_daemonRetiring) {
				Prado::log('Cron daemon (' . $this->getDaemonLockName() . ') is retiring; it serves until the next tick takes the lease', TLogger::INFO, TCronModule::class);
			}
		}
		if ($this->_daemonRetiring && $this->isDaemonHandoffRequested()) {
			$this->_daemonReason ??= TCronDaemonEventParameter::REASON_HANDOFF;
			return true;
		}
		return false;
	}

	/**
	 * Tells whether a tick's process holds the handoff lock, waiting for the lease.
	 * @return bool whether a successor waits
	 * @since 4.4.0
	 */
	protected function isDaemonHandoffRequested(): bool
	{
		$mutex = $this->getDaemonMutex();
		$handoff = $this->getDaemonHandoffLockName();
		if ($mutex->acquire($handoff)) {
			$mutex->release($handoff);
			return false;
		}
		return true;
	}

	/**
	 * Takes the lease over from a retiring daemon.  A healthy daemon, one not holding the retiring
	 * lock, is left alone and this returns false at once.  Otherwise this process holds the handoff
	 * lock, wakes the daemon, and waits up to {@see getDaemonHandoffTimeout DaemonHandoffTimeout} for
	 * the lease; the retiring daemon sees the handoff lock held and releases the lease to it.
	 * @param IMutex $mutex the daemon mutex
	 * @return bool whether this process now holds the lease
	 * @since 4.4.0
	 */
	protected function takeOverDaemon(IMutex $mutex): bool
	{
		$lockName = $this->getDaemonLockName();
		$retiring = $this->getDaemonRetiringLockName();
		if ($mutex->acquire($retiring)) {
			$mutex->release($retiring);
			Prado::log('Cron daemon lease (' . $lockName . ') is held by another process', TLogger::INFO, TCronModule::class);
			return false;
		}
		$handoff = $this->getDaemonHandoffLockName();
		if (!$mutex->acquire($handoff)) {
			Prado::log('Cron daemon lease (' . $lockName . ') is retiring and another process already waits for it', TLogger::INFO, TCronModule::class);
			return false;
		}
		try {
			$this->signalWake();
			if (!$mutex->acquire($lockName, $this->getDaemonHandoffTimeout())) {
				Prado::log('Cron daemon lease (' . $lockName . ') was not handed over within the handoff timeout', TLogger::INFO, TCronModule::class);
				return false;
			}
		} finally {
			$mutex->release($handoff);
		}
		return true;
	}

	/**
	 * Raises the `OnDaemonStart` event, and `fxCronDaemonStart` globally, after the lease is acquired, before the first sweep.
	 * @param TCronDaemonEventParameter $param the lease lock name and whether the wake transport is watched
	 * @since 4.4.0
	 */
	public function onDaemonStart($param)
	{
		$this->raiseEvent('OnDaemonStart', $this, $param);
		$this->raiseEvent('fxCronDaemonStart', $this, $param);
	}

	/**
	 * Raises the `OnDaemonIdle` event, and `fxCronDaemonIdle` globally, before each wait.  The parameter carries the tasks of the sweep just
	 * finished, the elapsed seconds, and the seconds about to be waited, which a handler changes with
	 * {@see TCronDaemonEventParameter::setWait}.  A subclass releases resources an idle process should not
	 * hold here, such as a database connection, and calls the parent to raise the event.
	 * @param TCronDaemonEventParameter $param the idle parameter
	 * @since 4.4.0
	 */
	public function onDaemonIdle($param)
	{
		$this->raiseEvent('OnDaemonIdle', $this, $param);
		$this->raiseEvent('fxCronDaemonIdle', $this, $param);
	}

	/**
	 * Raises the `OnDaemonEnd` event, and `fxCronDaemonEnd` globally, after the lease is released.  The parameter carries the tasks the
	 * daemon ran, the elapsed seconds, and the {@see TCronDaemonEventParameter::getReason Reason}.
	 * @param TCronDaemonEventParameter $param the end parameter
	 * @since 4.4.0
	 */
	public function onDaemonEnd($param)
	{
		$this->raiseEvent('OnDaemonEnd', $this, $param);
		$this->raiseEvent('fxCronDaemonEnd', $this, $param);
	}

	/**
	 * Stops the running daemon after its current sweep, and ends its current wait.
	 * @since 4.4.0
	 */
	public function stopDaemon(): void
	{
		$this->_daemonReason ??= TCronDaemonEventParameter::REASON_STOP;
		$this->_daemonStop = true;
		$this->wakeDaemon();
	}

	/**
	 * Handles the global `fxRetireCronDaemon` event: a component in the daemon's process raises it to
	 * {@see retireDaemon retire} the daemon without a reference to this module.
	 * @param mixed $sender the raising component
	 * @param mixed $param the event parameter
	 * @since 4.4.0
	 */
	public function fxRetireCronDaemon($sender, $param)
	{
		$this->retireDaemon();
	}

	/**
	 * Handles the global `fxStopCronDaemon` event: a component in the daemon's process raises it to
	 * {@see stopDaemon stop} the daemon without a reference to this module.
	 * @param mixed $sender the raising component
	 * @param mixed $param the event parameter
	 * @since 4.4.0
	 */
	public function fxStopCronDaemon($sender, $param)
	{
		$this->stopDaemon();
	}

	/**
	 * @return string the lock name a process holds while it asks the daemon to retire, {@see getDaemonLockName} and `:retire`
	 * @since 4.4.0
	 */
	public function getDaemonRetireLockName(): string
	{
		return $this->getDaemonLockName() . ':retire';
	}

	/**
	 * @return string the lock name a process holds while it asks the daemon to stop, {@see getDaemonLockName} and `:stop`
	 * @since 4.4.0
	 */
	public function getDaemonStopLockName(): string
	{
		return $this->getDaemonLockName() . ':stop';
	}

	/**
	 * Tells whether another holder has a lock of the {@see getDaemonMutex DaemonMutex}.  A free lock is
	 * taken and released at once.
	 * @param string $name the lock name
	 * @return bool whether the lock is held
	 * @since 4.4.0
	 */
	protected function isDaemonLockHeld(string $name): bool
	{
		$mutex = $this->getDaemonMutex();
		if ($mutex->acquire($name)) {
			$mutex->release($name);
			return false;
		}
		return true;
	}

	/**
	 * Returns the state of the daemon of this module's lease, from any process.
	 * @return ?string {@see DAEMON_STATE_RUNNING}, {@see DAEMON_STATE_RETIRING}, or null when no daemon holds the lease
	 * @since 4.4.0
	 */
	public function getDaemonState(): ?string
	{
		if (!$this->isDaemonLockHeld($this->getDaemonLockName())) {
			return null;
		}
		return $this->isDaemonLockHeld($this->getDaemonRetiringLockName()) ? static::DAEMON_STATE_RETIRING : static::DAEMON_STATE_RUNNING;
	}

	/**
	 * Asks the running daemon, from any process, to retire: it holds the retire lock and wakes the
	 * daemon, which starts retiring at its next turn, and waits for the retiring lock to appear.
	 * @param ?float $timeout the seconds to wait for the acknowledgment, null for {@see DEFAULT_DAEMON_REQUEST_TIMEOUT}
	 * @return ?bool true when the daemon retires (or already does), false when it did not acknowledge in time, null when no daemon runs
	 * @since 4.4.0
	 */
	public function requestDaemonRetire(?float $timeout = null): ?bool
	{
		return $this->requestDaemon($this->getDaemonRetireLockName(), static fn (?string $state) => $state !== static::DAEMON_STATE_RUNNING, $timeout);
	}

	/**
	 * Asks the running daemon, from any process, to stop at once: it holds the stop lock and wakes the
	 * daemon, which ends at its next turn, and waits for the lease to be released.  The next tick starts
	 * a new daemon.
	 * @param ?float $timeout the seconds to wait for the acknowledgment, null for {@see DEFAULT_DAEMON_REQUEST_TIMEOUT}
	 * @return ?bool true when the daemon stopped, false when it did not within the timeout, null when no daemon runs
	 * @since 4.4.0
	 */
	public function requestDaemonStop(?float $timeout = null): ?bool
	{
		return $this->requestDaemon($this->getDaemonStopLockName(), static fn (?string $state) => $state === null, $timeout);
	}

	/**
	 * Holds a request lock, wakes the daemon, and waits until {@see getDaemonState} satisfies the
	 * acknowledgment or the timeout passes.
	 * @param string $lockName the request lock
	 * @param callable $acknowledged `(?string $state): bool`, whether the state is the requested one
	 * @param ?float $timeout the seconds to wait, null for {@see DEFAULT_DAEMON_REQUEST_TIMEOUT}
	 * @return ?bool true when acknowledged, false when not in time, null when no daemon runs
	 * @since 4.4.0
	 */
	protected function requestDaemon(string $lockName, callable $acknowledged, ?float $timeout): ?bool
	{
		$state = $this->getDaemonState();
		if ($state === null) {
			return null;
		}
		if ($acknowledged($state)) {
			return true;
		}
		$mutex = $this->getDaemonMutex();
		if (!$mutex->acquire($lockName)) {
			return false;
		}
		try {
			$this->signalWake();
			$clock = $this->getMonotonicClock();
			$deadline = $clock->seconds() + ($timeout ?? (float) static::DEFAULT_DAEMON_REQUEST_TIMEOUT);
			while (true) {
				if ($acknowledged($this->getDaemonState())) {
					return true;
				}
				$remaining = $deadline - $clock->seconds();
				if ($remaining <= 0) {
					return false;
				}
				TAsync::sleep(min($remaining, static::DAEMON_MIN_WAIT));
			}
		} finally {
			$mutex->release($lockName);
		}
	}

	/**
	 * Applies the requests of other processes: a held stop lock stops the daemon, a held retire lock
	 * retires it.
	 * @since 4.4.0
	 */
	protected function checkDaemonRequests(): void
	{
		if (!$this->_daemonStop && $this->isDaemonLockHeld($this->getDaemonStopLockName())) {
			Prado::log('Cron daemon (' . $this->getDaemonLockName() . ') stops on request', TLogger::INFO, TCronModule::class);
			$this->_daemonReason ??= TCronDaemonEventParameter::REASON_STOP;
			$this->_daemonStop = true;
		} elseif (!$this->_daemonRetiring && !$this->_daemonRetireRequested && $this->isDaemonLockHeld($this->getDaemonRetireLockName())) {
			Prado::log('Cron daemon (' . $this->getDaemonLockName() . ') retires on request', TLogger::INFO, TCronModule::class);
			$this->_daemonRetireRequested = true;
		}
	}

	/**
	 * Retires the running daemon before its lifetime: it keeps serving and hands the lease to the next
	 * tick's process without a gap, as after {@see getDaemonLifetime DaemonLifetime}.  A deployment hook
	 * or an `onDaemonIdle` handler calls this to restart the daemon on new code or configuration.
	 * @since 4.4.0
	 */
	public function retireDaemon(): void
	{
		$this->_daemonRetireRequested = true;
		$this->wakeDaemon();
	}

	/**
	 * Retires the daemon when a watched configuration file changed since it started, or the
	 * {@see getDaemonDependency DaemonDependency} reports a change.
	 * @since 4.4.0
	 */
	protected function checkDaemonConfig(): void
	{
		if ($this->_daemonRetiring || $this->_daemonRetireRequested) {
			return;
		}
		if ($this->_daemonFingerprint !== null && $this->_daemonFingerprint !== $this->getDaemonConfigFingerprint()) {
			Prado::log('Cron daemon (' . $this->getDaemonLockName() . ') configuration changed; retiring for the next tick', TLogger::INFO, TCronModule::class);
			$this->_daemonRetireRequested = true;
		} elseif ($this->getDaemonDependency()?->getHasChanged()) {
			Prado::log('Cron daemon (' . $this->getDaemonLockName() . ') dependency changed; retiring for the next tick', TLogger::INFO, TCronModule::class);
			$this->_daemonRetireRequested = true;
		}
	}

	/**
	 * Runs the pending tasks once, without a minute limit: a daemon sweep.
	 * @return int the number of tasks that were pending
	 * @since 4.4.0
	 */
	protected function sweepTasks(): int
	{
		$this->filterStaleTasks();
		$pendingTasks = $this->getPendingTasks();
		if ($pendingTasks === null) {
			return 0;
		}
		$sweep = new TCronSweepEventParameter($pendingTasks, true);
		$this->onSweepStart($sweep);
		$pendingTasks = $sweep->getTasks();
		$numtasks = count($pendingTasks);
		$results = [];
		if ($numtasks) {
			$this->logCron($numtasks);
			$results = $this->runTasks($pendingTasks);
		} else {
			$this->setLastCronTime($this->microtime());
		}
		$this->onSweepEnd($sweep->setResults($results));
		return $numtasks;
	}

	/**
	 * Computes the seconds the daemon waits before its next sweep: until the next trigger
	 * ({@see getNextPendingTime}), at most {@see getDaemonIdle DaemonIdle} and the remaining
	 * {@see getDaemonLifetime DaemonLifetime} before retiring; {@see onDaemonIdle} may change it, and {@see DAEMON_MIN_WAIT} is the floor.
	 * @param float $start the monotonic seconds the daemon started at
	 * @return float the seconds to wait
	 * @since 4.4.0
	 */
	protected function getDaemonWait(float $start): float
	{
		$wait = $this->getDaemonIdle();
		$next = $this->getNextPendingTime();
		if ($next !== null) {
			$wait = min($wait, max(0.0, $next - $this->microtime()));
		}
		$lifetime = $this->getDaemonLifetime();
		if ($lifetime > 0 && !$this->_daemonRetiring) {
			$wait = min($wait, max(0.0, $lifetime - ($this->getMonotonicClock()->seconds() - $start)));
		}
		return max($wait, static::DAEMON_MIN_WAIT);
	}

	/**
	 * Waits on the event loop for the given seconds or until {@see wakeDaemon} resumes the wait.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param float $seconds the seconds to wait
	 * @since 4.4.0
	 */
	protected function waitDaemon(IEventLoop $loop, float $seconds): void
	{
		$suspension = $loop->suspension();
		$resumed = false;
		$this->_daemonResume = static function () use ($suspension, &$resumed) {
			if (!$resumed) {
				$resumed = true;
				$suspension->resume();
			}
		};
		$timer = $loop->delay($seconds, $this->_daemonResume);
		try {
			$suspension->suspend();
		} finally {
			$resumed = true;
			$this->_daemonResume = null;
			$loop->cancel($timer);
		}
	}

	/**
	 * Returns whether a wake arrived since the last call, and clears it.  A wake that arrives during
	 * a sweep makes the daemon sweep again instead of waiting.
	 * @return bool whether a wake arrived
	 * @since 4.4.0
	 */
	protected function consumeDaemonWake(): bool
	{
		$wake = $this->_daemonWake;
		$this->_daemonWake = false;
		return $wake;
	}

	/**
	 * Records a wake and ends the daemon's current wait, so the next sweep runs at once.
	 * @since 4.4.0
	 */
	protected function wakeDaemon(): void
	{
		$this->_daemonWake = true;
		if ($this->_daemonResume !== null) {
			($this->_daemonResume)();
		}
	}

	/**
	 * @param float $start the monotonic seconds the daemon started at
	 * @return bool whether the {@see getDaemonLifetime DaemonLifetime} has passed
	 * @since 4.4.0
	 */
	protected function isDaemonExpired(float $start): bool
	{
		$lifetime = $this->getDaemonLifetime();
		return $lifetime > 0 && ($this->getMonotonicClock()->seconds() - $start) >= $lifetime;
	}

	/**
	 * Watches SIGTERM and SIGINT on the loop to stop the daemon, where the platform has signals.
	 * @param IEventLoop $loop the daemon's event loop
	 * @return string[] the loop callback identifiers
	 * @since 4.4.0
	 */
	protected function watchDaemonSignals(IEventLoop $loop): array
	{
		$ids = [];
		if (!\extension_loaded('pcntl')) {
			return $ids;
		}
		foreach ([15, 2] as $signal) { // SIGTERM, SIGINT
			try {
				$ids[] = $loop->onSignal($signal, function () {
					$this->_daemonReason ??= TCronDaemonEventParameter::REASON_SIGNAL;
					$this->stopDaemon();
				});
			} catch (TNotSupportedException $e) {
				break;
			}
		}
		return $ids;
	}

	/**
	 * Returns the state of the module for monitoring: its cron processes and the tasks the current user
	 * may read, read from the stores so another process's runs show.
	 *
	 * | Key | Content |
	 * |---|---|
	 * | `module`, `time` | the module ID and the report time |
	 * | `daemon` | the daemon's status, or null when no daemon runs |
	 * | `processes` | the live statuses of {@see getStatuses}, without their file |
	 * | `tasks` | by name: `mode`, `schedule`, `timezone`, `priority`, `description`, `paused`, `running` (the process IDs of its runs), `next`, `last`, `count`, `result`, `duration` |
	 * @return array the report
	 * @since 4.4.0
	 */
	public function getMonitorReport(): array
	{
		$processes = [];
		$daemon = null;
		foreach ($this->getStatuses() as $status) {
			if (!$status['alive']) {
				continue;
			}
			unset($status['file']);
			$processes[] = $status;
			if (($status['role'] ?? null) === static::STATUS_ROLE_DAEMON) {
				$daemon ??= $status;
			}
		}
		$running = [];
		foreach ($processes as $status) {
			foreach ($status['running'] as $run) {
				$running[$run['name'] ?? ''][] = (int) ($run['pid'] ?? 0);
			}
		}
		$paused = array_flip($this->getPausedTasks());
		$results = $this->getTaskResults(true);
		$next = $this->getNextTriggers(PHP_INT_MAX, true);
		$tasks = [];
		foreach ($this->getTasks() ?? [] as $name => $task) {
			$result = $results[$name] ?? [];
			$tasks[$name] = [
				'mode' => $task->getMode(),
				'schedule' => $task->getSchedule(),
				'timezone' => $task->getScheduleTimeZone() ?? date_default_timezone_get(),
				'priority' => $task->getPriority(),
				'description' => $task->getDescription(),
				'paused' => isset($paused[$name]),
				'running' => $running[$name] ?? [],
				'next' => $next[$name] ?? null,
				'last' => $result['time'] ?? null,
				'count' => $result['count'] ?? 0,
				'result' => $result['result'] ?? null,
				'duration' => $result['duration'] ?? null,
			];
		}
		return ['module' => $this->getID(), 'time' => $this->microtime(), 'daemon' => $daemon, 'processes' => $processes, 'tasks' => $tasks];
	}

	/**
	 * Returns {@see getMonitorReport} in the Prometheus text exposition format:
	 * `prado_cron_daemon_up`, `prado_cron_processes`, and per task `prado_cron_task_running`,
	 * `prado_cron_task_paused`, `prado_cron_task_runs_total`, `prado_cron_task_last_run_timestamp_seconds`,
	 * `prado_cron_task_last_duration_seconds`, `prado_cron_task_last_success`, and
	 * `prado_cron_task_next_run_timestamp_seconds`, labeled by `module` and `task`.
	 * @return string the metrics
	 * @since 4.4.0
	 */
	public function getMonitorMetrics(): string
	{
		$report = $this->getMonitorReport();
		$label = static fn (array $labels): string => '{' . implode(',', array_map(static fn ($k, $v) => $k . '="' . addcslashes((string) $v, "\\\"\n") . '"', array_keys($labels), $labels)) . '}';
		$module = ['module' => (string) $report['module']];
		$lines = [];
		$metric = static function (string $name, string $type, string $help, array $samples) use (&$lines): void {
			$lines[] = '# HELP ' . $name . ' ' . $help;
			$lines[] = '# TYPE ' . $name . ' ' . $type;
			foreach ($samples as [$labels, $value]) {
				$lines[] = $name . $labels . ' ' . $value;
			}
		};
		$metric('prado_cron_daemon_up', 'gauge', 'Whether a cron daemon runs.', [[$label($module), ($report['daemon'] !== null) ? 1 : 0]]);
		$metric('prado_cron_processes', 'gauge', 'The cron processes that publish their status.', [[$label($module), count($report['processes'])]]);
		$per = static function (callable $value) use ($report, $module, $label): array {
			$samples = [];
			foreach ($report['tasks'] as $name => $task) {
				if (($v = $value($task)) !== null) {
					$samples[] = [$label($module + ['task' => $name]), $v];
				}
			}
			return $samples;
		};
		$metric('prado_cron_task_running', 'gauge', 'The runs of the task in progress.', $per(static fn ($t) => count($t['running'])));
		$metric('prado_cron_task_paused', 'gauge', 'Whether the task is paused.', $per(static fn ($t) => $t['paused'] ? 1 : 0));
		$metric('prado_cron_task_runs_total', 'counter', 'The runs of the task.', $per(static fn ($t) => $t['count']));
		$metric('prado_cron_task_last_run_timestamp_seconds', 'gauge', 'The start of the last run of the task.', $per(static fn ($t) => $t['last']));
		$metric('prado_cron_task_last_duration_seconds', 'gauge', 'The seconds the last finished run of the task took.', $per(static fn ($t) => $t['duration']));
		$metric('prado_cron_task_last_success', 'gauge', 'Whether the last finished run of the task succeeded.', $per(static fn ($t) => ($t['result'] === null) ? null : (($t['result'] === static::RESULT_OK) ? 1 : 0)));
		$metric('prado_cron_task_next_run_timestamp_seconds', 'gauge', 'The next trigger of the task.', $per(static fn ($t) => $t['next']));
		return implode("\n", $lines) . "\n";
	}

	/**
	 * Starts publishing the live status of this process with {@see getEnableStatus EnableStatus}, and
	 * removes the status files of this host's ended processes.  A process already publishing keeps its status.
	 * @param string $role {@see STATUS_ROLE_DAEMON} or {@see STATUS_ROLE_SWEEP}
	 * @return bool whether this call started the status
	 * @since 4.4.0
	 */
	protected function beginStatus(string $role): bool
	{
		if (!$this->getEnableStatus() || $this->_status !== null) {
			return false;
		}
		foreach ($this->getStatuses() as $status) {
			if (!$status['alive'] && $status['host'] === (string) gethostname()) {
				@unlink($status['file']);
			}
		}
		$now = $this->microtime();
		$this->_status = [
			'module' => $this->getID(),
			'class' => static::class,
			'role' => $role,
			'pid' => (int) getmypid(),
			'host' => (string) gethostname(),
			'php' => PHP_VERSION,
			'started' => $now,
			'updated' => $now,
			'concurrency' => $this->getConcurrency(),
			'daemon' => null,
			'running' => [],
			'recent' => [],
		];
		$this->writeStatus();
		return true;
	}

	/**
	 * Stops publishing the live status of this process and removes its status file.
	 * @since 4.4.0
	 */
	protected function endStatus(): void
	{
		if ($this->_status === null) {
			return;
		}
		$file = $this->getStatusFile();
		$this->_status = null;
		if (is_file($file)) {
			@unlink($file);
		}
	}

	/**
	 * Merges values into the daemon part of the live status and writes it.
	 * @param array $values the daemon values
	 * @since 4.4.0
	 */
	protected function updateDaemonStatus(array $values): void
	{
		if ($this->_status === null) {
			return;
		}
		$this->_status['daemon'] = array_merge($this->_status['daemon'] ?? [], $values);
		$this->writeStatus();
	}

	/**
	 * Adds a starting run to the live status.
	 * @param TCronTask $task the task
	 * @param string $username the user name the task runs as
	 * @return ?int the run ID, or null without a live status
	 * @since 4.4.0
	 */
	protected function beginRunStatus($task, string $username): ?int
	{
		if ($this->_status === null) {
			return null;
		}
		$id = ++$this->_statusRunId;
		$now = $this->microtime();
		$timeout = $task->getTimeout() ?? $this->getTaskTimeout();
		$this->_status['running'][$id] = [
			'name' => $task->getName(),
			'mode' => $task->getMode(),
			'user' => $username,
			'started' => $now,
			'deadline' => ($timeout === null) ? null : $now + $timeout,
			'warn' => (($warn = $task->getWarnDuration()) === null) ? null : $now + $warn,
			'pid' => $this->_status['pid'],
		];
		$this->writeStatus();
		return $id;
	}

	/**
	 * Sets the process ID of a run in the live status: the child process of a Process task.
	 * @param ?int $id the run ID
	 * @param ?int $pid the process ID
	 * @since 4.4.0
	 */
	protected function setRunStatusPid(?int $id, ?int $pid): void
	{
		if ($id === null || !isset($this->_status['running'][$id])) {
			return;
		}
		$this->_status['running'][$id]['pid'] = $pid;
		$this->writeStatus();
	}

	/**
	 * Moves a finished run from the running runs of the live status to its recent runs.
	 * @param ?int $id the run ID
	 * @param string $result the run's result
	 * @param float $duration the seconds the run took
	 * @since 4.4.0
	 */
	protected function endRunStatus(?int $id, string $result, float $duration): void
	{
		if ($id === null || !isset($this->_status['running'][$id])) {
			return;
		}
		$run = $this->_status['running'][$id];
		unset($this->_status['running'][$id]);
		$run['result'] = $result;
		$run['duration'] = round($duration, 6);
		unset($run['deadline'], $run['user'], $run['warn']);
		array_unshift($this->_status['recent'], $run);
		$this->_status['recent'] = array_slice($this->_status['recent'], 0, static::STATUS_RECENT);
		$this->writeStatus();
	}

	/**
	 * Writes the live status to the status file, through a temporary file renamed over it.  A failure is
	 * logged and does not stop cron.
	 * @since 4.4.0
	 */
	protected function writeStatus(): void
	{
		if ($this->_status === null) {
			return;
		}
		$this->_status['updated'] = $this->microtime();
		$file = $this->getStatusFile();
		$dir = dirname($file);
		if (!is_dir($dir) && !@mkdir($dir, 0o777, true) && !is_dir($dir)) {
			Prado::log('Cron status directory (' . $dir . ') could not be created', TLogger::WARNING, TCronModule::class);
			return;
		}
		$tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
		$json = json_encode($this->_status, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
		if (@file_put_contents($tmp, (string) $json) === false || !@rename($tmp, $file)) {
			if (is_file($tmp)) {
				@unlink($tmp);
			}
			Prado::log('Cron status file (' . $file . ') could not be written', TLogger::WARNING, TCronModule::class);
		}
	}

	/**
	 * @return string the status file of this process: the module ID, host, and process ID in {@see getStatusPath}
	 * @since 4.4.0
	 */
	protected function getStatusFile(): string
	{
		$name = $this->getID() . '.' . ($this->_status['host'] ?? gethostname()) . '.' . ($this->_status['pid'] ?? getmypid());
		return $this->getStatusPath() . DIRECTORY_SEPARATOR . preg_replace('/[^\w.-]+/', '_', $name) . '.json';
	}

	/**
	 * Returns the live status of each cron process of this module that publishes one: a daemon, or a
	 * `cron/run` sweep.  Each status is an array:
	 *  - `role`, `pid`, `host`, `php`, `started`, `updated`, `concurrency`: the process.
	 *  - `daemon`: null for a sweep; else `state`, `lock`, `transport`, `watching`, `lifetime`, `idle`,
	 *    `sweeps`, `tasks`, `wake` (the time of the next sweep), and `next` (the next triggers by task name).
	 *  - `running`: the running runs, each with `name`, `mode`, `user`, `started`, `deadline`, and `pid` (the child process of a Process task).
	 *  - `recent`: the finished runs, newest first, each with `name`, `mode`, `started`, `pid`, `result`, and `duration`.
	 *  - `alive`: whether the process runs: a process of this host is checked by its ID, another host's by
	 *    its last update within {@see STATUS_STALE} seconds.
	 *  - `file`: the status file.
	 * @return array[] the statuses, the daemon first, then by start time
	 * @since 4.4.0
	 */
	public function getStatuses(): array
	{
		$statuses = [];
		$host = (string) gethostname();
		$now = $this->microtime();
		foreach (glob($this->getStatusPath() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
			$status = json_decode((string) @file_get_contents($file), true);
			if (!is_array($status) || ($status['module'] ?? null) !== $this->getID()) {
				continue;
			}
			$status['host'] = (string) ($status['host'] ?? '');
			$status['running'] = array_values((array) ($status['running'] ?? []));
			$status['recent'] = array_values((array) ($status['recent'] ?? []));
			$status['alive'] = $this->isStatusAlive($status, $host, $now);
			$status['file'] = $file;
			$statuses[] = $status;
		}
		usort($statuses, static fn (array $a, array $b): int => [($a['role'] ?? '') !== static::STATUS_ROLE_DAEMON, $a['started'] ?? 0] <=> [($b['role'] ?? '') !== static::STATUS_ROLE_DAEMON, $b['started'] ?? 0]);
		return $statuses;
	}

	/**
	 * Tells whether the process of a status runs.
	 * @param array $status the status
	 * @param string $host this host's name
	 * @param float $now the current time
	 * @return bool whether the process runs
	 * @since 4.4.0
	 */
	protected function isStatusAlive(array $status, string $host, float $now): bool
	{
		$pid = (int) ($status['pid'] ?? 0);
		if ($status['host'] === $host) {
			return $pid > 0 && $this->isProcessRunning($pid);
		}
		$expected = max((float) ($status['updated'] ?? 0), (float) ($status['daemon']['wake'] ?? 0));
		return $now - $expected <= static::STATUS_STALE;
	}

	/**
	 * Tells whether a process of this host runs.  A process of another user, which cannot be
	 * signaled (`EPERM`), runs.
	 * @param int $pid the process ID
	 * @return bool whether the process runs
	 * @since 4.4.0
	 */
	protected function isProcessRunning(int $pid): bool
	{
		if (TProcessHelper::isRunning($pid)) {
			return true;
		}
		return !TProcessHelper::isSystemWindows() && function_exists('posix_get_last_error') && posix_get_last_error() === 1;
	}

	/**
	 * Logs the daemon's start and raises `dyLogCronDaemonStart`.
	 * @param string $lockName the lease lock name
	 * @param bool $watching whether the wake transport is watched
	 * @since 4.4.0
	 */
	protected function logCronDaemonStart(string $lockName, bool $watching)
	{
		Prado::log('Cron daemon started (' . $lockName . ($watching ? ', watching wakes' : '') . ')', TLogger::INFO, TCronModule::class);
		$this->dyLogCronDaemonStart($lockName, $watching);
	}

	/**
	 * Logs the daemon's end and raises `dyLogCronDaemonEnd`.
	 * @param int $numtasks the number of tasks the daemon ran
	 * @since 4.4.0
	 */
	protected function logCronDaemonEnd(int $numtasks)
	{
		Prado::log('Cron daemon ended after ' . $numtasks . ' tasks', TLogger::INFO, TCronModule::class);
		$this->dyLogCronDaemonEnd($numtasks);
	}
}
