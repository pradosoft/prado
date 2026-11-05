<?php

/**
 * TBinaryStreamBehavior class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Behaviors;

use Prado\Exceptions\TIOException;
use Prado\IO\TByteOrder;
use Prado\Util\TBehavior;
use Psr\Http\Message\StreamInterface;

/**
 * TBinaryStreamBehavior class.
 *
 * Adds typed binary read/write methods to any {@see \Prado\IO\TStream} it is attached
 * to.  Because Prado behaviors expose their public methods on the owner, attaching
 * this behavior gives the stream `readInt32()`, `writeUInt16()`, `readDouble()`, and
 * the rest, without changing the stream class.
 *
 * ```php
 * $s = TStream::fromMemory();
 * $s->attachBehavior('binary', new TBinaryStreamBehavior(TByteOrder::BigEndian));
 * $s->writeUInt32(0x01020304);
 * $s->seek(0);
 * $len = $s->readUInt32();          // 0x01020304
 * $tag = $s->readUInt16(TByteOrder::LittleEndian);   // per-call order override
 * ```
 *
 * Each multi-byte method takes an optional byte-order argument; when omitted it uses
 * the behavior's configured {@see getByteOrder() ByteOrder} (null = the machine
 * order).  Multi-byte values are packed in machine order and byte-reversed when the
 * requested order differs, which handles signed and unsigned types uniformly.
 *
 * Values follow {@see pack()} semantics: a value outside the field width wraps modulo
 * the field (writeUInt8(256) writes 0; writeUInt8(-1) writes 255).  An unsigned 64-bit
 * value above PHP_INT_MAX reads back as a negative integer, since PHP has no unsigned
 * 64-bit type, and on a 32-bit PHP build 64-bit values beyond the integer range lose
 * precision.  The methods require the behavior to be attached to a {@see StreamInterface}
 * owner and throw {@see TIOException} otherwise.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBinaryStreamBehavior extends TBehavior
{
	/** @var ?int The default byte order; null means the machine order. */
	private ?int $_byteOrder = null;

	/**
	 * @param ?int $byteOrder The default byte order ({@see TByteOrder} constant), or null for machine order.
	 */
	public function __construct(?int $byteOrder = null)
	{
		$this->setByteOrderDirect($byteOrder);
		parent::__construct();
	}

	/**
	 * Returns the raw byte order.
	 * @return ?int The raw byte order.
	 */
	protected function getByteOrderDirect(): ?int
	{
		return $this->_byteOrder;
	}

	/**
	 * Sets the raw byte order.
	 * @param ?int $value The raw byte order.
	 */
	protected function setByteOrderDirect(?int $value): void
	{
		$this->_byteOrder = $value;
	}

	/**
	 * Returns the behavior's default byte order.
	 * @return ?int A {@see TByteOrder} constant, or null for the machine order.
	 */
	public function getByteOrder(): ?int
	{
		return $this->getByteOrderDirect();
	}

	/**
	 * Sets the behavior's default byte order.
	 * @param ?int $byteOrder A {@see TByteOrder} constant, or null for the machine order.
	 */
	public function setByteOrder(?int $byteOrder): void
	{
		$this->setByteOrderDirect($byteOrder);
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

	/** Writes an unsigned 8-bit integer. @param int $value The value. @return int Bytes written. */
	public function writeUInt8(int $value): int
	{
		return $this->writePacked($value, 1, 'C', null);
	}

	/** Writes a signed 8-bit integer. @param int $value The value. @return int Bytes written. */
	public function writeInt8(int $value): int
	{
		return $this->writePacked($value, 1, 'c', null);
	}

	/** Writes an unsigned 16-bit integer. @param int $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeUInt16(int $value, ?int $order = null): int
	{
		return $this->writePacked($value, 2, 'S', $order);
	}

	/** Writes a signed 16-bit integer. @param int $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeInt16(int $value, ?int $order = null): int
	{
		return $this->writePacked($value, 2, 's', $order);
	}

	/** Writes an unsigned 32-bit integer. @param int $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeUInt32(int $value, ?int $order = null): int
	{
		return $this->writePacked($value, 4, 'L', $order);
	}

	/** Writes a signed 32-bit integer. @param int $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeInt32(int $value, ?int $order = null): int
	{
		return $this->writePacked($value, 4, 'l', $order);
	}

	/** Writes an unsigned 64-bit integer. @param int $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeUInt64(int $value, ?int $order = null): int
	{
		return $this->writePacked($value, 8, 'Q', $order);
	}

	/** Writes a signed 64-bit integer. @param int $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeInt64(int $value, ?int $order = null): int
	{
		return $this->writePacked($value, 8, 'q', $order);
	}

	/** Writes a 32-bit float. @param float $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeFloat(float $value, ?int $order = null): int
	{
		return $this->writePacked($value, 4, 'f', $order);
	}

	/** Writes a 64-bit double. @param float $value The value. @param ?int $order The byte order. @return int Bytes written. */
	public function writeDouble(float $value, ?int $order = null): int
	{
		return $this->writePacked($value, 8, 'd', $order);
	}

	/**
	 * Reads and unpacks a fixed-width value, byte-reversing when the requested order
	 * differs from the machine order.
	 * @param int $bytes The number of bytes to read.
	 * @param string $code The single-element {@see unpack()} format code.
	 * @param ?int $order The byte order, or null for the behavior/machine default.
	 * @return float|int The unpacked value.
	 */
	private function readPacked(int $bytes, string $code, ?int $order): int|float
	{
		$data = $this->readExact($bytes);
		if ($bytes > 1 && TByteOrder::resolve($order ?? $this->getByteOrderDirect()) !== TByteOrder::native()) {
			$data = strrev($data);
		}
		return unpack($code, $data)[1];
	}

	/**
	 * Packs and writes a fixed-width value, byte-reversing when the requested order
	 * differs from the machine order.
	 * @param float|int $value The value to write.
	 * @param int $bytes The packed width in bytes.
	 * @param string $code The single-element {@see pack()} format code.
	 * @param ?int $order The byte order, or null for the behavior/machine default.
	 * @return int The number of bytes written.
	 */
	private function writePacked(int|float $value, int $bytes, string $code, ?int $order): int
	{
		$data = pack($code, $value);
		if ($bytes > 1 && TByteOrder::resolve($order ?? $this->getByteOrderDirect()) !== TByteOrder::native()) {
			$data = strrev($data);
		}
		return $this->stream()->write($data);
	}

	/**
	 * Reads exactly the requested number of bytes from the owner stream.
	 * @param int $bytes The number of bytes to read.
	 * @throws TIOException When the stream ends before the bytes are read.
	 * @return string The bytes read.
	 */
	private function readExact(int $bytes): string
	{
		$stream = $this->stream();
		$data = '';
		while (strlen($data) < $bytes) {
			$chunk = $stream->read($bytes - strlen($data));
			if ($chunk === '') {
				throw new TIOException('binarystream_unexpected_eof', $bytes, strlen($data));
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Returns the owner stream.
	 * @throws TIOException When the owner is not a stream.
	 * @return StreamInterface The owner stream.
	 */
	private function stream(): StreamInterface
	{
		$owner = $this->getOwner();
		if (!($owner instanceof StreamInterface)) {
			throw new TIOException('binarystream_no_stream_owner');
		}
		return $owner;
	}
}
