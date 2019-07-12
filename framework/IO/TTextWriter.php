<?php
/**
 * TTextWriter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\IO
 */

namespace Prado\IO;

/**
 * TTextWriter class.
 *
 * TTextWriter implements a memory-based text writer.
 * Content written by TTextWriter are stored in memory
 * and can be obtained by calling {@link flush()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\IO
 * @since 3.0
 */
class TTextWriter extends \Prado\TComponent implements \Prado\IO\ITextWriter
{
	private $_str = '';

	/**
	 * Flushes the content that has been written.
	 * @return string the content being flushed
	 */
	public function flush()
	{
		$str = $this->_str;
		$this->_str = '';
		return $str;
	}

	/**
	 * Writes a string.
	 * @param string $str string to be written
	 */
	public function write($str)
	{
		$this->_str .= $str;
	}

	/**
	 * Writers a string and terminates it with a newline.
	 * @param string $str content to be written
	 * @see write
	 */
	public function writeLine($str = '')
	{
		$this->write($str . "\n");
	}
}
