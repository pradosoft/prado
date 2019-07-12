<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TClassBehaviorEventParameter class.
 * TClassBehaviorEventParameter is the parameter sent with the class behavior changes.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @package Prado\Util
 * @since 3.2.3
 */
class TClassBehaviorEventParameter extends \Prado\TEventParameter
{
	private $_class;
	private $_name;
	private $_behavior;
	private $_priority;

	/**
	 * Holds the parameters for the Class Behavior Events
	 *	@param string $class this is the class to get the behavior
	 *	@param string $name the name of the behavior
	 *	@param object $behavior this is the behavior to implement the class behavior
	 * @param mixed $priority
	 */
	public function __construct($class, $name, $behavior, $priority)
	{
		$this->_class = $class;
		$this->_name = $name;
		$this->_behavior = $behavior;
		$this->_priority = $priority;
	}

	/**
	 * This is the class to get the behavior
	 * @return string the class to get the behavior
	 */
	public function getClass()
	{
		return $this->_class;
	}

	/**
	 * name of the behavior
	 * @return string the name to get the behavior
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * This is the behavior which the class is to get
	 * @return object the behavior to implement
	 */
	public function getBehavior()
	{
		return $this->_behavior;
	}

	/**
	 * This is the priority which the behavior is to get
	 * @return numeric the priority of the behavior
	 */
	public function getPriority()
	{
		return $this->_priority;
	}
}
