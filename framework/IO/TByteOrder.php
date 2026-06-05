<?php

/**
 * TByteOrder class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TEnumerable;
use Prado\Util\Helpers\TBitHelper;

/**
 * TByteOrder class.
 *
 * Enumerates the two byte orders (endianness) used when packing and unpacking
 * binary data: {@see LittleEndian} (value 0) and {@see BigEndian} (value 1, also
 * called network byte order).  A null byte order means "use the running machine's
 * order", resolved by {@see resolve()} / {@see native()}.
 *
 * {@see \Prado\IO\Behavior\TBinaryStreamBehavior} uses it to choose the
 * {@see pack()}/{@see unpack()} format for multi-byte integers and floats.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TByteOrder extends TEnumerable
{
	public const LittleEndian = 0;
	public const BigEndian = 1;

	/**
	 * Returns the running machine's byte order.
	 * @return int {@see LittleEndian} or {@see BigEndian}.
	 */
	public static function native(): int
	{
		return TBitHelper::isSystemBigEndian() ? self::BigEndian : self::LittleEndian;
	}

	/**
	 * Resolves a byte order, treating null as the {@see native()} machine order.
	 * @param ?int $order A byte-order constant, or null for the machine order.
	 * @return int The concrete byte order ({@see LittleEndian} or {@see BigEndian}).
	 */
	public static function resolve(?int $order): int
	{
		return $order ?? static::native();
	}

	/**
	 * Indicates whether a byte order is big-endian (null resolves to the machine order).
	 * @param ?int $order A byte-order constant, or null for the machine order.
	 * @return bool Whether the resolved order is {@see BigEndian}.
	 */
	public static function isBigEndian(?int $order = null): bool
	{
		return static::resolve($order) === self::BigEndian;
	}
}
