<?php

/**
 * TCronMethodTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\TPropertyValue;

/**
 * TCronMethodTask class.
 *
 * This class will evaluate a specific method and parameters when
 * running the task.
 * <code>
 *		<task schedule="* * * * *" task="dbcache.flushCacheExpired(true)" / >
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Cron
 * @since 4.2.0
 */
 
 class TCronMethodTask extends TCronTask
 {
 	/** @var string the method to call on the module */
 	private $_method;
	
 	/**
 	 * @param null|Prado\IModule|string $moduleId the module or id of the module to call
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
	
 	/** implements task to get the module from $_moduleId, then run $_method on it */
 	public function task($sender, $param)
 	{
 		$method = $this->_method;
 		if (strpos($method, '(') === false) {
 			$method .= '()';
 		}
 		$this->evaluateExpression('$this->getModule()->' . $method);
 	}
	
 	public function validateTask()
 	{
 		$module = $this->getModule();
 		$method = $this->_method;
 		if (($pos = strpos($method, '(')) !== false) {
 			$method = substr($method, 0, $pos);
 		}
		
 		return method_exists($module, $method);
 	}
	
 	public function getMethod()
 	{
 		return $this->_method;
 	}
	
 	public function setMethod($method)
 	{
 		$this->_method = TPropertyValue::ensureString($method);
 	}
 }
