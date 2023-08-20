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
 * TOutputWriter extends TTextWriter to write the buffer to "Output" when {@see flush}ed.
 * In a CLI execution, STDOUT is "Output" but this is not true for processing web
 * pages.  For web pages, "Output" goes to the browser and 'php://stdout' goes to
 * the web server's output (either cli or file); while STDOUT is not defined.
 *
 * Once this class is flushed, PHP's flush need to be called to ensure the "Output"
 * is written.  If Output Buffering (ob_*) is used, ob_flush (or equivalent) also
 * must be called before PHP's flush and after this class flushes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TOutputWriter extends TTextWriter
{
	/** @const The file path to open a data stream to Output. */
	public const OUTPUT_URI = 'php://output';

	/** @const The type of stream for Output. */
	public const OUTPUT_TYPE = 'Output';

	/**
	 * Flushes the content that has been written.  This does not call PHP's ob_flush
	 * or flush and those must be called to ensure the output is actually sent.
	 * @return string the content being flushed.
	 */
	public function flush()
	{
		$str = parent::flush();
		echo $str;
		return $str;
	}
}
