<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
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
class TBroadcastEventParameter extends TEventParameter
{
	private $_name;
	private $_param;

	/**
	 * Constructor.
	 * @param string name of the broadcast event
	 * @param mixed parameter of the broadcast event
	 */
	public function __construct($name='',$parameter=null)
	{
		$this->_name=$name;
		$this->_param=$parameter;
	}

	/**
	 * @return string name of the broadcast event
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string name of the broadcast event
	 */
	public function setName($value)
	{
		$this->_name=$value;
	}

	/**
	 * @return mixed parameter of the broadcast event
	 */
	public function getParameter()
	{
		return $this->_param;
	}

	/**
	 * @param mixed parameter of the broadcast event
	 */
	public function setParameter($value)
	{
		$this->_param=$value;
	}
}
