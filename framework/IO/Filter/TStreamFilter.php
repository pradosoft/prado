<?php

/**
 * TStreamFilter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Filter;

use Prado\Exceptions\TIOException;
use Prado\IO\TStream;

/**
 * TStreamFilter class.
 *
 * Serves as the abstract base for custom PHP stream filters, extending the native
 * {@see \php_user_filter}.  It handles the bucket-processing loop so a subclass only
 * implements {@see convert()} to transform the data (for example compression or
 * encoding).  Register the subclass once with {@see register()}, then attach it to
 * any stream with {@see append()}/{@see prepend()} or via
 * {@see \Prado\IO\TStream::appendFilter()}.
 *
 * A subclass names itself by overriding {@see getFilterName()}:
 * ```php
 * class TRot13Filter extends TStreamFilter
 * {
 *     public static function getFilterName(): string { return 'prado.rot13'; }
 *     protected function convert(object $bucket, bool $closing): int
 *     {
 *         $bucket->data = str_rot13($bucket->data);
 *         return PSFS_PASS_ON;
 *     }
 * }
 * TRot13Filter::registerOnce();
 * $stream->appendFilter('prado.rot13', STREAM_FILTER_WRITE);
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://www.php.net/manual/en/class.php-user-filter.php
 */
abstract class TStreamFilter extends \php_user_filter
{
	/**
	 * Returns the name this filter registers under.  Subclasses override it.
	 * @return string The filter name (e.g. 'prado.rot13').
	 */
	public static function getFilterName(): string
	{
		return '';
	}

	/**
	 * Returns the default attach mode for this filter.
	 * @return int STREAM_FILTER_READ, STREAM_FILTER_WRITE, or STREAM_FILTER_ALL.
	 */
	public static function getDefaultMode(): int
	{
		return STREAM_FILTER_ALL;
	}

	/**
	 * Registers the filter class under a name (idempotent guard is {@see registerOnce()}).
	 * @param ?string $name The filter name; null uses {@see getFilterName()}.
	 * @throws TIOException When the name is empty, already registered, or registration fails.
	 */
	public static function register(?string $name = null): void
	{
		$name ??= static::getFilterName();
		if ($name === '') {
			throw new TIOException('streamfilter_registration_failed', '(empty)', static::class);
		}
		if (in_array($name, stream_get_filters(), true)) {
			throw new TIOException('streamfilter_already_registered', $name);
		}
		if (!stream_filter_register($name, static::class)) {
			throw new TIOException('streamfilter_registration_failed', $name, static::class);
		}
	}

	/**
	 * Registers the filter only if the name is not already registered in this process.
	 * @param ?string $name The filter name; null uses {@see getFilterName()}.
	 */
	public static function registerOnce(?string $name = null): void
	{
		$name ??= static::getFilterName();
		if ($name !== '' && !in_array($name, stream_get_filters(), true)) {
			static::register($name);
		}
	}

	/**
	 * Indicates whether a filter name is registered in this process ({@see stream_get_filters()}).
	 * @param ?string $name The filter name; null uses {@see getFilterName()}.
	 * @return bool Whether the name is registered.
	 */
	public static function isRegistered(?string $name = null): bool
	{
		return in_array($name ?? static::getFilterName(), stream_get_filters(), true);
	}

	/**
	 * Appends the filter to a stream ({@see stream_filter_append()}).  When $stream is a
	 * {@see TStream}, this registers the filter and delegates to {@see TStream::appendFilter()},
	 * so the returned handle is owned by and tracked on that stream; a raw resource yields an
	 * owner-less handle.
	 * @param resource|TStream $stream The target stream resource or {@see TStream}.
	 * @param ?string $name The filter name; null uses {@see getFilterName()}.
	 * @param ?int $mode The attach mode; null uses {@see getDefaultMode()}.
	 * @param mixed $params Optional parameters passed to {@see onCreate()}.
	 * @return mixed A {@see TStreamFilterHandle} (owned when $stream is a TStream), or false on failure.
	 */
	public static function append($stream, ?string $name = null, ?int $mode = null, mixed $params = null): mixed
	{
		$name ??= static::getFilterName();
		$mode ??= static::getDefaultMode();
		if ($stream instanceof TStream) {
			static::registerOnce($name);
			return $stream->appendFilter($name, $mode, $params);
		}
		$filter = $params === null
			? stream_filter_append($stream, $name, $mode)
			: stream_filter_append($stream, $name, $mode, $params);
		return $filter === false ? false : new TStreamFilterHandle($filter, $name);
	}

