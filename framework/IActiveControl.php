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
 * IActiveControl interface.
 *
 * Active controls must implement IActiveControl interface.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package System
 * @since 3.1
 */
interface IActiveControl
{
	/**
	 * @return TBaseActiveControl Active control properties.
	 */
	public function getActiveControl();
}