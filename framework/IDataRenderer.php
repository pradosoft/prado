<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */

/**
 * IDataRenderer interface.
 *
 * If a control wants to be used a renderer for another data-bound control,
 * this interface must be implemented.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.1
 */
interface IDataRenderer
{
	/**
	 * @return mixed the data bound to this object
	 */
	public function getData();

	/**
	 * @param mixed the data to be bound to this object
	 */
	public function setData($value);
}