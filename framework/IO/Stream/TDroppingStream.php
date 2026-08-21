<?php

/**
 * TDroppingStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * TDroppingStream class.
 *
 * Caps the size of the stream it wraps.  Writes proceed until the inner stream reaches
 * the maximum length; bytes that would exceed it are silently dropped, and
 * {@see write()} reports only the bytes actually written.  This bounds a buffer that an
 * untrusted or runaway producer writes to.
 *
 * The cap is measured against the inner stream's {@see getSize()}, so the inner stream
 * must report its size (a memory or file buffer does); over an unsized sink the cap
 * bounds each write alone rather than the total.
 *
 * ```php
 * $capped = new TDroppingStream(TStream::fromMemory(), 1024);
 * $capped->write($big);   // keeps at most 1024 bytes; returns the count stored
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDroppingStream extends TStreamDecorator
{
	/** @var int The maximum number of bytes the inner stream may hold. */
	private int $_maxLength;

	/**
	 * @param StreamInterface $stream The inner stream to cap.
	 * @param int $maxLength The maximum number of bytes to retain.
	 */
	public function __construct(StreamInterface $stream, int $maxLength)
	{
		parent::__construct($stream);
		$this->setMaxLengthDirect($maxLength);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw maximum length.
	 * @return int The raw maximum length.
	 */
	protected function getMaxLengthDirect(): int
	{
		return $this->_maxLength;
	}

	/**
	 * Sets the raw maximum length.
	 * @param int $value The raw maximum length.
	 */
	protected function setMaxLengthDirect(int $value): void
	{
		$this->_maxLength = $value;
	}

	/**
	 * Writes up to the remaining capacity, dropping any excess.
	 * @param string $string The bytes to write.
	 * @return int The number of bytes actually written (0 when already full).
	 */
	public function write(string $string): int
	{
		$remaining = $this->getMaxLengthDirect() - (int) $this->getStream()->getSize();
		if ($remaining <= 0) {
			return 0;
		}
		if (strlen($string) <= $remaining) {
			return $this->getStream()->write($string);
		}
		return $this->getStream()->write(substr($string, 0, $remaining));
	}
}
