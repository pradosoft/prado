<?php

/**
 * TBinaryStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\Exceptions\TIOException;
use Prado\IO\TByteOrder;
use Psr\Http\Message\StreamInterface;

/**
 * TBinaryStream class.
 *
 * A slim {@see TStreamDecorator} that reads typed binary fields from an inner stream:
 * {@see readInt32()}, {@see readUInt16()}, {@see readDouble()}, {@see readBytes()}, and
 * the rest.  It holds no buffer of its own; each field is read straight from the inner
 * stream, so throughput rests on that stream's native buffering (a resource-backed
 * {@see \Prado\IO\TStream} over a file or php://memory/temp reads through PHP's stream
 * buffer).
 *
 * Multi-byte values are unpacked in machine order and byte-reversed when the requested
 * {@see TByteOrder} differs, with an optional per-call order overriding the configured
 * {@see getByteOrder() ByteOrder}.
 *
 * ```php
 * $b = new TBinaryStream(TStream::fromFile('image.tif', 'rb'), TByteOrder::LittleEndian);
 * $magic = $b->readUInt16();
 * $count = $b->readUInt32();
 * $tag   = $b->readBytes(4);
 * ```
 *
 * It reads; writes forward to the inner stream. Typed writing is provided by
 * {@see \Prado\IO\Behavior\TBinaryStreamBehavior}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBinaryStream extends TStreamDecorator
{
	/** @var ?int The default byte order (a {@see TByteOrder} constant); null is the machine order. */
	private ?int $_byteOrder = null;

	/**
	 * @param ?StreamInterface $stream The inner stream to read from.
	 * @param ?int $byteOrder The default byte order ({@see TByteOrder} constant), or null for machine order.
	 */
	public function __construct(?StreamInterface $stream = null, ?int $byteOrder = null)
	{
		$this->setByteOrderDirect($byteOrder);
		parent::__construct($stream);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw default byte order.
	 * @return ?int The raw default byte order.
	 */
	protected function getByteOrderDirect(): ?int
	{
		return $this->_byteOrder;
	}

	/**
	 * Sets the raw default byte order.
	 * @param ?int $value The raw default byte order.
	 */
	protected function setByteOrderDirect(?int $value): void
	{
		$this->_byteOrder = $value;
	}

	/**
	 * Returns the default byte order used by the typed reads.
	 * @return ?int A {@see TByteOrder} constant, or null for the machine order.
	 */
	public function getByteOrder(): ?int
	{
		return $this->getByteOrderDirect();
	}

	/**
	 * Sets the default byte order used by the typed reads.
	 * @param ?int $byteOrder A {@see TByteOrder} constant, or null for the machine order.
	 */
	public function setByteOrder(?int $byteOrder): void
	{
		$this->setByteOrderDirect($byteOrder);
	}

	//
	// ─── Typed reads ─────────────────────────────────────────────────────────
	//

	/**
	 * Reads exactly $length bytes, looping over short reads and throwing at EOF.
	 * @param int $length The number of bytes to read.
	 * @throws TIOException When the stream ends before $length bytes are read.
	 * @return string The bytes read.
	 */
	public function readBytes(int $length): string
	{
		if ($length <= 0) {
			return '';
		}
		$data = '';
		while (strlen($data) < $length) {
			$chunk = $this->read($length - strlen($data));
			if ($chunk === '') {
				throw new TIOException('binarystream_unexpected_eof', $length, strlen($data));
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Reads and unpacks a fixed-width value, byte-reversing when the requested order
	 * differs from the machine order.
	 * @param int $bytes The number of bytes to read.
	 * @param string $code The single-element {@see unpack()} format code.
	 * @param ?int $order The byte order, or null for the configured/machine default.
	 * @return float|int The unpacked value.
	 */
	private function readPacked(int $bytes, string $code, ?int $order): int|float
	{
		$data = $this->readBytes($bytes);
		if ($bytes > 1 && TByteOrder::resolve($order ?? $this->getByteOrderDirect()) !== TByteOrder::native()) {
			$data = strrev($data);
		}
		return unpack($code, $data)[1];
	}

	/** Reads an unsigned 8-bit integer. @return int The value. */
	public function readUInt8(): int
	{
		return (int) $this->readPacked(1, 'C', null);
	}

	/** Reads a signed 8-bit integer. @return int The value. */
	public function readInt8(): int
	{
		return (int) $this->readPacked(1, 'c', null);
	}

	/** Reads an unsigned 16-bit integer. @param ?int $order The byte order. @return int The value. */
	public function readUInt16(?int $order = null): int
	{
		return (int) $this->readPacked(2, 'S', $order);
	}

	/** Reads a signed 16-bit integer. @param ?int $order The byte order. @return int The value. */
	public function readInt16(?int $order = null): int
	{
		return (int) $this->readPacked(2, 's', $order);
	}

	/** Reads an unsigned 32-bit integer. @param ?int $order The byte order. @return int The value. */
	public function readUInt32(?int $order = null): int
	{
		return (int) $this->readPacked(4, 'L', $order);
	}

	/** Reads a signed 32-bit integer. @param ?int $order The byte order. @return int The value. */
	public function readInt32(?int $order = null): int
	{
		return (int) $this->readPacked(4, 'l', $order);
	}

	/** Reads an unsigned 64-bit integer. @param ?int $order The byte order. @return int The value. */
	public function readUInt64(?int $order = null): int
	{
		return (int) $this->readPacked(8, 'Q', $order);
	}

	/** Reads a signed 64-bit integer. @param ?int $order The byte order. @return int The value. */
	public function readInt64(?int $order = null): int
	{
		return (int) $this->readPacked(8, 'q', $order);
	}

	/** Reads a 32-bit float. @param ?int $order The byte order. @return float The value. */
	public function readFloat(?int $order = null): float
	{
		return (float) $this->readPacked(4, 'f', $order);
	}

	/** Reads a 64-bit double. @param ?int $order The byte order. @return float The value. */
	public function readDouble(?int $order = null): float
	{
		return (float) $this->readPacked(8, 'd', $order);
	}
}
