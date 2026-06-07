<?php

/**
 * TBitReader class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Util;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Helpers\TBitHelper;

/**
 * TBitReader class.
 *
 * Reads a field of any bit width from a PSR-7 {@see \Psr\Http\Message\StreamInterface} and
 * decodes it as an unsigned integer, a signed integer, or a float, per a
 * {@see TBitFieldFormat}.  A field width runs from 0 to PHP_INT_SIZE * 8 bits.
 *
 * A field crosses byte boundaries: {@see readBits()} draws whole bytes from the stream into
 * the buffer and leaves the leftover bits for the next read, so a 12-bit field consumes two
 * source bytes and buffers the remaining 4.  Bit order, byte order, and the float-conversion
 * scaling come from {@see TBaseBitStream}; the reader honors them as follows:
 *
 * | Configuration | Effect on a read |
 * |---------------|------------------|
 * | LSBFirst      | Each source byte is mirrored, so the field reads least-significant-bit first. Default is most-significant-bit first. |
 * | ByteOrder     | A whole-byte field (16/24/.../64 bits) is byte-reversed when the order is little-endian. |
 * | FloatConvert  | A float field is read from its integer range back to a normalized [0, 1] value. |
 *
 * {@see readBits()} returns false when the stream ends before the whole field is available;
 * the bits consumed by the failed read are not recoverable from a non-seekable stream.  An
 * unsigned field of the full integer width whose top bit is set returns a negative PHP
 * integer holding the raw bit pattern, since PHP has no unsigned integer type.
 *
 * ```php
 * $r = new TBitReader(TStream::fromString("\xAB\xCD"));
 * $hi = $r->readBits(4);                            // 0xA
 * $lo = $r->readBits(12);                           // 0xBCD
 * $n  = $r->readBits(8, TBitFieldFormat::Signed);   // sign-extended to a PHP int
 * ```
 *
 * The reader pulls whole bytes ahead of the consumed bit position, so do not interleave bit
 * reads with direct reads of the same stream.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBitReader extends TBaseBitStream
{
	/**
	 * Reads and discards bits until {@see getCurrentBitIndex()} is a multiple of the alignment,
	 * advancing past a byte (or wider) boundary.  An index already on the boundary reads
	 * nothing and returns true; an alignBits below 1 reads nothing and returns false.
	 * @param int $alignBits The bit boundary to align to; 8 aligns to a byte. Default 8.
	 * @return bool True when the boundary is reached, false when the stream ends first or alignBits is below 1.
	 */
	public function align(int $alignBits = 8): bool
	{
		if ($alignBits < 1) {
			return false;
		}
		$n = ($alignBits - ($this->getCurrentBitIndex() % $alignBits)) % $alignBits;
		while ($n > 0) {
			$take = min(PHP_INT_SIZE * 8, $n);
			if ($this->readBits($take) === false) {
				return false;
			}
			$n -= $take;
		}
		return true;
	}

	/**
	 * Reads and decodes a field of bits.
	 *
	 * When the stream ends before the whole field is available the read returns false; the
	 * bits consumed by the failed read are not recoverable from a non-seekable stream.  An
	 * unsigned field of the full integer width whose top bit is set returns a negative PHP
	 * integer holding the raw bit pattern (PHP has no unsigned integer type).
	 *
	 * @param int $numBits The number of bits to read, from 0 to PHP_INT_SIZE * 8.
	 * @param int $format A {@see TBitFieldFormat} constant. Default TBitFieldFormat::Unsigned.
	 * @throws TInvalidDataValueException When the bit count is out of range, or a float width is not 8/16/32/64.
	 * @return false|float|int The decoded value, or false when the stream ends before the bits are read.
	 */
	public function readBits(int $numBits, int $format = TBitFieldFormat::Unsigned): false|int|float
	{
		if ($numBits < 0 || $numBits > PHP_INT_SIZE * 8) {
			if (PHP_INT_SIZE === 4 && $numBits > 32 && $numBits <= 64) {
				throw new TInvalidDataValueException('bitreader_64bit_php_required', $numBits);
			}
			throw new TInvalidDataValueException('bitreader_invalid_bits', $numBits);
		}
		if ($numBits === 0) {
			return 0;
		}

		$lsbFirst = $this->getLSBFirst();

		// Fast path: a byte-aligned buffer and a whole-byte field read and pack in one step,
		// skipping the bit-by-bit consume loop (an aligned 64-bit read is one pass).
		if ($this->getBitCountDirect() === 0 && ($numBits & 7) === 0) {
			$chunk = $this->readExact($numBits >> 3);
			if ($chunk === null) {
				return false;
			}
			$value = $this->applyByteOrder($this->packChunk($chunk, $lsbFirst), $numBits);
			$this->setCurrentBitIndexDirect($this->getCurrentBitIndexDirect() + $numBits);
			return $this->decode($value, $numBits, $format);
		}

		$buffer = $this->getByteBufferDirect();
		$count = $this->getBitCountDirect();
		$saveBuffer = $buffer;
		$saveCount = $count;
		$stream = $this->getStreamDirect();
		$value = 0;
		$remaining = $numBits;
		while ($remaining > 0) {
			if ($count === 0) {
				// Fill the buffer with as many bytes as this read needs, in one stream
				// read, capped at PHP_INT_SIZE so the packed bits fit a single integer.
				$want = min(PHP_INT_SIZE, intdiv($remaining + 7, 8));
				$chunk = $stream->read($want);
				if ($chunk === '') {
					$this->setByteBufferDirect($saveBuffer);
					$this->setBitCountDirect($saveCount);
					return false;
				}
				$count = strlen($chunk) * 8;
				$buffer = $this->packChunk($chunk, $lsbFirst);
			}
			// Consume in <= 8-bit steps so the shifts and masks never reach the integer width.
			$take = min($remaining, $count, 8);
			$shift = $count - $take;
			$bits = ($buffer >> $shift) & ((1 << $take) - 1);
			$value = ($value << $take) | $bits;
			$count -= $take;
			$buffer &= (1 << $count) - 1;
			$remaining -= $take;
		}
		$this->setByteBufferDirect($buffer);
		$this->setBitCountDirect($count);
		$this->setCurrentBitIndexDirect($this->getCurrentBitIndexDirect() + $numBits);

		$value = $this->applyByteOrder($value, $numBits);
		return $this->decode($value, $numBits, $format);
	}

	/**
	 * Reads exactly $bytes from the stream, looping over short reads.
	 * @param int $bytes The number of bytes to read.
	 * @return ?string The bytes, or null when the stream ends before $bytes are read.
	 */
	private function readExact(int $bytes): ?string
	{
		$stream = $this->getStreamDirect();
		$data = $stream->read($bytes);
		while (strlen($data) < $bytes) {
			$more = $stream->read($bytes - strlen($data));
			if ($more === '') {
				return null;
			}
			$data .= $more;
		}
		return $data;
	}

	/**
	 * Packs up to PHP_INT_SIZE bytes into one big-endian integer for the bit buffer, using the
	 * narrowest {@see unpack()} format for the chunk length (ord for 1 byte, 'n' for 2, 'N' over
	 * a zero-prefixed byte for 3, 'N' for 4, 'J' over a zero-prefixed chunk for 5 to 8), which is
	 * faster than a byte loop or a pad-to-8 unpack.  The 4-byte case uses 'N' on a 64-bit build
	 * and two 16-bit halves on a 32-bit build, where unpack('N') returns a float for a high-bit
	 * value and the halves keep it in integer arithmetic.  Under {@see getLSBFirst() LSBFirst} the result is
	 * mirrored in one pass by {@see TBitHelper::mirrorByte()}, which reverses the bits within
	 * every byte of the whole word at once.  A chunk of 5 to 8 bytes occurs only on a 64-bit build.
	 * @param string $chunk The 1..PHP_INT_SIZE bytes read from the stream.
	 * @param bool $lsbFirst Whether each byte is mirrored to least-significant-bit-first order.
	 * @return int The bytes packed most-significant-byte first.
	 */
	private function packChunk(string $chunk, bool $lsbFirst): int
	{
		$buffer = match (strlen($chunk)) {
			1 => ord($chunk),
			2 => unpack('n', $chunk)[1],
			3 => unpack('N', "\x00" . $chunk)[1],
			4 => PHP_INT_SIZE === 8 ? unpack('N', $chunk)[1] : ((($p = unpack('n2', $chunk))[1] << 16) | $p[2]),
			5 => unpack('J', "\x00\x00\x00" . $chunk)[1],
			6 => unpack('J', "\x00\x00" . $chunk)[1],
			7 => unpack('J', "\x00" . $chunk)[1],
			default => unpack('J', $chunk)[1],
		};
		return $lsbFirst ? TBitHelper::mirrorByte($buffer) : $buffer;
	}

	/**
	 * Decodes a raw bit value according to the requested format.
	 * @param int $value The raw bit value.
	 * @param int $numBits The field width in bits.
	 * @param int $format A {@see TBitFieldFormat} constant.
	 * @return float|int The decoded value.
	 */
	private function decode(int $value, int $numBits, int $format): int|float
	{
		if ($format === TBitFieldFormat::Signed) {
			$shift = (PHP_INT_SIZE << 3) - $numBits;
			return ($value << $shift) >> $shift;
		}
		if ($format === TBitFieldFormat::Float) {
			$float = match ($numBits) {
				8 => TBitHelper::fp8RangeToFloat($value),
				16 => TBitHelper::fp16ToFloat($value),
				32 => unpack('G', pack('N', $value))[1],
				64 => unpack('E', pack('J', $value))[1],
				default => throw new TInvalidDataValueException('bitreader_invalid_float_bits', $numBits),
			};
			if ($this->getFloatConvert()) {
				$float = floor((2 ** $numBits - 1) * min(1.0, max(0.0, $float)));
			}
			return $float;
		}
		return $value;
	}
}
