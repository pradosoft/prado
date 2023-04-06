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
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Security\IUserManager;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Shell\TShellApplication;
use Prado\TPropertyValue;
use Prado\Util\TLogger;
use Prado\Xml\TXmlElement;
use Prado\Xml\TXmlDocument;

/**
 * TCronModule class.
 *
 * TCronModule runs time based services for the application. This will run a
 * task at a given time.  A task can be a task class or a module Id followed by
 * '->' followed by a method with or without parameters. eg.
 *
 * <code>
 * 	<module id="cron" class="Prado\Util\Cron\TCronModule" DefaultUserName="admin">
 *		<job Name="cronclean" Schedule="0 0 1 * * *" Task="Prado\Util\Cron\TDbCronCleanLogTask" UserName="cron" />
 *		<job Name="dbcacheclean" Schedule="* * * * *" Task="dbcache->flushCacheExpired(true)" />
 *		<job Schedule="0 * * * *" Task="mymoduleid->taskmethod" />
 *	</module>
 * </code>
 *
 * The schedule is formatted like a linux crontab schedule expression.
 * {@link TTimeSchedule} parses the schedule and supports 8 different
 * languages.  Advanced options, like @daily, and @hourly, are supported.
 *
 * This module is designed to be run as a system Crontab prado-cli every
 * minute.  The application then decides what tasks to execute, or not, and
 * when.
 *
 * The following is an example for your system cron tab to run the PRADO
 * application cron.
 * <code>
 *		* * * * *  php /dir_to_/vendor/bin/prado-cli app /dir_to_app/ cron
 * </code>
 *
 * The default cron user can be set with {@link set$DefaultUserName} with its
 * default being 'cron' user.  The default user is used when no task specific
 * user is specifiedThe 'cron' user should exist in the TUserManager to
 * switched the application user properly.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method void dyLogCron($numtasks)
 * @method void dyLogCronTask($task, $username)
 * @method void dyLogCronTaskEnd($task)
 * @method bool dyRegisterShellAction($returnValue)
 * @method \Prado\Shell\TShellWriter getOutputWriter()
 * @see https://crontab.guru For more info on Crontab Schedule Expressions.
 */
class TCronModule extends \Prado\TModule implements IPermissions
{
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

	/** @var bool if the module has been initialized */
	protected $_initialized = false;

	/** @var IUserManager user manager instance */
	private $_userManager;

	/** @var TCronTaskInfo[] The info for tasks in the system. */
	private $_tasksInfo;

	/** @var bool whether or not the $_tasks is properties or TCronTask  */
	private $_tasksInstanced = false;

	/** @var array[]|TCronTask[] the tasks */
	private $_tasks = [];

	/** @var string The user Id of the tasks without users */
	private $_defaultUserName = self::DEFAULT_CRON_USER;

	/** @var bool enable cron on requests, default false */
	private $_enableRequestCron = false;

	/** @var numeric probability that a request cron will trigger [0.0 to 100.0], default 1.0 (for 1%) */
	private $_requestCronProbability = 1.0;

	/** @var null|bool is the app running in cron shell mode or not, or null for auto-detect */
	private $_inCronShell;

	/** @var string the cli class to instance for CLI command line actions; this changes for TDbCronModule */
	protected $_shellClass = 'Prado\\Util\\Cron\\TShellCronAction';

	/** @var array[] any additional tasks to install from properties */
	private $_additionalCronTasks;

	/**
	 * Initializes the module.  Read the configuration, installs Shell Actions,
	 * should Request cron be activated.
	 * @param array|\Prado\Xml\TXmlElement $config
	 * @throws TConfigurationException when the user manage doesn't exist or is invalid
	 */
	public function init($config)
	{
		$app = $this->getApplication();

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
			$users = $app->getModulesByType('Prado\\Security\\IUserManager');
			foreach ($users as $id => $module) {
				$this->_userManager = $app->getModule($id);
				break;
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

		if (php_sapi_name() !== 'cli' && $this->getEnableRequestCron()) {
			if (100.0 * ((float) (mt_rand()) / (float) (mt_getrandmax())) <= $this->getRequestCronProbability()) {
				$app->attachEventHandler('OnEndRequest', [$this, 'processPendingTasks'], 20);
			}
		}
		$this->_initialized = true;
		parent::init($config);
	}

	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager)
	{
		return [
			new TPermissionEvent(static::PERM_CRON_SHELL, 'Activates cron shell commands.', 'dyRegisterShellAction'),
		];
	}

