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
 * IStyleable interface.
 *
 * This interface should be implemented by classes that support CSS styles.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.1.0
 */
interface IStyleable
{
	/**
	 * @return boolean whether the object has defined any style information
	 */
	public function getHasStyle();
	/**
	 * @return TStyle the object representing the css style of the object
	 */
	public function getStyle();
	/**
	 * Removes all styles associated with the object
	 */
	public function clearStyle();
}