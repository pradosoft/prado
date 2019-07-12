<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * TBroadcastEventParameter class
 *
 * TBroadcastEventParameter encapsulates the parameter data for
 * events that are broadcasted. The name of of the event is specified via
 * {@link setName Name} property while the event parameter is via
 * {@link setParameter Parameter} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TBroadcastEventParameter extends \Prado\TEventParameter
{
	private $_name;
	private $_param;

	/**
	 * Constructor.
	 * @param string $name name of the broadcast event
	 * @param null|mixed $parameter parameter of the broadcast event
	 */
	public function __construct($name = '', $parameter = null)
	{
		$this->_name = $name;
		$this->_param = $parameter;
	}

	/**
	 * @return string name of the broadcast event
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string $value name of the broadcast event
	 */
	public function setName($value)
	{
		$this->_name = $value;
	}

	/**
	 * @return mixed parameter of the broadcast event
	 */
	public function getParameter()
	{
		return $this->_param;
	}

	/**
	 * @param mixed $value parameter of the broadcast event
	 */
	public function setParameter($value)
	{
		$this->_param = $value;
	}
}