	/**
	 * This reads the configuration and stores the specified tasks, for lazy loading, until needed.
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
			}
			if (!($properties[self::NAME_KEY] ?? null)) {
				$class = $properties[self::TASK_KEY] ?? '';
				$schedule = $properties[self::SCHEDULE_KEY] ?? '';
				$name = $properties[self::NAME_KEY] = substr(md5($schedule . $class), 0, 7);
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
	 * @param null|mixed $param parameter for the event
	 */
	public function registerShellAction($sender, $param)
	{
		if ($this->dyRegisterShellAction(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => $this->_shellClass, 'CronModule' => $this]);
		}
	}

	/**
	 * makes the tasks from the configuration array into task objects.
	 * @return \Prado\Util\Cron\TCronTask[]
	 */
	protected function ensureTasks()
	{
		if (!$this->_tasksInstanced) {
			foreach ($this->_tasks as $properties) {
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
				$this->_tasks[$name] = $task;
			}
			$this->_tasksInstanced = true;
		}
		return $this->_tasks;
	}

	/**
	 * This lazy loads the tasks from configuration array to instance.
	 * This calls {@link ensureTasks} to get the tasks and their persistent data.
	 * @return \Prado\Util\Cron\TCronTask[] currently active cron tasks
	 */
	public function getTasks()
	{
		$this->ensureTasks();
		return $this->_tasks;
	}

	/**
	 * These are the tasks specified in the configuration and getAdditionalCronTasks
	 * until {@link ensureTasks} is called.
	 * @return array[]|TCronTask[] currently active cron tasks
	 */
	public function getRawTasks()
	{
		return $this->_tasks;
	}

	/**
	 * This lazy loads the tasks.
	 * @param string $name
	 * @return TCronTask cron tasks with a specific name
	 */
	public function getTask($name)
	{
		$tasks = $this->getTasks();
		return $tasks[$name] ?? null;
	}

	/**
	 * .
	 * @param string $taskExec the class name or "module->method('param1')" to place
	 * into a {@link TCronMethodTask}.
	 * @return TCronTask the instance of $taskExec
	 */
	public function instanceTask($taskExec)
	{
		if (($pos = strpos($taskExec, self::METHOD_SEPARATOR)) !== false) {
			$module = substr($taskExec, 0, $pos);
			$method = substr($taskExec, $pos + 2); //String Length of self::METHOD_SEPARATOR
			$task = new TCronMethodTask($module, $method);
		} else {
			$task = Prado::createComponent($taskExec);
			if (!$task instanceof \Prado\Util\Cron\TCronTask) {
				throw new TInvalidDataTypeException("cron_not_a_crontask", $taskExec);
			}
		}
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
	 * @return TCronTask[] the tasks that are pending.
	 */
	public function getPendingTasks()
	{
		$pendingtasks = [];
		foreach ($this->getTasks() as $name => $task) {
			if ($task->getIsPending()) {
				$pendingtasks[$name] = $task;
			}
		}
		return $pendingtasks;
	}

	/**
	 * Filters the Tasks for a specific class.
	 * @param string $type
	 * @return TCronTask[] the tasks of $type
	 */
	public function getTasksByType($type)
	{
		$matches = [];
		foreach ($this->getTasks() as $name => $task) {
			if ($task->isa($type)) {
				$matches[$name] = $task;
			}
		}
		return $matches;
	}

	/**
	 * processPendingTasks executes pending tasks
	 * @return int number of tasks run that were pending
	 */
	public function processPendingTasks()
	{
		$inCronTab = (($inCron = $this->getInCronShell()) !== null) ? $inCron : TShellApplication::detectCronTabShell();
		$this->filterStaleTasks();
		$pendingTasks = $this->getPendingTasks();
		$numtasks = count($pendingTasks);
		$startMinute = floor(time() / 60);

		$this->logCron($numtasks);
		if ($numtasks) {
			foreach ($pendingTasks as $key => $task) {
				if ($inCronTab && $startMinute != floor(time() / 60)) {
					break;
				}
				$this->runTask($task);
			}
		}

		return $numtasks;
	}

	/**
	 * @param int $numtasks number of tasks being processed
	 */
	protected function logCron($numtasks)
	{
		Prado::log("Processing {$numtasks} Cron Tasks", TLogger::INFO, 'Prado.Cron.TCronModule');
		$this->dyLogCron($numtasks);
		$this->setLastCronTime(microtime(true));
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
	 * Runs a specific task. Sets the user to the Task user or the cron module
	 * {@link getDefaultUserName}.
	 * @param \Prado\Util\Cron\TCronTask $task the task to run.
	 */
	public function runTask($task)
	{
		$app = $this->getApplication();
		$users = $this->getUserManager();
		$defaultUsername = $username = $this->getDefaultUserName();
		$restore_user = $app->getUser();
		$user = null;

		if ($users) {
			if ($nusername = $task->getUserName()) {
				$username = $nusername;
			}
			$user = $users->getUser($username);
			if (!$user && $username !== $defaultUsername) {
				$user = $users->getUser($username = $defaultUsername);
			}

			if ($user) {
				$app->setUser($user);
			} elseif ($restore_user) {
				$user = clone $restore_user;
				$user->setIsGuest(true);
				$app->setUser($user);
			}
		}

		$this->logCronTask($task, $username);
		$this->updateTaskInfo($task);
		$task->execute($this);
		$this->logCronTaskEnd($task);

		if ($user) {
			if ($restore_user) {
				$app->setUser($restore_user);
			} else {
				$user->setIsGuest(true);
			}
		}
	}

	/**
	 * Logs the cron task being run with the system log and output on cli
	 * @param TCronTask $task
	 * @param string $username the user the task is running under.
	 */
	protected function logCronTask($task, $username)
	{
		Prado::log('Running cron task (' . $task->getName() . ', ' . $username . ')', TLogger::INFO, 'Prado.Cron.TCronModule');
		$this->dyLogCronTask($task, $username);
	}

	/**
	 * sets the lastExecTime to now and increments the processCount.  This saves
	 * the new data to the global state.
	 * @param TCronTask $task
	 */
	protected function updateTaskInfo($task)
	{
		$tasksInfo = $this->getApplication()->getGlobalState(self::TASKS_INFO, []);
		$name = $task->getName();
		$time = $tasksInfo[$name]['lastExecTime'] = (int) microtime(true);
		$count = $tasksInfo[$name]['processCount'] = $task->getProcessCount() + 1;
		$task->setProcessCount($count);
		$task->setLastExecTime($time);

		$this->getApplication()->setGlobalState(self::TASKS_INFO, $tasksInfo, [], true);
	}

	/**
	 * Logs the end of the task.
	 * @param TCronTask $task
	 */
	protected function logCronTaskEnd($task)
	{
		Prado::log('Ending cron task (' . $task->getName() . ', ' . $task->getTask() . ')', TLogger::INFO, 'Prado.Cron.TCronModule');
		$this->dyLogCronTaskEnd($task);
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
	 * @return string the default user id of Tasks without users ids
	 */
	public function getDefaultUserName()
	{
		return $this->_defaultUserName;
	}

	/**
	 * @param string $id the default user id of Tasks without users ids
	 * @throws TInvalidOperationException if the module has been initialized
	 */
	public function setDefaultUserName($id)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('cron_property_unchangeable', 'DefaultUserName');
		}
		$this->_defaultUserName = TPropertyValue::ensureString($id);
	}

	/**
	 * @return null|IUserManager user manager instance
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('cron_property_unchangeable', 'UserManager');
		}
		if (!is_string($provider) && !($provider instanceof IUserManager) && $provider !== null) {
			throw new TConfigurationException('cron_usermanager_invalid', is_object($provider) ? get_class($provider) : $provider);
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('cron_property_unchangeable', 'EnableRequestCron');
		}
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('cron_property_unchangeable', 'RequestCronProbability');
		}
		$this->_requestCronProbability = TPropertyValue::ensureFloat($probability);
	}

	/**
	 * @return null|bool is cli running from cron.
	 * @since 4.2.2
	 */
	public function getInCronShell()
	{
		return $this->_inCronShell;
	}

	/**
	 * @param null|bool $inCronShell is cli running from cron.  This limits
	 * pending tasks to only the current minute.
	 * @since 4.2.2
	 */
	public function setInCronShell($inCronShell)
	{
		$this->_inCronShell = $inCronShell === null ? null : TPropertyValue::ensureBoolean($inCronShell === '' ? true : $inCronShell);
	}

	/**
	 * @return array additional tasks in a list.
	 */
	public function getAdditionalCronTasks()
	{
		return $this->_additionalCronTasks ?? [];
	}

	/**
	 * This will take a string that is an array of tasks that has been
	 * through serialize(), or json_encode, or is an xml file of additional tasks.
	 * If one task is set, then it is automatically placed into an array.
	 * @param null|array|string $tasks additional tasks in an array [0..n],  or
	 * a single task.
	 * @throws TInvalidOperationException if the module has been initialized
	 */
	public function setAdditionalCronTasks($tasks)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('cron_property_unchangeable', 'AdditionalCronTasks');
		}
		if (is_string($tasks)) {
			if (($t = @unserialize($tasks)) !== false) {
				$tasks = $t;
			} elseif (($t = json_decode($tasks, true)) !== null) {
				$tasks = $t;
			} else {
				$xmldoc = new TXmlDocument('1.0', 'utf-8');
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
		$this->_additionalCronTasks = $tasks;
	}
}
