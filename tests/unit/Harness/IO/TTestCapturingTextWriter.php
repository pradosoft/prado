<?php

/**
 * TTestCapturingTextWriter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\IO;

use Prado\IO\TTextWriter;

/**
 * TTestCapturingTextWriter keeps every string written, so a test reads output
 * that the code under test has already flushed.
 */
class TTestCapturingTextWriter extends TTextWriter
{
	/** @var string everything written since construction or the last {@see clearCaptured()} */
	private string $_captured = '';

	/**
	 * Writes a string and keeps a copy of it.
	 * @param string $str the string to write
	 */
	public function write($str)
	{
		$this->_captured .= $str;
		parent::write($str);
	}

	/**
	 * Returns everything written since construction or the last {@see clearCaptured()}.
	 * @return string the captured output.
	 */
	public function getCaptured(): string
	{
		return $this->_captured;
	}

	/**
	 * Discards the captured output.
	 */
	public function clearCaptured(): void
	{
		$this->_captured = '';
	}
}
