<?php
/**
 * TOutputWriter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

/**
 * TOutputWriter class.
 *
 * TOutputWriter extends TTextWriter to fwrite the buffer to STDOUT
 * when {@see flush}ed.  This allows for testing of the Shell output.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TOutputWriter extends TTextWriter
{
	/**
	 * Flushes the content that has been written.
	 * @return string the content being flushed
	 */
	public function flush()
	{
		$str = parent::flush();
		fwrite(STDOUT, $str);
		flush();
		return $str;
	}
}
