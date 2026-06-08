<?php

/**
 * TBitWriter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Util;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Helpers\TBitHelper;

/**
 * TBitWriter class.
 *
 * Writes a field of any bit width to a PSR-7 {@see \Psr\Http\Message\StreamInterface},
 * encoding an unsigned integer, a signed integer, or a float, per a {@see TBitFieldFormat}.
 * A field width runs from 0 to PHP_INT_SIZE * 8 bits.
 *
 * {@see writeBits()} writes only the low $numBits of the value: a wider value is truncated,
 * and a negative integer is written as its two's-complement low bits.  Bits join the
 * partial-byte buffer and every whole byte they complete is emitted, so a field may finish
 * earlier-buffered bits and leave its own remainder (fewer than 8 bits) buffered.  A field
 * written from a byte-aligned buffer takes a one-pass fast path that packs all its bytes at
 * once.  Bit order, byte order, and the float-conversion scaling come from
 * {@see TBaseBitStream}; the writer honors them as follows:
 *
 * | Configuration | Effect on a write |
 * |---------------|-------------------|
 * | LSBFirst      | Each completed byte is mirrored before it is written, so the field is emitted least-significant-bit first. Default is most-significant-bit first. |
 * | ByteOrder     | A whole-byte field (8, 16, 24, ..., 64 bits) is byte-reversed when the order is little-endian. |
 * | FloatConvert  | A float field is scaled from a normalized [0, 1] value into its integer range before encoding. |
 *
 * {@see flush()} writes any trailing partial byte, zero-padding the unused low bits.  Those
 * pending bits reach the stream only through {@see flush()}; the writer does not flush on
 * destruction, so call it before finishing and before any direct read or write of the
 * underlying stream.
 *
 * ```php
 * $s = TStream::fromMemory();
 * $w = new TBitWriter($s);
 * $w->writeBits(0xA, 4);
 * $w->writeBits(0xBCD, 12);
 * $w->flush();                 // emits "\xAB\xCD"
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBitWriter extends TBaseBitStream
{
	/**
	 * Writes zero bits until {@see getCurrentBitIndex()} is a multiple of the alignment, padding
	 * past a byte (or wider) boundary.  An index already on the boundary and an alignBits below
	 * 1 write nothing.  A completed byte reaches the stream at once; a trailing partial byte
	 * waits for {@see flush()}.
	 * @param int $alignBits The bit boundary to align to; 8 aligns to a byte. Default 8.
	 */
	public function align(int $alignBits = 8): void
	{
		if ($alignBits < 1) {
			return;
		}
		$n = ($alignBits - ($this->getCurrentBitIndex() % $alignBits)) % $alignBits;
		while ($n > 0) {
			$take = min(PHP_INT_SIZE * 8, $n);
			$this->writeBits(0, $take);
			$n -= $take;
		}
	}

	/**
	 * Encodes and writes a field of bits.
	 *
	 * Only the low $numBits of the value are written; a value wider than $numBits is
	 * truncated, and a negative integer is written as its two's-complement low bits.
	 * Bits accumulate until a byte completes; call {@see flush()} to emit a trailing
	 * partial byte.
	 *
	 * @param float|int $value The value to write, right-aligned to the least-significant bit.
	 * @param int $numBits The number of bits to write, from 0 to PHP_INT_SIZE * 8.
	 * @param int $format A {@see TBitFieldFormat} constant. Default TBitFieldFormat::Unsigned.
	 * @throws TInvalidDataValueException When the bit count is out of range, or a float width is not 8/16/32/64.
	 */
	public function writeBits(int|float $value, int $numBits, int $format = TBitFieldFormat::Unsigned): void
	{
		if ($numBits < 0 || $numBits > PHP_INT_SIZE * 8) {
			if (PHP_INT_SIZE === 4 && $numBits > 32 && $numBits <= 64) {
				throw new TInvalidDataValueException('bitwriter_64bit_php_required', $numBits);
			}
			throw new TInvalidDataValueException('bitwriter_invalid_bits', $numBits);
		}
		if ($numBits === 0) {
			return;
		}

		$bits = $this->encode($value, $numBits, $format);
		$bits = $this->applyByteOrder($bits, $numBits);

		$buffer = $this->getByteBufferDirect();
		$count = $this->getBitCountDirect();
		$lsbFirst = $this->getLSBFirst();

		// Fast path: a byte-aligned buffer and a whole-byte field emit every byte in a single
		// pack, with no leftover (e.g. an aligned 64-bit write is one pass instead of two).
		if ($count === 0 && ($numBits & 7) === 0) {
			$word = $lsbFirst ? TBitHelper::mirrorByte($bits) : $bits;
			$this->getStreamDirect()->write(self::packBytes($word, $numBits >> 3));
			$this->setCurrentBitIndexDirect($this->getCurrentBitIndexDirect() + $numBits);
			return;
		}

		$out = '';
		$remaining = $numBits;
		// Keep the working width a byte under the integer size so the shifts and masks below
		// never reach it; each pass folds in a slice of new bits and emits all whole bytes.
		$step = PHP_INT_SIZE * 8 - 8;
		while ($remaining > 0) {
			$take = min($remaining, $step - $count);
			$buffer = ($buffer << $take) | (($bits >> ($remaining - $take)) & ((1 << $take) - 1));
			$count += $take;
			$remaining -= $take;
			$wholeBytes = $count >> 3;
			if ($wholeBytes > 0) {
				$emitBits = $wholeBytes << 3;
				$keep = $count - $emitBits;
				$word = ($buffer >> $keep) & ((1 << $emitBits) - 1);
				if ($lsbFirst) {
					$word = TBitHelper::mirrorByte($word);
				}
				$out .= self::packBytes($word, $wholeBytes);
				$buffer &= (1 << $keep) - 1;
				$count = $keep;
			}
		}
		$this->setByteBufferDirect($buffer);
		$this->setBitCountDirect($count);
		$this->setCurrentBitIndexDirect($this->getCurrentBitIndexDirect() + $numBits);
		if ($out !== '') {
			$this->getStreamDirect()->write($out);
		}
	}

	/**
	 * Packs the low $bytes bytes of a value into a big-endian string, picking the fastest
	 * {@see pack()} form per length: chr for 1, 'n' for 2, chr+'n' for 3, 'N' for 4, 'N'+chr
	 * for 5, and a tail substring of 'J' for 6 to 8.  A run of 5 to 8 bytes occurs only on a
	 * 64-bit build.  chr() masks to a byte on its own, so no explicit mask is needed.
	 * @param int $word The value whose low $bytes bytes are emitted.
	 * @param int $bytes The number of bytes to emit, from 1 to PHP_INT_SIZE.
	 * @return string The bytes, most-significant first.
	 */
	private static function packBytes(int $word, int $bytes): string
	{
		return match ($bytes) {
			1 => chr($word),
			2 => pack('n', $word),
			3 => chr($word >> 16) . pack('n', $word),
			4 => pack('N', $word),
			5 => pack('N', $word >> 8) . chr($word),
			6 => substr(pack('J', $word), 2),
			7 => substr(pack('J', $word), 1),
			default => pack('J', $word),
		};
	}

	/**
	 * Writes any pending partial byte, zero-padding the unused low bits.
	 * @return int The number of bytes written by the flush.
	 */
	public function flush(): int
	{
		$bitCount = $this->getBitCountDirect();
		if ($bitCount === 0) {
			return 0;
		}
		$this->setByteBufferDirect($this->getByteBufferDirect() << (8 - $bitCount));
		$this->setCurrentBitIndexDirect($this->getCurrentBitIndexDirect() + (8 - $bitCount));
		$this->setBitCountDirect(8);
		$this->emitByte();
		return 1;
	}

	/**
	 * Writes the completed buffer byte to the stream and resets the buffer.
	 */
	private function emitByte(): void
	{
		$byte = $this->getByteBufferDirect() & 0xFF;
		if ($this->getLSBFirst()) {
			$byte = TBitHelper::mirrorByte($byte);
		}
		$this->getStreamDirect()->write(chr($byte));
		$this->setByteBufferDirect(0);
		$this->setBitCountDirect(0);
	}

	/**
	 * Converts a value to its raw bit pattern according to the requested format.
	 * @param float|int $value The value to encode.
	 * @param int $numBits The field width in bits.
	 * @param int $format A {@see TBitFieldFormat} constant.
	 * @return int The raw bit pattern.
	 */
	private function encode(int|float $value, int $numBits, int $format): int
	{
		if ($format !== TBitFieldFormat::Float) {
			return (int) $value;
		}
		if ($this->getFloatConvert()) {
			$value = $value / (2 ** $numBits - 1);
		}
		return match ($numBits) {
			8 => TBitHelper::floatToFp8Range((float) $value),
			16 => TBitHelper::floatToFp16((float) $value),
			32 => unpack('N', pack('G', (float) $value))[1],
			64 => unpack('J', pack('E', (float) $value))[1],
			default => throw new TInvalidDataValueException('bitwriter_invalid_float_bits', $numBits),
		};
	}
}
