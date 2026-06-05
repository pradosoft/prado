<?php

/**
 * TTestStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\TStream;

/**
 * TTestStream is an instrumented {@see \Prado\IO\TStream} for unit tests.
 *
 * {@see TStream} is concrete, so most tests open it through {@see TTestIOHelper}.  This
 * harness exists to exercise the self-encapsulation seam: the PSR-7 capability methods
 * {@see \Prado\IO\TStream::isReadable()}, {@see \Prado\IO\TStream::isWritable()}, and
 * {@see \Prado\IO\TStream::isSeekable()} are the single override points, and the public
 * operations ({@see \Prado\IO\TStream::read()}, {@see \Prado\IO\TStream::write()},
 * {@see \Prado\IO\TStream::seek()}) all route through them.
 *
 * The force flags override what each capability method reports, independent of the
 * underlying handle:
 *
 * - {@see $forceReadable}/{@see $forceWritable}/{@see $forceSeekable}, when not null,
 *   replace the value the matching getter returns. A test sets one to confirm that the
 *   override governs every public surface and internal guard at once.
 *
 * Counters {@see $readCalls} and {@see $writeCalls} record how often the public read and
 * write operations ran. The raw stored capability flags remain reachable through
 * {@see rawReadable()}/{@see rawWritable()}/{@see rawSeekable()} for asserting the override
 * diverges from the handle's true capability.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestStream extends TStream
{
	/** @var ?bool When set, the value {@see isReadable()} reports; null uses the real flag. */
	public ?bool $forceReadable = null;

	/** @var ?bool When set, the value {@see isWritable()} reports; null uses the real flag. */
	public ?bool $forceWritable = null;

	/** @var ?bool When set, the value {@see isSeekable()} reports; null uses the real flag. */
	public ?bool $forceSeekable = null;

	/** @var int The number of times {@see read()} has run. */
	public int $readCalls = 0;

	/** @var int The number of times {@see write()} has run. */
	public int $writeCalls = 0;

	/**
	 * Reports the readable capability, honoring {@see $forceReadable} when set.
	 * @return bool Whether the stream is readable.
	 */
	public function isReadable(): bool
	{
		return $this->forceReadable ?? parent::isReadable();
	}

	/**
	 * Reports the writable capability, honoring {@see $forceWritable} when set.
	 * @return bool Whether the stream is writable.
	 */
	public function isWritable(): bool
	{
		return $this->forceWritable ?? parent::isWritable();
	}

	/**
	 * Reports the seekable capability, honoring {@see $forceSeekable} when set.
	 * @return bool Whether the stream is seekable.
	 */
	public function isSeekable(): bool
	{
		return $this->forceSeekable ?? parent::isSeekable();
	}

	/**
	 * Counts the call, then delegates to the base read.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function read(int $length): string
	{
		$this->readCalls++;
		return parent::read($length);
	}

	/**
	 * Counts the call, then delegates to the base write.
	 * @param string $string The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		$this->writeCalls++;
		return parent::write($string);
	}

	/**
	 * Returns the raw stored readable flag, bypassing {@see $forceReadable}.
	 * @return bool The stored readable flag.
	 */
	public function rawReadable(): bool
	{
		return parent::isReadable();
	}

	/**
	 * Returns the raw stored writable flag, bypassing {@see $forceWritable}.
	 * @return bool The stored writable flag.
	 */
	public function rawWritable(): bool
	{
		return parent::isWritable();
	}

	/**
	 * Returns the raw stored seekable flag, bypassing {@see $forceSeekable}.
	 * @return bool The stored seekable flag.
	 */
	public function rawSeekable(): bool
	{
		return parent::isSeekable();
	}
}