	/**
	 * Prepends the filter to a stream ({@see stream_filter_prepend()}).  When $stream is a
	 * {@see TStream}, this registers the filter and delegates to {@see TStream::prependFilter()},
	 * so the returned handle is owned by and tracked on that stream; a raw resource yields an
	 * owner-less handle.
	 * @param resource|TStream $stream The target stream resource or {@see TStream}.
	 * @param ?string $name The filter name; null uses {@see getFilterName()}.
	 * @param ?int $mode The attach mode; null uses {@see getDefaultMode()}.
	 * @param mixed $params Optional parameters passed to {@see onCreate()}.
	 * @return mixed A {@see TStreamFilterHandle} (owned when $stream is a TStream), or false on failure.
	 */
	public static function prepend($stream, ?string $name = null, ?int $mode = null, mixed $params = null): mixed
	{
		$name ??= static::getFilterName();
		$mode ??= static::getDefaultMode();
		if ($stream instanceof TStream) {
			static::registerOnce($name);
			return $stream->prependFilter($name, $mode, $params);
		}
		$filter = $params === null
			? stream_filter_prepend($stream, $name, $mode)
			: stream_filter_prepend($stream, $name, $mode, $params);
		return $filter === false ? false : new TStreamFilterHandle($filter, $name);
	}

	/**
	 * Removes a previously attached filter ({@see stream_filter_remove()}), by
	 * {@see TStreamFilterHandle} or raw resource.
	 * @param mixed $filter A handle from {@see append()}/{@see prepend()}, or a raw resource.
	 * @return bool Whether the filter was removed.
	 */
	public static function remove($filter): bool
	{
		if ($filter instanceof TStreamFilterHandle) {
			return $filter->remove();
		}
		return is_resource($filter) && stream_filter_remove($filter);
	}

	/**
	 * Lists the stream filters registered in this PHP process.
	 * @return array The registered filter names ({@see stream_get_filters()}).
	 */
	public static function getRegisteredFilters(): array
	{
		return stream_get_filters();
	}

	/**
	 * Processes the bucket brigade, delegating each bucket to {@see convert()}.
	 * @param mixed $in The input bucket brigade.
	 * @param mixed $out The output bucket brigade.
	 * @param int &$consumed The running count of consumed bytes.
	 * @param bool $closing Whether the stream is closing.
	 * @return int PSFS_PASS_ON, PSFS_FEED_ME, or PSFS_ERR_FATAL.
	 */
	public function filter($in, $out, &$consumed, bool $closing): int
	{
		$return = PSFS_PASS_ON;
		while ($bucket = stream_bucket_make_writeable($in)) {
			$length = $bucket->datalen;
			$result = $this->convert($bucket, $closing);
			if ($result === PSFS_ERR_FATAL) {
				return PSFS_ERR_FATAL;
			}
			$consumed += $length;
			stream_bucket_append($out, $bucket);
			$return = $result;
		}
		return $return;
	}

	/**
	 * Transforms a single bucket's data.  Subclasses implement the filtering logic
	 * and update `$bucket->data` (and `$bucket->datalen` when the length changes).
	 * @param object $bucket The bucket to transform.
	 * @param bool $closing Whether this is the final bucket of the stream.
	 * @return int PSFS_PASS_ON, PSFS_FEED_ME, or PSFS_ERR_FATAL.
	 */
	abstract protected function convert(object $bucket, bool $closing): int;

	/**
	 * Initializes the filter when PHP creates it.  Subclasses may override it.
	 * @return bool Whether initialization succeeded.
	 */
	public function onCreate(): bool
	{
		return true;
	}

	/**
	 * Cleans up when the filter is closed.  Subclasses may override it.
	 */
	public function onClose(): void
	{
	}
}
