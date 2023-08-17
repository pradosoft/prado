<?php
/**
 * TStdOutWriter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

/**
 * TStdOutWriter class.
 *
 * TStdOutWriter extends TTextWriter to fwrite the buffer to STDOUT when {@see flush}ed.
 * This allows for testing of the Shell output.
 *
 * STDOUT is only defined in the CLI.  When processing a PHP web page, this opens
 * a new handle to 'php://stdout'.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TStdOutWriter extends TTextWriter
{
	/** The file path to open a data stream in memory */
	public const STDOUT_URI = 'php://stdout';

	/** @var mixed the Standard Out stream handle */
	private mixed $_stdout;

	/**
	 * Closes the StdOut handle when STDOUT is not defined
	 */
	public function __destruct()
	{
		if (!defined('STDOUT') && $this->_stdout) {
			fclose($this->_stdout);
		}
		parent::__destruct();
	}

	/**
	 * Flushes the content that has been written.
	 * @return string the content being flushed
	 */
	public function flush()
	{
		$str = parent::flush();

		if (!$this->_stdout) {
			if (!defined('STDOUT')) {
				$this->_stdout = fopen(TStdOutWriter::STDOUT_URI, 'wb');
			} else {
				$this->_stdout = STDOUT;
			}
		}

		fwrite($this->_stdout, $str);
		flush();

		return $str;
	}
}
