<?php

/**
 * TCronTaskInfo class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TCronTaskInfo class.
 *
 * TCronTaskInfo helps distribute information for application cron tasks.
 * This is for adding cron tasks via TDbCronModule.  The Task can be a class,
 * or a module id with method and parameters.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Cron
 * @since 4.2.0
 */

class TCronTaskInfo extends \Prado\TComponent
{
	/** @var string the short reference name of the task  */
	protected $_name;
	
	/** @var string class name or module/method of the task to instance */
	protected $_task;
	
	/** @var string module servicing the task */
	protected $_moduleid;
	
	/** @var string title of the task */
	protected $_title;
	
	/** @var string a short description of the task */
	protected $_description;
	
	/**
	 * This sets the main properties in the class
	 * @param string $name the short reference name of the task
	 * @param callable|string $task the class to instance for the task
	 * @param null|\Prado\IModule|string $moduleid the module sericing the task
	 * @param null|string $title the title of the task
	 * @param null|string $description a short description of the task
	 */
	public function __construct($name, $task, $moduleid = null, $title = null, $description = null)
	{
		$this->setName($name);
		$this->setTask($task);
		if ($moduleid !== null) {
			$this->setModuleId($moduleid);
		}
		if ($title !== null) {
			$this->setTitle($title);
		}
		if ($description !== null) {
			$this->setDescription($description);
		}
		parent::__construct();
	}
	
	/**
	 * @return string the name of the Task
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Sets the name of the Task
	 * @param string $name the name of the Task
	 */
	public function setName($name)
	{
		$this->_name = TPropertyValue::ensureString($name);
	}
	
	/**
	 * @return string the Task Class of the Task, with namespaces
	 */
	public function getTask()
	{
		return $this->_task;
	}

	/**
	 * Sets the Task Class of the Task
	 * @param string $task Class the Task Class of the Task
	 */
	public function setTask($task)
	{
		$this->_task = TPropertyValue::ensureString($task);
	}
	
	/**
	 * @return string the module id of the utility module for the task
	 */
	public function getModuleId()
	{
		return $this->_moduleid;
	}

	/**
	 * @param string $moduleid the module id of the utility module for the task
	 */
	public function setModuleId($moduleid)
	{
		if (is_object($moduleid) && $moduleid->isa('Prado\\IModule')) {
			$this->_moduleid = $moduleid->getId();
		} else {
			$this->_moduleid = TPropertyValue::ensureString($moduleid);
		}
	}
	
	/**
	 * @return \Prado\TModule gets the module from the module id for the task
	 */
	public function getModule()
	{
		if (!$this->_moduleid) {
			return null;
		}
		return Prado::getApplication()->getModule($this->_moduleid);
	}
	
	/**
	 * @return string the title of the Task
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * @param string $title the title of the Task
	 */
	public function setTitle($title)
	{
		$this->_title = TPropertyValue::ensureString($title);
	}
	
	/**
	 * @return string the Description of the Task
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * Sets the Description of the Task
	 * @param string $description the Description of the Task
	 */
	public function setDescription($description)
	{
		$this->_description = TPropertyValue::ensureString($description);
	}
}
