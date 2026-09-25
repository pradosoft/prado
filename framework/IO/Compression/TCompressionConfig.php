<?php

/**
 * TCompressionConfig class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

use Prado\Exceptions\TConfigurationException;
use Prado\TComponent;
use Prado\TPropertyValue;

/**
 * TCompressionConfig class.
 *
 * The compression settings of a component, gathered in one object so every component
 * that compresses carries the same properties under the same names.  A component holds
 * one through {@see TCompressionConfigTrait}.  A page state persister is one, reached from
 * a page directive:
 *
 * ```
 * <%@ StatePersister.Compression.Method="zstd" StatePersister.Compression.Level="9" %>
 * ```
 *
 * A module holding one also reads the nested `<compression>` element of its
 * configuration, as {@see TCompressionConfigTrait} describes.
 *
 * One {@see getMethod() Method} applies to a module, and {@see compress()} and
 * {@see decompress()} both run under it.  A module that stores what it compresses, such
 * as a state persister or a cache, reads back what it wrote as long as the setting holds;
 * changing the method invalidates the data already stored under the previous one.
 *
 * {@see getShouldCompress() ShouldCompress} reports that compression is on and its codec
 * runs here.  {@see shouldCompressLength()} adds the {@see getThreshold() Threshold} for a
 * consumer that can tell compressed data from uncompressed data.
 *
 * The constructor assigns each setting from its `DEFAULT_` constant through `static::`,
 * so a subclass changes a default by redeclaring the constant:
 *
 * ```php
 * class TMyCompressionConfig extends TCompressionConfig
 * {
 *     public const DEFAULT_METHOD = 'zstd';
 *     public const DEFAULT_ENABLED = true;
 * }
 * ```
 *
 * ```php
 * $compression = $module->getCompression();
 * if ($compression->shouldCompressLength(strlen($data))) {
 *     $data = $compression->compress($data);
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCompressionConfig extends TComponent
{
	/** Whether compression is on when nothing sets it. */
	public const DEFAULT_ENABLED = false;

	/**
	 * The content coding applied when none is named.  The zlib format, which is what
	 * PHP's {@see gzcompress()} writes, so data compressed by an earlier version of
	 * PRADO reads back under this default.
	 */
	public const DEFAULT_METHOD = 'deflate';

	/** The compression level when none is set; -1 is the codec's own default. */
	public const DEFAULT_LEVEL = -1;

	/** The smallest data, in bytes, that is compressed when no threshold is set. */
	public const DEFAULT_THRESHOLD = 1024;

	/** @var bool whether compression is on */
	private bool $_enabled;

	/** @var string the content-coding token applied */
	private string $_method;

	/** @var int the compression level, or -1 for the codec's default */
	private int $_level;

	/** @var int the smallest data, in bytes, that is compressed */
	private int $_threshold;

	/**
	 * Applies the `DEFAULT_` constants of the instantiated class, so a subclass that
	 * redeclares one starts from its own value.  A property initializer cannot do this:
	 * `static::` is not allowed in a compile-time constant, and `self::` would bind the
	 * constants of this class to every subclass.
	 */
	public function __construct()
	{
		$this->_enabled = static::DEFAULT_ENABLED;
		$this->_method = static::DEFAULT_METHOD;
		$this->_level = static::DEFAULT_LEVEL;
		$this->_threshold = static::DEFAULT_THRESHOLD;
		parent::__construct();
	}

	/**
	 * @return bool whether compression is on. Defaults to {@see DEFAULT_ENABLED}.
	 */
	public function getEnabled(): bool
	{
		return $this->_enabled;
	}

	/**
	 * @param bool|string $value whether compression is on.
	 */
	public function setEnabled($value): void
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the content-coding token applied. Defaults to {@see DEFAULT_METHOD}.
	 */
	public function getMethod(): string
	{
		return $this->_method;
	}

	/**
	 * Sets the content coding applied, one of the tokens of {@see TCompression}.  A
	 * module that stores what it compresses reads back only what the current method
	 * wrote, so a change to this setting invalidates the data already stored.
	 * @param string $value the content-coding token.
	 * @throws TConfigurationException if the token is not a known content coding.
	 */
	public function setMethod($value): void
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if (TCompression::getCodec($value) === null) {
			throw new TConfigurationException('compressionconfig_method_unknown', $value, implode(', ', TCompression::getMethods()));
		}
		$this->_method = $value;
	}

	/**
	 * @return int the compression level passed to the codec. Defaults to {@see DEFAULT_LEVEL}.
	 */
	public function getLevel(): int
	{
		return $this->_level;
	}

	/**
	 * Sets the compression level passed to the codec.  The meaningful range belongs to
	 * the coding (0..9 for gzip and deflate, 1..22 for zstd); a level outside a codec's
	 * range falls back to that codec's default.
	 * @param int|string $value the compression level, or -1 for the codec's default.
	 */
	public function setLevel($value): void
	{
		$this->_level = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return int the smallest data, in bytes, that is compressed. Defaults to {@see DEFAULT_THRESHOLD}.
	 */
	public function getThreshold(): int
	{
		return $this->_threshold;
	}

	/**
	 * Sets the smallest data that is compressed.  Shorter data costs more in CPU and in
	 * the coding's own framing than it saves.
	 * @param int|string $value the threshold in bytes; a negative value becomes 0.
	 */
	public function setThreshold($value): void
	{
		$this->_threshold = max(0, TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return bool whether the codec of {@see getMethod() Method} runs in this PHP installation.
	 */
	public function getIsAvailable(): bool
	{
		return TCompression::isAvailable($this->getMethod());
	}

	/**
	 * @return bool whether compression is on and its codec runs here.
	 */
	public function getShouldCompress(): bool
	{
		return $this->getEnabled() && $this->getIsAvailable();
	}

	/**
	 * Returns whether data of a given length is compressed: compression is on, its codec
	 * runs here, and the length reaches {@see getThreshold() Threshold}.
	 * @param int $length the length of the data in bytes.
	 * @return bool whether the data is compressed.
	 */
	public function shouldCompressLength(int $length): bool
	{
		return $this->getShouldCompress() && $length >= $this->getThreshold();
	}

	/**
	 * Compresses data under {@see getMethod() Method} at {@see getLevel() Level}.
	 * @param string $data the raw bytes.
	 * @throws \Prado\Exceptions\TIOException if the codec is unavailable or the call fails.
	 * @return string the compressed bytes.
	 */
	public function compress(string $data): string
	{
		return TCompression::compress($data, $this->getMethod(), $this->getLevel());
	}

	/**
	 * Decompresses data that {@see compress()} produced under the current
	 * {@see getMethod() Method}.
	 * @param string $data the compressed bytes.
	 * @throws \Prado\Exceptions\TIOException if the codec is unavailable or the data is corrupt.
	 * @return string the raw bytes.
	 */
	public function decompress(string $data): string
	{
		return TCompression::decompress($data, $this->getMethod());
	}
}
