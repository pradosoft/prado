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
 * IBindable interface.
 *
 * This interface must be implemented by classes that are capable of performing databinding.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
interface IBindable
{
	/**
	 * Performs databinding.
	 */
	public function dataBind();
}