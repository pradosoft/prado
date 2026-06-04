<?php

/**
 * TStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;
use Prado\Prado;
use Psr\Http\Message\StreamInterface;

/**
 * TStream class
 *
 * TStream is the universal byte-stream wrapper of the Prado IO layer. It wraps a
 * single PHP stream resource and implements PSR-7's {@see StreamInterface}, so a
 * TStream can be passed to any PSR-7 consumer (Guzzle, Slim, PSR-18 clients).
 *
 * A stream is readable, writable, and seekable according to the mode of the
 * resource it wraps. {@see isReadable()}, {@see isWritable()}, and
 * {@see isSeekable()} report these capabilities. {@see read()} on a non-readable
 * stream and {@see write()} on a non-writable stream throw {@see \RuntimeException},
 * per the PSR-7 contract.
 *
 * Files, memory, temp, and the standard streams are TStream instances over a fixed
 * URI and mode, created through the named constructors {@see fromFile()},
 * {@see fromMemory()}, {@see fromTemp()}, {@see fromString()},
 * {@see fromResource()}, and the {@see for()} coercion.
 *
 * Each operation has a PSR-7 name and a PHP alias:
 *  - PSR-7: read/write/seek/tell/eof/rewind/getSize/getContents.
 *  - PHP: {@see fread()}/{@see fwrite()}/{@see fseek()}/{@see ftell()}/
 *    {@see feof()}/{@see fgets()}/{@see fgetc()}/{@see fputs()}.
 * PSR getters are also Prado properties (getSize() → ->Size, getReadable() →
 * ->Readable).
 *
 * Self-encapsulation (Uniform Access Principle): capability and filter state is
 * reached through accessors. The protected {@see getReadableDirect()} family are
 * the raw accessors, so subclasses (pipes, sockets) override the public capability
 * getters without disturbing internal logic.
 *
 * Native PHP stream filters attach through {@see appendFilter()},
 * {@see prependFilter()}, and {@see removeFilter()}; compression and transforms
 * (such as zlib.inflate) apply as filters. Attached filters are inspected by name
 * or handle with {@see hasFilter()}, {@see getFilterIndex()}, {@see getFilters()},
 * and {@see getFilterNames()}.
 *
 * Events ('on' prefix), in addition to those from {@see TResource}. Each is a real
 * method taking a single mixed $param:
 *  - onEndOfFile: at the first read that hits EOF.
 *  - onSeek: after a successful reposition.
 *
 * Dynamic events ('dy' prefix, behavior interception via
 * {@see \Prado\TComponent::callBehaviorsMethod()}):
 * @method string dyRead(string $data, int $length) Filters bytes just read.
 * @method int dyWrite(int $written, string $data) Filters the write byte-count.
 * @method bool dyPreSeek(bool $allow, int $offset, int $whence) Vetoes a seek before it runs; return false to deny.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStream extends TResource implements StreamInterface
{
	/** @var bool Whether the stream can be read from. */
	private bool $_readable = false;

	/** @var bool Whether the stream can be written to. */
	private bool $_writable = false;

	/** @var bool Whether the stream can be sought. */
	private bool $_seekable = false;

	/** @var ?int Cached size in bytes; null when unknown/invalidated. */
	private ?int $_size = null;

	/** @var ?string The stream URI from metadata, or null. */
	private ?string $_uri = null;

	/** @var array<int, array{name: string, filter: resource}> Attached filters, in stack order (front first). */
	private array $_filters = [];

	/**
	 * Adopts an open stream resource and derives its capabilities from the mode.
	 * @param mixed $resource An open PHP stream resource.
	 * @param bool $owns Whether this object should close the handle. Default true.
	 */
	public function attachResource(mixed $resource, bool $owns = true): void
	{
		parent::attachResource($resource, $owns);
		$this->detectCapabilities();
	}

	/**
	 * Cloning yields a non-owning view of the same handle (see {@see TResource::__clone()}).
	 * The clone does not adopt the original's filter registrations.
	 */
	public function __clone()
	{
		parent::__clone();
		$this->setFiltersDirect([]);
	}

	/**
	 * Derives readable/writable/seekable from the stream mode and metadata.
	 * A mode containing 'r' or '+' is readable; a mode containing 'w', 'a', 'x',
	 * 'c', or '+' is writable; seekability comes from the stream metadata.
	 */
	protected function detectCapabilities(): void
	{
		$mode = (string) ($this->getMetadata('mode') ?? '');
		$this->setReadableDirect(strpbrk($mode, 'r+') !== false);
		$this->setWritableDirect(strpbrk($mode, 'waxc+') !== false);
		$this->setSeekableDirect((bool) $this->getMetadata('seekable'));
		$uri = $this->getMetadata('uri');
		$this->setURIDirect(is_string($uri) ? $uri : null);
		$this->setSizeDirect(null);
	}

	/**
	 * Closes the stream and resets capability/filter state.
	 * @return ?bool The close result, or null when nothing was open.
	 */
	public function closeStream(): ?bool
	{
		$result = parent::closeStream();
		$this->resetStreamState();
		return $result;
	}

	/**
	 * Severs the resource and resets capability/filter state.
	 * @see StreamInterface::detach()
	 * @return mixed The detached resource, or null.
	 */
	public function detach(): mixed
	{
		$resource = parent::detach();
		$this->resetStreamState();
		return $resource;
	}

	/**
	 * Clears the cached capabilities, size, URI and filter handles.
	 */
	private function resetStreamState(): void
	{
		$this->setFiltersDirect([]);
		$this->setReadableDirect(false);
		$this->setWritableDirect(false);
		$this->setSeekableDirect(false);
		$this->setSizeDirect(null);
		$this->setURIDirect(null);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw readable flag.
	 * @return bool The raw readable flag.
	 */
	protected function getReadableDirect(): bool
	{
		return $this->_readable;
	}

	/**
	 * Sets the raw readable flag.
	 * @param bool $value The raw readable flag.
	 */
	protected function setReadableDirect(bool $value): void
	{
		$this->_readable = $value;
	}

	/**
	 * Returns the raw writable flag.
	 * @return bool The raw writable flag.
	 */
	protected function getWritableDirect(): bool
	{
		return $this->_writable;
	}

	/**
	 * Sets the raw writable flag.
	 * @param bool $value The raw writable flag.
	 */
	protected function setWritableDirect(bool $value): void
	{
		$this->_writable = $value;
	}

	/**
	 * Returns the raw seekable flag.
	 * @return bool The raw seekable flag.
	 */
	protected function getSeekableDirect(): bool
	{
		return $this->_seekable;
	}

	/**
	 * Sets the raw seekable flag.
	 * @param bool $value The raw seekable flag.
	 */
	protected function setSeekableDirect(bool $value): void
	{
		$this->_seekable = $value;
	}

	/**
	 * Returns the raw cached size.
	 * @return ?int The raw cached size.
	 */
	protected function getSizeDirect(): ?int
	{
		return $this->_size;
	}

	/**
	 * Sets the raw cached size.
	 * @param ?int $value The raw cached size.
	 */
	protected function setSizeDirect(?int $value): void
	{
		$this->_size = $value;
	}

	/**
	 * Returns the raw URI.
	 * @return ?string The raw URI.
	 */
	protected function getURIDirect(): ?string
	{
		return $this->_uri;
	}

	/**
	 * Sets the raw URI.
	 * @param ?string $value The raw URI.
	 */
	protected function setURIDirect(?string $value): void
	{
		$this->_uri = $value;
	}

	/**
	 * Returns the raw attached-filter list (name/handle entries, in stack order).
	 * @return array<int, array{name: string, filter: resource}> The raw filter list.
	 */
	protected function getFiltersDirect(): array
	{
		return $this->_filters;
	}

	/**
	 * Sets the raw attached-filter list.
	 * @param array<int, array{name: string, filter: resource}> $value The raw filter list.
	 */
	protected function setFiltersDirect(array $value): void
	{
		$this->_filters = $value;
	}

	//
	// ─── PSR-7 StreamInterface ───────────────────────────────────────────────
	//

	/**
	 * Reads the whole stream to a string, swallowing throwables (PSR-7 1.x contract).
	 * @see StreamInterface::__toString()
	 * @return string The entire stream contents, or '' on any error.
	 */
	public function __toString(): string
	{
		try {
			if ($this->getSeekable()) {
				$this->seekTo(0);
			}
			return $this->getContents();
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Closes the stream (PSR-7). Use {@see closeStream()} for the boolean result.
	 * @see StreamInterface::close()
	 */
	public function close(): void
	{
		$this->closeStream();
	}

	/**
	 * Returns the size of the stream in bytes.
	 * @see StreamInterface::getSize()
	 * @return ?int The size in bytes, or null when indeterminable.
	 */
	public function getSize(): ?int
	{
		$size = $this->getSizeDirect();
		if ($size !== null) {
			return $size;
		}
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return null;
		}
		$stat = fstat($resource);
		if ($stat !== false && isset($stat[7])) {
			$this->setSizeDirect($stat[7]);
			return $stat[7];
		}
		return null;
	}

	/**
	 * Returns the current position of the read/write pointer.
	 * @see StreamInterface::tell()
	 * @throws \RuntimeException When the position cannot be determined.
	 * @return int The current position of the read/write pointer.
	 */
	public function tell(): int
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			throw new \RuntimeException('Cannot tell position of a detached stream');
		}
		$pos = ftell($resource);
		if ($pos === false) {
			throw new \RuntimeException('Unable to determine stream position');
		}
		return $pos;
	}

	/**
	 * Indicates whether the stream is at end of file.
	 * @see StreamInterface::eof()
	 * @return bool Whether the stream is at the end of file.
	 */
	public function eof(): bool
	{
		$resource = $this->getResourceDirect();
		return !is_resource($resource) || feof($resource);
	}

	/**
	 * Indicates whether the stream is seekable.
	 * @see StreamInterface::isSeekable()
	 * @return bool Whether the stream is seekable.
	 */
	public function isSeekable(): bool
	{
		return $this->getSeekable();
	}

	/**
	 * Seeks to a position (PSR-7). Use {@see seekTo()} for the boolean result.
	 * @see StreamInterface::seek()
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @throws \RuntimeException When the stream is not seekable or the seek fails.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		if (!$this->seekTo($offset, $whence)) {
			throw new \RuntimeException('Unable to seek to stream position ' . $offset);
		}
	}

	/**
	 * Seeks to a position, returning success (Prado-internal companion to {@see seek()}).
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @return bool Whether the seek succeeded.
	 */
	public function seekTo(int $offset, int $whence = SEEK_SET): bool
	{
		$resource = $this->getResourceDirect();
		if (!$this->getSeekable() || !is_resource($resource)) {
			return false;
		}
		$allow = true;
		$this->callBehaviorsMethod('dyPreSeek', $allow, $allow, $offset, $whence);
		if ($allow === false) {
			return false;
		}
		$result = fseek($resource, $offset, $whence) === 0;
		if ($result) {
			$this->onSeek($offset);
		}
		return $result;
	}

	/**
	 * Seeks to the beginning of the stream (PSR-7).
	 * @see StreamInterface::rewind()
	 * @throws \RuntimeException When the stream is not seekable.
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * Indicates whether the stream is writable.
	 * @see StreamInterface::isWritable()
	 * @return bool Whether the stream is writable.
	 */
	public function isWritable(): bool
	{
		return $this->getWritable();
	}

	/**
	 * Writes data to the stream.
	 * @see StreamInterface::write()
	 * @param string $string The bytes to write.
	 * @throws \RuntimeException When the stream is not writable or the write fails.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		$resource = $this->getResourceDirect();
		if (!$this->getWritable()) {
			throw new \RuntimeException('Cannot write to a non-writable stream');
		}
		if (!is_resource($resource)) {
			throw new \RuntimeException('Cannot write to a detached stream');
		}
		$this->setSizeDirect(null); // size changed; invalidate cache
		$written = fwrite($resource, $string);
		if ($written === false) {
			$this->onError('write');
			throw new \RuntimeException('Unable to write to stream');
		}
		$this->callBehaviorsMethod('dyWrite', $written, $written, $string);
		return $written;
	}

	/**
	 * Indicates whether the stream is readable.
	 * @see StreamInterface::isReadable()
	 * @return bool Whether the stream is readable.
	 */
	public function isReadable(): bool
	{
		return $this->getReadable();
	}

	/**
	 * Reads up to $length bytes from the stream.
	 * @see StreamInterface::read()
	 * @param int $length The maximum number of bytes to read.
	 * @throws \RuntimeException When the stream is not readable or the read fails.
	 * @return string The bytes read (may be shorter than $length, '' at EOF).
	 */
	public function read(int $length): string
	{
		$resource = $this->getResourceDirect();
		if (!$this->getReadable()) {
			throw new \RuntimeException('Cannot read from a non-readable stream');
		}
		if (!is_resource($resource)) {
			throw new \RuntimeException('Cannot read from a detached stream');
		}
		if ($length < 0) {
			throw new \RuntimeException('Length parameter cannot be negative');
		}
		if ($length === 0) {
			return '';
		}
		$data = fread($resource, $length);
		if ($data === false) {
			$this->onError('read');
			throw new \RuntimeException('Unable to read from stream');
		}
		$this->callBehaviorsMethod('dyRead', $data, $data, $length);
		if ($data === '' && feof($resource)) {
			$this->onEndOfFile($length);
		}
		return $data;
	}

	/**
	 * Returns the remaining contents of the stream from the current position.
	 * @see StreamInterface::getContents()
	 * @throws \RuntimeException When the stream is unreadable or the read fails.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		$resource = $this->getResourceDirect();
		if (!$this->getReadable()) {
			throw new \RuntimeException('Cannot read from a non-readable stream');
		}
		if (!is_resource($resource)) {
			throw new \RuntimeException('Cannot read from a detached stream');
		}
		$contents = stream_get_contents($resource);
		if ($contents === false) {
			$this->onError('getContents');
			throw new \RuntimeException('Unable to read stream contents');
		}
		return $contents;
	}

	//
	// ─── Prado property accessors (capabilities) ─────────────────────────────
	//

	/**
	 * Indicates whether the stream is readable (the ->Readable property).
	 * @return bool Whether the stream is readable.
	 */
	public function getReadable(): bool
	{
		return $this->getReadableDirect();
	}

	/**
	 * Indicates whether the stream is writable (the ->Writable property).
	 * @return bool Whether the stream is writable.
	 */
	public function getWritable(): bool
	{
		return $this->getWritableDirect();
	}

	/**
	 * Indicates whether the stream is seekable (the ->Seekable property).
	 * @return bool Whether the stream is seekable.
	 */
	public function getSeekable(): bool
	{
		return $this->getSeekableDirect();
	}

	/**
	 * Returns the stream URI (the ->URI property).
	 * @return ?string The stream URI, or null.
	 */
	public function getURI(): ?string
	{
		return $this->getURIDirect();
	}

	//
	// ─── PHP-name aliases ────────────────────────────────────────────────────
	//

	/**
	 * Alias of {@see read()}.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function fread(int $length): string
	{
		return $this->read($length);
	}

	/**
	 * Alias of {@see write()}.
	 * @param string $data The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function fwrite(string $data): int
	{
		return $this->write($data);
	}

	/**
	 * Alias of {@see write()} (matches PHP's {@see fputs()}).
	 * @param string $data The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function fputs(string $data): int
	{
		return $this->write($data);
	}

	/**
	 * Alias of {@see seekTo()} (returns success like a boolean {@see fseek()}).
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @return bool Whether the seek succeeded.
	 */
	public function fseek(int $offset, int $whence = SEEK_SET): bool
	{
		return $this->seekTo($offset, $whence);
	}

	/**
	 * Alias of {@see tell()}.
	 * @return int The current position.
	 */
	public function ftell(): int
	{
		return $this->tell();
	}

	/**
	 * Alias of {@see eof()}.
	 * @return bool Whether the stream is at end of file.
	 */
	public function feof(): bool
	{
		return $this->eof();
	}

	/**
	 * Reads a line from the stream (PHP {@see fgets()} semantics).  It reads the raw
	 * handle directly, bypassing the {@see read()} capability check and dy behaviors.
	 * @param ?int $length Max bytes to read (including newline); null reads to EOL.
	 * @return false|string The line, or false at EOF/on error.
	 */
	public function fgets(?int $length = null): false|string
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		return $length === null ? fgets($resource) : fgets($resource, $length);
	}

	/**
	 * Reads a single byte from the stream (PHP {@see fgetc()} semantics).  It reads the
	 * raw handle directly, bypassing the {@see read()} capability check and dy behaviors.
	 * @return false|string The byte read, or false at EOF.
	 */
	public function fgetc(): false|string
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		return fgetc($resource);
	}

	//
	// ─── Stream filters (native PHP) ─────────────────────────────────────────
	//

	/**
	 * Appends a filter to this stream ({@see stream_filter_append()}).  The
	 * returned handle is retained so it can be passed to {@see removeFilter()}.
	 * @param string $name The registered filter name (e.g. 'zlib.inflate').
	 * @param int $mode STREAM_FILTER_READ, STREAM_FILTER_WRITE or STREAM_FILTER_ALL.
	 * @param mixed $params Optional parameters passed to the filter.
	 * @return mixed The filter handle, or false on failure.
	 */
	public function appendFilter(string $name, int $mode = STREAM_FILTER_ALL, mixed $params = null): mixed
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		$filter = $params === null
			? stream_filter_append($resource, $name, $mode)
			: stream_filter_append($resource, $name, $mode, $params);
		if ($filter !== false) {
			$filters = $this->getFiltersDirect();
			$filters[] = ['name' => $name, 'filter' => $filter];
			$this->setFiltersDirect($filters);
		}
		return $filter;
	}

	/**
	 * Prepends a filter to this stream ({@see stream_filter_prepend()}).
	 * @param string $name The registered filter name.
	 * @param int $mode STREAM_FILTER_READ, STREAM_FILTER_WRITE or STREAM_FILTER_ALL.
	 * @param mixed $params Optional parameters passed to the filter.
	 * @return mixed The filter handle, or false on failure.
	 */
	public function prependFilter(string $name, int $mode = STREAM_FILTER_ALL, mixed $params = null): mixed
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		$filter = $params === null
			? stream_filter_prepend($resource, $name, $mode)
			: stream_filter_prepend($resource, $name, $mode, $params);
		if ($filter !== false) {
			$filters = $this->getFiltersDirect();
			array_unshift($filters, ['name' => $name, 'filter' => $filter]);
			$this->setFiltersDirect($filters);
		}
		return $filter;
	}

	/**
	 * Removes a previously appended/prepended filter ({@see stream_filter_remove()}).
	 * @param mixed $filter The filter handle returned by append/prepend.
	 * @return bool Whether the filter was removed.
	 */
	public function removeFilter(mixed $filter): bool
	{
		if (!is_resource($filter)) {
			return false;
		}
		$result = stream_filter_remove($filter);
		if ($result) {
			$filters = $this->getFiltersDirect();
			foreach ($filters as $key => $entry) {
				if ($entry['filter'] === $filter) {
					unset($filters[$key]);
					break;
				}
			}
			$this->setFiltersDirect(array_values($filters));
		}
		return $result;
	}

	/**
	 * Returns the filter handles attached to this stream, in stack order (front first).
	 * @return array<int, resource> The attached filter handles.
	 */
	public function getFilters(): array
	{
		return array_map(static fn (array $entry) => $entry['filter'], $this->getFiltersDirect());
	}

	/**
	 * Returns the names of the filters attached to this stream, in stack order.
	 * @return array<int, string> The attached filter names.
	 */
	public function getFilterNames(): array
	{
		return array_map(static fn (array $entry) => $entry['name'], $this->getFiltersDirect());
	}

	/**
	 * Indicates whether a filter is attached to this stream, by name or by handle.
	 * @param resource|string $filter A filter name (e.g. 'zlib.inflate') or a handle from append/prepend.
	 * @return bool Whether a matching filter is attached.
	 */
	public function hasFilter(mixed $filter): bool
	{
		return $this->getFilterIndex($filter) !== null;
	}

	/**
	 * Returns the stack position of a filter, by name or by handle.  Index 0 is the
	 * front of the stack (the most-recently prepended); a name matches the first such
	 * filter.  Distinct from the static {@see filterExists()}, which reports whether a
	 * filter is registered in the process.
	 * @param resource|string $filter A filter name or a handle from append/prepend.
	 * @return ?int The 0-based position, or null when not attached.
	 */
	public function getFilterIndex(mixed $filter): ?int
	{
		$byName = is_string($filter);
		foreach ($this->getFiltersDirect() as $index => $entry) {
			if ($byName ? $entry['name'] === $filter : $entry['filter'] === $filter) {
				return $index;
			}
		}
		return null;
	}

	/**
	 * Lists the stream filters registered in this PHP process.
	 * @return array The filters registered in this PHP process ({@see stream_get_filters()}).
	 */
	public static function getAvailableFilters(): array
	{
		return stream_get_filters();
	}

	/**
	 * Indicates whether a named stream filter is registered in this process.
	 * @param string $name A filter name.
	 * @return bool Whether the named filter is registered in this process.
	 */
	public static function filterExists(string $name): bool
	{
		return in_array($name, stream_get_filters(), true);
	}

	//
	// ─── Named constructors & coercion ───────────────────────────────────────
	//

	/**
	 * Coerces a value into a TStream (or returns a StreamInterface unchanged).
	 *  - StreamInterface → returned as-is
	 *  - resource → wrapped (not owned)
	 *  - string/scalar → an in-memory stream containing the value
	 *  - null → an empty temp stream
	 * @param mixed $source The value to coerce.
	 * @throws TIOException When the value cannot be turned into a stream.
	 * @return StreamInterface A stream over the source.
	 */
	public static function for(mixed $source): StreamInterface
	{
		if ($source instanceof StreamInterface) {
			return $source;
		}
		if (is_resource($source)) {
			return self::fromResource($source, false);
		}
		if ($source === null) {
			return self::fromTemp();
		}
		if (is_string($source) || (is_scalar($source) && !is_bool($source)) || (is_object($source) && method_exists($source, '__toString'))) {
			return self::fromString((string) $source);
		}
		throw new TIOException('stream_open_failed', gettype($source), 'for');
	}

	/**
	 * Opens a file or stream-wrapper URI as a TStream.  Any registered wrapper is
	 * honoured (e.g. 'compress.zlib://archive.gz', 'php://filter/...', 'data://...').
	 * @param string $filename The file path or wrapper URI.
	 * @param string $mode The fopen mode. Default 'rb'.
	 * @param bool $useIncludePath Whether to search the include path. Default false.
	 * @param mixed $context An optional stream context.
	 * @throws TIOException When the file/URI cannot be opened.
	 * @return self The opened stream (owned).
	 */
	public static function fromFile(string $filename, string $mode = 'rb', bool $useIncludePath = false, mixed $context = null): self
	{
		$resource = $context === null
			? @fopen($filename, $mode, $useIncludePath)
			: @fopen($filename, $mode, $useIncludePath, $context);
		if ($resource === false) {
			throw new TIOException('stream_open_failed', $filename, $mode);
		}
		return Prado::createComponent(self::class, $resource);
	}

	/**
	 * Creates a writable+readable in-memory temp stream containing $data, rewound.
	 * @param string $data The initial contents.
	 * @return self The stream (owned), positioned at the start.
	 */
	public static function fromString(string $data = ''): self
	{
		$stream = self::fromTemp();
		if ($data !== '') {
			$stream->write($data);
			$stream->seekTo(0);
		}
		return $stream;
	}

	/**
	 * Creates a php://memory stream.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @return self The stream (owned).
	 */
	public static function fromMemory(string $mode = 'r+b'): self
	{
		return self::fromFile('php://memory', $mode);
	}

	/**
	 * Creates a php://temp stream that spills to disk past $maxMemoryBytes.
	 * @param int $maxMemoryBytes Bytes kept in memory before spilling. Default 2 MiB.
	 * @return self The stream (owned).
	 */
	public static function fromTemp(int $maxMemoryBytes = 2097152): self
	{
		return self::fromFile('php://temp/maxmemory:' . $maxMemoryBytes, 'r+b');
	}

	/**
	 * Wraps an already-open resource.
	 * @param mixed $resource An open PHP stream resource.
	 * @param bool $owns Whether the new stream should close it. Default false.
	 * @return self The wrapping stream.
	 */
	public static function fromResource(mixed $resource, bool $owns = false): self
	{
		$stream = Prado::createComponent(self::class);
		$stream->attachResource($resource, $owns);
		return $stream;
	}

	/**
	 * Bridges any PSR-7 stream *back* into a native PHP resource, for code and
	 * built-in functions that require a real resource handle.
	 * @param StreamInterface $stream The stream to expose as a resource.
	 * @return resource A native stream resource backed by $stream.
	 */
	public static function asResource(StreamInterface $stream)
	{
		return TStreamResourceWrapper::getResource($stream);
	}

	//
	// ─── Events ──────────────────────────────────────────────────────────────
	//

	/**
	 * Raised at the first read that reaches end of file.
	 * @param mixed $param The requested read length.
	 */
	public function onEndOfFile(mixed $param): void
	{
		$this->raiseEvent('onEndOfFile', $this, $param);
	}

	/**
	 * Raised after a successful reposition of the stream pointer.
	 * @param mixed $param The seek offset.
	 */
	public function onSeek(mixed $param): void
	{
		$this->raiseEvent('onSeek', $this, $param);
	}

	/**
	 * Filter handles are PHP resources and cannot be serialized; the capability
	 * cache is meaningless without the (already-zapped) handle.  Exclude both from
	 * {@see \Prado\TComponent::__sleep()}.
	 * @param array &$exprops The properties excluded from serialization.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_filters";
		$exprops[] = "\0" . __CLASS__ . "\0_readable";
		$exprops[] = "\0" . __CLASS__ . "\0_writable";
		$exprops[] = "\0" . __CLASS__ . "\0_seekable";
		$exprops[] = "\0" . __CLASS__ . "\0_size";
		$exprops[] = "\0" . __CLASS__ . "\0_uri";
	}
}
