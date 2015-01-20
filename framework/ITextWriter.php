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
 * ITextWriter interface.
 *
 * This interface must be implemented by writers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
interface ITextWriter
{
	/**
	 * Writes a string.
	 * @param string string to be written
	 */
	public function write($str);
	/**
	 * Flushes the content that has been written.
	 */
	public function flush();
}