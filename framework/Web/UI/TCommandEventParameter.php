<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

/**
 * TCommandEventParameter class
 *
 * TCommandEventParameter encapsulates the parameter data for <b>Command</b>
 * event of button controls. You can access the name of the command via
 * {@link getCommandName CommandName} property, and the parameter carried
 * with the command via {@link getCommandParameter CommandParameter} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
class TCommandEventParameter extends TEventParameter
{
	private $_name;
	private $_param;

	/**
	 * Constructor.
	 * @param string name of the command
	 * @param string parameter of the command
	 */
	public function __construct($name='',$parameter='')
	{
		$this->_name=$name;
		$this->_param=$parameter;
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
