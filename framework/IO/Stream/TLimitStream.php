<?php

/**
 * TLimitStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * TLimitStream class.
 *
 * Presents a fixed window of an inner stream: reading starts at a byte offset and stops
 * after a byte limit, as if the surrounding bytes were not present.  Positions reported
 * by {@see tell()} are relative to the window start, so a consumer sees an independent
 * stream of the limited length.
 *
 * ```php
 * $whole = TStream::fromString('HEADER<payload>TRAILER');
 * $body  = new TLimitStream($whole, 9, 6);   // 9 bytes starting at offset 6
 * echo (string) $body;                        // "<payload>"
 * ```
 *
 * The decorator caps reads to the remaining window and reports {@see eof()} at the
 * limit.  Reads and writes pass through to the inner stream.  {@see getReportInnerSize()}
 * flips {@see getSize()} between the window length (default) and the inner stream's full
 * size.  The limit is a read window over existing bytes; {@see TFreeSpaceStream} and
 * {@see TDroppingStream} bound how much can be written.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLimitStream extends TStreamDecorator
{
	/** @var int The byte offset of the window start within the inner stream. */
	private int $_offset;

	/** @var int The window length in bytes, or -1 for the remainder of the inner stream. */
	private int $_limit;

	/** @var bool Whether {@see getSize()} reports the inner stream's full size instead of the window length. Default false. */
	private bool $_reportInnerSize = false;

	/**
	 * @param StreamInterface $stream The inner stream to window.
	 * @param int $limit The window length in bytes, or -1 for the remainder. Default -1.
	 * @param int $offset The byte offset of the window start. Default 0.
	 * @param bool $reportInnerSize Whether {@see getSize()} reports the inner stream's full size. Default false.
	 */
	public function __construct(StreamInterface $stream, int $limit = -1, int $offset = 0, bool $reportInnerSize = false)
	{
		parent::__construct($stream);
		$this->setLimitDirect($limit);
		$this->setOffsetDirect($offset);
		$this->setReportInnerSizeDirect($reportInnerSize);
		$this->positionToOffset();
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw window start offset.
	 * @return int The raw window start offset.
	 */
	protected function getOffsetDirect(): int
	{
		return $this->_offset;
	}

	/**
	 * Sets the raw window start offset.
	 * @param int $value The raw window start offset.
	 */
	protected function setOffsetDirect(int $value): void
	{
		$this->_offset = $value;
	}

	/**
	 * Returns the raw window length.
	 * @return int The raw window length.
	 */
	protected function getLimitDirect(): int
	{
		return $this->_limit;
	}

	/**
	 * Sets the raw window length.
	 * @param int $value The raw window length.
	 */
	protected function setLimitDirect(int $value): void
	{
		$this->_limit = $value;
	}

	/**
	 * Returns the raw report-inner-size flag.
	 * @return bool The raw report-inner-size flag.
	 */
	protected function getReportInnerSizeDirect(): bool
	{
		return $this->_reportInnerSize;
	}

	/**
	 * Sets the raw report-inner-size flag.
	 * @param bool $value The raw report-inner-size flag.
	 */
	protected function setReportInnerSizeDirect(bool $value): void
	{
		$this->_reportInnerSize = $value;
	}

	/**
	 * Returns whether {@see getSize()} reports the inner stream's full size.
	 * @return bool True when getSize() reports the inner stream's full size, false for the window length.
	 */
	public function getReportInnerSize(): bool
	{
		return $this->getReportInnerSizeDirect();
	}

	/**
	 * Sets whether {@see getSize()} reports the inner stream's full size instead of the window length.
	 * @param bool $value True to report the inner stream's full size.
	 */
	public function setReportInnerSize(bool $value): void
	{
		$this->setReportInnerSizeDirect($value);
	}

	/**
	 * Returns the window length capped by the inner stream size, or the inner stream's full
	 * size when {@see getReportInnerSize()} is set.
	 * @return ?int The size in bytes, or null when the inner size is unknown.
	 */
	public function getSize(): ?int
	{
		$size = $this->getStream()->getSize();
		if ($size === null) {
			return null;
		}
		if ($this->getReportInnerSizeDirect()) {
			return $size;
		}
		$available = max(0, $size - $this->getOffsetDirect());
		return $this->getLimitDirect() === -1 ? $available : min($this->getLimitDirect(), $available);
	}

	/**
	 * Returns the position relative to the window start.
	 * @return int The position in bytes.
	 */
	public function tell(): int
	{
		return $this->getStream()->tell() - $this->getOffsetDirect();
	}

	/**
	 * Indicates whether the window is exhausted.
	 * @return bool True at the window limit or the end of the inner stream.
	 */
	public function eof(): bool
	{
		if ($this->getLimitDirect() !== -1 && $this->tell() >= $this->getLimitDirect()) {
			return true;
		}
		return $this->getStream()->eof();
	}

	/**
	 * Seeks within the window. SEEK_END is unsupported.
	 * @param int $offset The offset relative to the window.
	 * @param int $whence SEEK_SET or SEEK_CUR.
	 * @throws \RuntimeException When the whence is unknown, SEEK_END is used, or the target is
	 *   before the window start.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		if ($whence === SEEK_END) {
			throw new \RuntimeException('TLimitStream does not support seeking from the end');
		}
		if ($whence !== SEEK_SET && $whence !== SEEK_CUR) {
			throw new \RuntimeException('TLimitStream cannot seek with unknown whence ' . $whence);
		}
		if ($whence === SEEK_CUR) {
			$offset += $this->tell();
		}
		if ($offset < 0) {
			throw new \RuntimeException('TLimitStream cannot seek before the window start');
		}
		$this->getStream()->seek($this->getOffsetDirect() + $offset, SEEK_SET);
	}

	/**
	 * Reads up to $length bytes, capped by the remaining window.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read; '' once the window is exhausted.
	 */
	public function read(int $length): string
	{
		if ($this->getLimitDirect() === -1) {
			return $this->getStream()->read($length);
		}
		$remaining = $this->getLimitDirect() - $this->tell();
		if ($remaining <= 0) {
			return '';
		}
		return $this->getStream()->read(min($length, $remaining));
	}

	/**
	 * Positions the inner stream at the window start, seeking when possible and
	 * discarding bytes otherwise.
	 */
	private function positionToOffset(): void
	{
		if ($this->getOffsetDirect() <= 0) {
			return;
		}
		if ($this->getStream()->isSeekable()) {
			$this->getStream()->seek($this->getOffsetDirect(), SEEK_SET);
			return;
		}
		$current = 0;
		while ($current < $this->getOffsetDirect() && !$this->getStream()->eof()) {
			$chunk = $this->getStream()->read($this->getOffsetDirect() - $current);
			if ($chunk === '') {
				break;
			}
			$current += strlen($chunk);
		}
	}
}
