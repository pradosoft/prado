<?php
/**
 * TimeTrackerException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Demos
 */

/**
 * Generic time tracker application exception. Exception messages are saved in
 * "exceptions.txt"
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Demos
 * @since 3.1
 */
class TimeTrackerException extends TException
{
	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		return dirname(__FILE__).'/exceptions.txt';
	}
}

