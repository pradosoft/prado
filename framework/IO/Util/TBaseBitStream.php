<?php

/**
 * TBaseBitStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Util;

use Prado\IO\TByteOrder;
use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

/**
 * TBaseBitStream class.
 *
 * The shared base for bit-level access to a PSR-7 {@see StreamInterface}.  It carries the
 * underlying stream, the configuration both directions use, the running bit position, and
 * the partial-byte buffer that lets a field cross byte boundaries.  {@see TBitReader} adds
 * bit decoding; {@see TBitWriter} adds bit encoding and flushing.
 *
 * Configuration:
 *
 * | Property                            | Effect                                                                                          |
 * |-------------------------------------|-------------------------------------------------------------------------------------------------|
 * | {@see getLSBFirst() LSBFirst}       | When true, each byte is mirrored so bits are processed least-significant first.  Default false (most-significant first). |
 * | {@see getByteOrder() ByteOrder}     | The byte order of whole-byte (8, 16, 24, ..., 64-bit) fields, a {@see \Prado\IO\TByteOrder} constant; null follows the machine's native order.  Default {@see \Prado\IO\TByteOrder::BigEndian}. |
 * | {@see getFloatConvert() FloatConvert} | When true, a float field is scaled to and from the integer range of its width, mapping a normalized [0, 1] value onto 0..2**numBits-1.  Default false. |
 *
 * Bit order and byte order are independent.  LSBFirst flips the order of bits within each
 * byte.  ByteOrder reorders whole bytes within a multi-byte field, and applies only to a
 * width that is a whole number of bytes.  Values assemble most-significant-byte first, which
 * is naturally big-endian, so a big-endian order leaves them unchanged; the reordering is
 * arithmetic and host-independent.
 *
 * The reader pulls whole bytes from the stream into the buffer, so the stream position runs
 * ahead of {@see getCurrentBitIndex() the consumed bit index}; do not interleave bit access
 * with direct byte access of the same stream.  Each direction supplies its own
 * {@see TBitReader::align()}/{@see TBitWriter::align()}: reading discards bits to reach a
 * boundary, writing pads with zero bits.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseBitStream extends TComponent
{
	/** @var StreamInterface The underlying PSR-7 stream. */
	private StreamInterface $_stream;

	/** @var bool When true, each byte is bit-mirrored as it is read from or written to the stream, so fields are processed least-significant-bit first. Default false (most-significant-bit first). */
	private bool $_lsbFirst = false;

	/** @var ?int The byte order of whole-byte (8, 16, 24, ..., 64-bit) fields in the stream (a {@see TByteOrder} constant); null follows the machine's native order. Default {@see TByteOrder::BigEndian}. */
	private ?int $_byteOrder = TByteOrder::BigEndian;

	/** @var bool Cached resolution of whether {@see getByteOrder() ByteOrder} is little-endian, recomputed by {@see setByteOrderDirect()}. Default false (matching the big-endian default). */
	private bool $_littleEndian = false;

	/** @var bool When true, a {@see TBitFieldFormat::Float} field is scaled by its width: a write divides the value by 2**numBits-1, a read multiplies the decoded [0, 1] value back by it. The caller works in 0..2**numBits-1 units while the stream holds a normalized fraction. Other formats are unaffected. Default false. */
	private bool $_floatConvert = false;

	/** @var int The total bits read or written since construction; each field advances it by its width, and {@see align()} uses it to find the next boundary. */
	private int $_currentBitIndex = 0;

	/** @var int The leftover bits between fields, held most-significant first: bits pulled from the stream but not yet consumed (reader), or bits accumulated toward the next byte (writer). Fewer than 8 bits remain between fields. */
	private int $_byteBuffer = 0;

	/** @var int The number of valid bits currently held in {@see $_byteBuffer}. */
	private int $_bitCount = 0;

	/**
	 * @param StreamInterface $stream The underlying PSR-7 stream.
	 */
	public function __construct(StreamInterface $stream)
	{
		$this->setStreamDirect($stream);
		parent::__construct();
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw stream.
	 * @return StreamInterface The raw stream.
	 */
	protected function getStreamDirect(): StreamInterface
	{
		return $this->_stream;
	}

	/**
	 * Sets the raw stream.
	 * @param StreamInterface $value The raw stream.
	 */
	protected function setStreamDirect(StreamInterface $value): void
	{
		$this->_stream = $value;
	}

	/**
	 * Returns the raw LSB-first flag.
	 * @return bool The raw LSB-first flag.
	 */
	protected function getLSBFirstDirect(): bool
	{
		return $this->_lsbFirst;
	}

	/**
	 * Sets the raw LSB-first flag.
	 * @param bool $value The raw LSB-first flag.
	 */
	protected function setLSBFirstDirect(bool $value): void
	{
		$this->_lsbFirst = $value;
	}

	/**
	 * Returns the raw byte order.
	 * @return ?int The raw byte order (a {@see TByteOrder} constant), or null for native order.
	 */
	protected function getByteOrderDirect(): ?int
	{
		return $this->_byteOrder;
	}

	/**
	 * Sets the raw byte order and refreshes the cached little-endian resolution that
	 * {@see applyByteOrder()} reads on every field.
	 * @param ?int $value The raw byte order (a {@see TByteOrder} constant), or null for native order.
	 */
	protected function setByteOrderDirect(?int $value): void
	{
		$this->_byteOrder = $value;
		$this->_littleEndian = (($value ?? TByteOrder::native()) === TByteOrder::LittleEndian);
	}

	/**
	 * Returns the raw float-convert flag.
	 * @return bool The raw float-convert flag.
	 */
	protected function getFloatConvertDirect(): bool
	{
		return $this->_floatConvert;
	}

	/**
	 * Sets the raw float-convert flag.
	 * @param bool $value The raw float-convert flag.
	 */
	protected function setFloatConvertDirect(bool $value): void
	{
		$this->_floatConvert = $value;
	}

	/**
	 * Returns the raw current bit index.
	 * @return int The raw current bit index.
	 */
	protected function getCurrentBitIndexDirect(): int
	{
		return $this->_currentBitIndex;
	}

	/**
	 * Sets the raw current bit index.
	 * @param int $value The raw current bit index.
	 */
	protected function setCurrentBitIndexDirect(int $value): void
	{
		$this->_currentBitIndex = $value;
	}

	/**
	 * Returns the raw byte buffer.
	 * @return int The raw byte buffer.
	 */
	protected function getByteBufferDirect(): int
	{
		return $this->_byteBuffer;
	}

	/**
	 * Sets the raw byte buffer.
	 * @param int $value The raw byte buffer.
	 */
	protected function setByteBufferDirect(int $value): void
	{
		$this->_byteBuffer = $value;
	}

	/**
	 * Returns the raw bit count.
	 * @return int The raw bit count.
	 */
	protected function getBitCountDirect(): int
	{
		return $this->_bitCount;
	}

	/**
	 * Sets the raw bit count.
	 * @param int $value The raw bit count.
	 */
	protected function setBitCountDirect(int $value): void
	{
		$this->_bitCount = $value;
	}

	//
	// ─── Property accessors ──────────────────────────────────────────────────
	//

	/**
	 * Returns the underlying stream.
	 * @return StreamInterface The underlying PSR-7 stream.
	 */
	public function getStream(): StreamInterface
	{
		return $this->getStreamDirect();
	}

	/**
	 * Returns whether each byte is processed least-significant-bit first.  When true, every
	 * {@see TBitReader::readBits()} mirrors each source byte after reading it, and every
	 * {@see TBitWriter::writeBits()} mirrors each completed byte before writing it.
	 * @return bool True when bytes are processed LSB first.
	 */
	public function getLSBFirst(): bool
	{
		return $this->getLSBFirstDirect();
	}

	/**
	 * Sets whether each byte is processed least-significant-bit first.  It takes effect on the
	 * next field: a byte is bit-mirrored on the way in when reading and on the way out when
	 * writing.  The default, false, processes the most-significant bit of each byte first.
	 * @param bool $value True to process each byte LSB first.
	 */
	public function setLSBFirst(bool $value): void
	{
		$this->setLSBFirstDirect($value);
	}

	/**
	 * Returns the byte order of multi-byte fields in the stream.
	 * @return ?int A {@see TByteOrder} constant ({@see TByteOrder::BigEndian} or
	 *   {@see TByteOrder::LittleEndian}), or null for the machine's native order.
	 */
	public function getByteOrder(): ?int
	{
		return $this->getByteOrderDirect();
	}

	/**
	 * Sets the byte order of multi-byte fields in the stream.  {@see TByteOrder::BigEndian}
	 * matches the natural most-significant-byte-first assembly and leaves values unchanged;
	 * {@see TByteOrder::LittleEndian} byte-reverses whole-byte fields; null follows the
	 * machine's native order.
	 * @param ?int $value A {@see TByteOrder} constant, or null for the machine's native order.
	 */
	public function setByteOrder(?int $value): void
	{
		$this->setByteOrderDirect($value);
	}

	/**
	 * Returns whether a float field is scaled by its width.  It applies only to a
	 * {@see TBitFieldFormat::Float} field: reading maps the decoded [0, 1] value up to
	 * 0..2**numBits-1, writing maps a value in that range down to a stored fraction.
	 * @return bool True when float conversion is enabled.
	 */
	public function getFloatConvert(): bool
	{
		return $this->getFloatConvertDirect();
	}

	/**
	 * Sets whether a float field is scaled by its width.  When true, a
	 * {@see TBitFieldFormat::Float} write divides the value by 2**numBits-1 before encoding
	 * and a read multiplies the decoded [0, 1] value back by it; other formats are unaffected.
	 * The default is false (the raw IEEE float).
	 * @param bool $value True to scale a float field by its range.
	 */
	public function setFloatConvert(bool $value): void
	{
		$this->setFloatConvertDirect($value);
	}

	/**
	 * Returns the total number of bits read or written since construction.  Each field
	 * advances it by its width, and {@see TBitReader::align()}/{@see TBitWriter::align()} read
	 * it to find the next boundary.  The reader buffers whole bytes, so the underlying stream
	 * position runs ahead of this index.  It has no setter; the read and write paths maintain it.
	 * @return int The current bit index.
	 */
	public function getCurrentBitIndex(): int
	{
		return $this->getCurrentBitIndexDirect();
	}

	/**
	 * Applies the stream's {@see getByteOrder() ByteOrder} to an assembled value, byte-reversing
	 * it when the order resolves to little-endian and the width is a whole number of bytes (16,
	 * 24, 32, 40, 48, 56, 64).  The little-endian resolution (which treats a null order as the
	 * machine's native order) is cached by {@see setByteOrderDirect()}, so this reads a flag
	 * rather than resolving the order on every field.  Bits assemble most-significant-byte
	 * first, which is naturally big-endian, so a big-endian order returns the value unchanged,
	 * as does a width that is not a byte multiple (such as 17 bits).  The 16/32/64-bit widths
	 * take a direct swap; the remaining whole-byte widths use {@see reverseBytes()}.  The
	 * reordering is arithmetic and host-independent.
	 * @param int $value The assembled value.
	 * @param int $numBits The field width in bits.
	 * @return int The value in the configured byte order.
	 */
	protected function applyByteOrder(int $value, int $numBits): int
	{
		if (!$this->_littleEndian || $numBits % 8 !== 0) {
			return $value;
		}
		return match ($numBits) {
			16 => (($value & 0xFF) << 8) | (($value >> 8) & 0xFF),
			32 => unpack('V', pack('N', $value))[1],
			64 => unpack('P', pack('J', $value))[1],
			default => $this->reverseBytes($value, intdiv($numBits, 8)),
		};
	}

	/**
	 * Byte-reverses the low $bytes bytes of a value, for the whole-byte widths (24, 40, 48, 56
	 * bits) the direct swaps in {@see applyByteOrder()} do not cover.
	 * @param int $value The value to reverse.
	 * @param int $bytes The number of low bytes to reverse.
	 * @return int The byte-reversed value.
	 */
	private function reverseBytes(int $value, int $bytes): int
	{
		$result = 0;
		for ($i = 0; $i < $bytes; $i++) {
			$result = ($result << 8) | (($value >> ($i * 8)) & 0xFF);
		}
		return $result;
	}
}
