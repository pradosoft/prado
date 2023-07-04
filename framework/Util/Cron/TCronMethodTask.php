<?php

/**
 * TCronMethodTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TCronMethodTask class.
 *
 * This class will evaluate a specific method with parameters when
 * running the task the specified module.
 * ```php
 *		<job schedule="* * * * *" task="dbcache->flushCacheExpired(true)" / >
 *		<job schedule="* * * * *" task="dbcache->flushCacheExpired" / >
 *		<job schedule="* * * * *" task="amodule->taskmethod($this->getModule()->getProperty())" / >
 * ```
 *
 * The parenthesis may be omitted, or parameters may be functions themselves.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TCronMethodTask extends TCronTask
{
	/** @var string the method and parameters to call on the module */
	private $_method;

	/**
	 * @param null|\Prado\IModule|string $moduleId the module or id of the module to call
	 * @param string $method the method and parameters on the
	 */
	public function __construct($moduleId = null, $method = null)
	{
		if ($moduleId !== null) {
			$this->setModuleId($moduleId);
		}
		if ($method !== null) {
			$this->setMethod($method);
		}
	}

	/**
	 * @return string the user id executing the Task
	 */
	public function getTask()
	{
		return $this->getModuleId() . TCronModule::METHOD_SEPARATOR . $this->getMethod();
	}

	/**
	 * implements task to get the module from $_moduleId, then run $_method on it
	 * @param TCronModule $cron the module calling the task
	 */
	public function execute($cron)
	{
		$method = $this->_method;
		if (strpos($method, '(') === false) {
			$method .= '()';
		}
		$this->evaluateExpression('$this->getModule()->' . $method);
	}

	/**
	 * Validates the method exists on the module, for manual task installation.
	 */
	public function validateTask()
	{
		$module = $this->getModule();
		$method = $this->_method;
		if (($pos = strpos($method, '(')) !== false) {
			$method = substr($method, 0, $pos);
		}

		return method_exists($module, $method);
	}

	/**
	 * Gets the module for the task based upon the {@see getModuleId}.
	 * This verifies that the module does exist.
	 * @throws TConfigurationException when no module is found
	 * @return \Prado\IModule returns the module (from the application) of ModuleId
	 */
	public function getModule()
	{
		$module = parent::getModule();
		if ($module === null) {
			throw new TConfigurationException('cronmethodtask_no_module', $this->getModuleId());
		}
		return $module;
	}

	/**
	 * @return string the method of the module to call
	 */
	public function getMethod()
	{
		return $this->_method;
	}

	/**
	 * @param string $method the method of the module to call
	 */
	public function setMethod($method)
	{
		$this->_method = TPropertyValue::ensureString($method);
	}
}
