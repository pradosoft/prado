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
 * TCommandEventParameter class
 *
 * TCommandEventParameter encapsulates the parameter data for <b>Command</b>
 * event of button controls. You can access the name of the command via
 * {@link getCommandName CommandName} property, and the parameter carried
 * with the command via {@link getCommandParameter CommandParameter} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TCommandEventParameter extends \Prado\TEventParameter
{
	private $_name;
	private $_param;

	/**
	 * Constructor.
	 * @param string $name name of the command
	 * @param string $parameter parameter of the command
	 */
	public function __construct($name = '', $parameter = '')
	{
		$this->_name = $name;
		$this->_param = $parameter;
	}

	/**
	 * @return string name of the command
	 */
	public function getCommandName()
	{
		return $this->_name;
	}

	/**
	 * @return string parameter of the command
	 */
	public function getCommandParameter()
	{
		return $this->_param;
	}
}
