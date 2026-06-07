<?php

/**
 * TBitFieldFormat class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Util;

use Prado\TEnumerable;

/**
 * TBitFieldFormat class.
 *
 * Enumerates how {@see \Prado\IO\Util\TBitReader} and {@see \Prado\IO\Util\TBitWriter}
 * interpret a field of bits.  A value of this enumeration is the `$format` argument of
 * {@see \Prado\IO\Util\TBitReader::readBits()} and {@see \Prado\IO\Util\TBitWriter::writeBits()}.
 *
 * | Constant | Meaning                                                                                                                  |
 * |----------|--------------------------------------------------------------------------------------------------------------------------|
 * | Unsigned | The raw bit pattern as an unsigned integer.  A field of the full integer width whose top bit is set reads as a negative PHP int, since PHP has no unsigned type. |
 * | Signed   | A two's-complement integer, sign-extended from the field's top bit.                                                       |
 * | Float    | An IEEE-style float whose width must be 8, 16, 32, or 64 bits; another width raises a {@see \Prado\Exceptions\TInvalidDataValueException}. The 8- and 16-bit forms use the {@see \Prado\Util\Helpers\TBitHelper} fp8/fp16 codecs. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBitFieldFormat extends TEnumerable
{
	public const Unsigned = 1;

	public const Signed = 2;

	public const Float = 3;
}
