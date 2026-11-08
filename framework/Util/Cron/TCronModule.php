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
 * <module id="cron" class="Prado\Util\Cron\TCronModule" DefaultUserName="admin">
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
 *             ],
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
 * user is unchanged.  {@see setConcurrency Concurrency} is the number of tasks that
 * run at once (default 1, the tasks in order), so a task that waits on the event
 * loop lets the next one start.  An exception from a task is logged
 * ({@see logCronTaskError}) and the sweep continues.  {@see setTaskTimeout TaskTimeout}
 * and a task's own {@see \Prado\Util\Cron\TCronTask::setTimeout Timeout} bound a run with a
 * {@see \Prado\IO\Async\TCancellationToken} the task observes.
 *
 * With {@see setDaemon Daemon}, the crontab's `cron/run` tick elects a daemon instead of sweeping:
 * the process acquires the {@see setDaemonMutex DaemonMutex} lease and, when another process holds
 * it, exits at once; otherwise it runs the due tasks, waits on its event loop for the next trigger,
 * a wake from the {@see setWakeTransport WakeTransport}, or {@see setDaemonIdle DaemonIdle}, and
 * repeats.  After {@see setDaemonLifetime DaemonLifetime} the daemon retires: it keeps serving until a
 * tick's process waits for the lease, then hands it over without a gap, so the new process picks up
 * deployed code.  A changed configuration file ({@see setDaemonWatchConfig DaemonWatchConfig}) or
 * {@see retireDaemon} retires the daemon the same way; SIGTERM, SIGINT, or {@see stopDaemon} end it
 * at once.  {@see onDaemonStart}, {@see onDaemonIdle}
 * (before each wait, with the wait a handler changes), and {@see onDaemonEnd} are raised with a
 * {@see \Prado\Util\Cron\TCronDaemonEventParameter}.  A web request wakes a waiting process,
 * per-minute or daemon, through {@see signalWake} when it schedules an earlier task.
 *
 * Each sweep raises {@see onSweepStart}, whose {@see \Prado\Util\Cron\TCronSweepEventParameter} lets a
 * handler change the tasks about to run, and {@see onSweepEnd} with their results.  Every `on` event
 * of the module is also broadcast as a global `fx` event (`fxCronSweepStart`, `fxCronSweepEnd`,
 * `fxCronDaemonStart`, `fxCronDaemonIdle`, `fxCronDaemonEnd`), so a module declaring the method is
 * called without configuration.  A component raises `fxRetireCronDaemon` or `fxStopCronDaemon` to
 * retire or stop the daemon from inside its process; {@see requestDaemonRetire} and
 * {@see requestDaemonStop} do the same from another process, as the `cron/retire` and `cron/stop`
 * shell commands do.
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

	/** The behavior name for the Shell Log behavior */
	public const SHELL_LOG_BEHAVIOR = 'shellLog';

	public const TASK_KEY = 'task';

	public const SCHEDULE_KEY = 'schedule';

	public const NAME_KEY = 'name';

	public const USERNAME_KEY = 'username';

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
		} elseif (is_array($config) && isset($config['jobs'])) {
			$config = $config['jobs'];
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
		$this->setPersistentData($name, $task);
		return $task;
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
	 * through {@see runPendingTasksWithWake}.  With {@see getDaemon Daemon} in the CLI the call is
	 * {@see runDaemon}.
	 * @return int number of tasks that were pending, or that the daemon ran
	 */
	public function processPendingTasks()
	{
		if ($this->getDaemon() && $this->php_sapi_name() === 'cli') {
			return $this->runDaemon();
		}
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
	 * Runs tasks, each in a fiber of its own through {@see runTask}, with at most
	 * {@see getConcurrency} running at once.  A task removed since it was collected
	 * ({@see taskExistsInternal}) is skipped.  Returns once every task has finished.
	 * @param TCronTask[] $tasks the tasks to run, in order
	 * @param ?int $startMinute the minute (`time() / 60`) after which no further task starts, or null for no limit
	 * @return array<string, bool> whether each task that ran did so without an exception, by task name
	 * @since 4.4.0
	 */
	protected function runTasks(array $tasks, ?int $startMinute = null): array
	{
		$running = [];
		$futures = [];
		foreach ($tasks as $task) {
			if ($startMinute !== null && $startMinute != floor($this->time() / 60)) {
				break;
			}
			if (!$this->taskExistsInternal($task->getName())) {
				continue;
			}
			$this->awaitTasks($running, $this->getConcurrency() - 1);
			$running[] = $futures[$task->getName()] = TAsync::run([$this, 'runTask'], $task);
		}
		$this->awaitTasks($running, 0);
		$results = [];
		foreach ($futures as $name => $future) {
			$results[$name] = (bool) $future->await();
		}
		return $results;
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
	 * fiber's user in {@see \Prado\IO\Async\TFiberContext} for the run.  A deadline from the
	 * task's Timeout or the module's {@see getTaskTimeout} becomes the task's
	 * {@see \Prado\Util\Cron\TCronTask::getCancellationToken() CancellationToken}.  A `dyRunTask`
	 * permission denial ({@see PERM_CRON_RUN_TASK}) is logged, advances the task's info like a
	 * run, and returns false.  {@see updateTaskInfo} claims the run first; when another process
	 * claimed it, the task is skipped.  An exception from the task is logged by
	 * {@see logCronTaskError} and does not leave this method.
	 * @param \Prado\Util\Cron\TCronTask $task the task to run.
	 * @return bool whether the task ran without an exception
	 */
	public function runTask($task)
	{
		$app = $this->getApplication();
		[$user, $username] = $this->resolveTaskUser($task);
		$hadFiberUser = TFiberContext::has(TApplication::USER_CONTEXT_KEY);
		$priorFiberUser = TFiberContext::get(TApplication::USER_CONTEXT_KEY);
		if ($user !== null) {
			TFiberContext::set(TApplication::USER_CONTEXT_KEY, $user);
			$app->onSetUser($user);
		}
		$timeout = $task->getTimeout() ?? $this->getTaskTimeout();
		$token = ($timeout !== null) ? new TCancellationToken($timeout) : null;
		$task->setCancellationToken($token);
		try {
			if ($this->dyRunTask(false, $task, ['extra' => ['username' => $task->getUserName()]]) === true) {
				$this->dyLogPermissionFailed(static::PERM_CRON_RUN_TASK, 'running cron task \'' . $task->getName() . '\'');
				$this->updateTaskInfo($task);
				return false;
			}
			if (!$this->updateTaskInfo($task)) {
				Prado::log('Cron task (' . $task->getName() . ') was claimed by another process', TLogger::INFO, TCronModule::class);
				return false;
			}
			$this->logCronTask($task, $username);
			try {
				$task->execute($this);
				$this->logCronTaskEnd($task);
				return true;
			} catch (\Throwable $e) {
				$this->logCronTaskError($task, $e);
				return false;
			}
		} finally {
			$task->setCancellationToken(null);
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
	 */
	protected function logCronTask($task, $username)
	{
		Prado::log('Running cron task (' . $task->getName() . ', ' . $username . ')', TLogger::INFO, TCronModule::class);
		$this->dyLogCronTask($task, $username);
	}

	/**
	 * Claims the run of a task: sets the lastExecTime to now and increments the processCount, and
	 * saves the new data to the global state.  TCronModule has one process per application, so
	 * every claim succeeds; TDbCronManager claims against the database row.
	 * @param TCronTask $task
	 * @return bool whether this process claimed the run
	 */
	protected function updateTaskInfo($task)
	{
		$tasksInfo = $this->getApplication()->getGlobalState(self::TASKS_INFO, []);
		$name = $task->getName();
		$time = $tasksInfo[$name]['lastExecTime'] = $this->time();
		$count = $tasksInfo[$name]['processCount'] = $task->getProcessCount() + 1;
		$task->setProcessCount($count);
		$task->setLastExecTime($time);

		$this->getApplication()->setGlobalState(self::TASKS_INFO, $tasksInfo, [], true);
		return true;
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
	 * @return int the number of tasks that run at once, default 1
	 * @since 4.4.0
	 */
	public function getConcurrency(): int
	{
		return $this->_concurrency;
	}

	/**
	 * @param int|string $count the number of tasks that run at once; 1 runs the tasks in order
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
	 * {@see runTasks} with the module's Concurrency.
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
				$this->waitDaemon($loop, max($param->getWait(), static::DAEMON_MIN_WAIT));
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
	 * Retires the daemon when a watched configuration file changed since it started.
	 * @since 4.4.0
	 */
	protected function checkDaemonConfig(): void
	{
		if ($this->_daemonFingerprint === null || $this->_daemonRetiring || $this->_daemonRetireRequested) {
			return;
		}
		if ($this->_daemonFingerprint !== $this->getDaemonConfigFingerprint()) {
			Prado::log('Cron daemon (' . $this->getDaemonLockName() . ') configuration changed; retiring for the next tick', TLogger::INFO, TCronModule::class);
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
