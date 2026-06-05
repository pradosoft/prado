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
 * The public surface is PSR-7 (read/write/seek/tell/eof/rewind/isReadable/isWritable/
 * isSeekable/getSize/getContents).  PSR getters double as Prado properties (getSize() →
 * ->Size, isReadable() → ->Readable).  PHP-name access aliases (fread/fwrite/fseek/
 * fgets/…) are provided by {@see \Prado\IO\Behavior\TPhpStreamBehavior} when attached.
 *
 * Self-encapsulation (Uniform Access Principle): capability and filter state is
 * reached through accessors. The protected {@see getReadableDirect()} family are
 * the raw accessors, so subclasses (pipes, sockets) override {@see isReadable()},
 * {@see isWritable()}, and {@see isSeekable()} without disturbing internal logic.
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
 * @method bool dyIsReadable(bool $readable) Vetoes the readable capability; return false to deny.
 * @method bool dyIsWritable(bool $writable) Vetoes the writable capability; return false to deny.
 * @method bool dyIsSeekable(bool $seekable) Vetoes the seekable capability; return false to deny.
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
	 * @return mixed The detached resource, or null.
	 * @see StreamInterface::detach()
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
	 * @return string The entire stream contents, or '' on any error.
	 * @see StreamInterface::__toString()
	 */
	public function __toString(): string
	{
		try {
			if ($this->isSeekable()) {
				try {
					$this->seek(0);
				} catch (\Throwable $e) {
					// a behavior may forbid the seek; read from the current position
				}
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
	 * @return ?int The size in bytes, or null when indeterminable.
	 * @see StreamInterface::getSize()
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
	 * @throws \RuntimeException When the position cannot be determined.
	 * @return int The current position of the read/write pointer.
	 * @see StreamInterface::tell()
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
	 * @return bool Whether the stream is at the end of file.
	 * @see StreamInterface::eof()
	 */
	public function eof(): bool
	{
		$resource = $this->getResourceDirect();
		return !is_resource($resource) || feof($resource);
	}

	/**
	 * Indicates whether the stream is seekable.  A {@see dyIsSeekable} behavior may veto
	 * the capability, forcing it to false.
	 * @return bool Whether the stream is seekable.
	 * @see StreamInterface::isSeekable()
	 */
	public function isSeekable(): bool
	{
		$seekable = $this->getSeekableDirect();
		$this->callBehaviorsMethod('dyIsSeekable', $seekable, $seekable);
		return (bool) $seekable;
	}

	/**
	 * Seeks to a position.  A non-seekable (or behavior-vetoed, see {@see isSeekable()})
	 * or failed seek throws; a successful seek raises {@see onSeek}.
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @throws \RuntimeException When the stream is not seekable or the seek fails.
	 * @see StreamInterface::seek()
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$resource = $this->getResourceDirect();
		if (!$this->isSeekable() || !is_resource($resource)) {
			throw new \RuntimeException('Cannot seek a non-seekable stream');
		}
		if (fseek($resource, $offset, $whence) !== 0) {
			throw new \RuntimeException('Unable to seek to stream position ' . $offset);
		}
		$this->onSeek($offset);
	}

	/**
	 * Seeks to the beginning of the stream (PSR-7).
	 * @throws \RuntimeException When the stream is not seekable.
	 * @see StreamInterface::rewind()
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * Indicates whether the stream is writable.  A {@see dyIsWritable} behavior may veto
	 * the capability, forcing it to false.
	 * @return bool Whether the stream is writable.
	 * @see StreamInterface::isWritable()
	 */
	public function isWritable(): bool
	{
		$writable = $this->getWritableDirect();
		$this->callBehaviorsMethod('dyIsWritable', $writable, $writable);
		return (bool) $writable;
	}

	/**
	 * Writes data to the stream.
	 * @param string $string The bytes to write.
	 * @throws \RuntimeException When the stream is not writable or the write fails.
	 * @return int The number of bytes written.
	 * @see StreamInterface::write()
	 */
	public function write(string $string): int
	{
		$resource = $this->getResourceDirect();
		if (!$this->isWritable()) {
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
	 * Indicates whether the stream is readable.  A {@see dyIsReadable} behavior may veto
	 * the capability, forcing it to false.
	 * @return bool Whether the stream is readable.
	 * @see StreamInterface::isReadable()
	 */
	public function isReadable(): bool
	{
		$readable = $this->getReadableDirect();
		$this->callBehaviorsMethod('dyIsReadable', $readable, $readable);
		return (bool) $readable;
	}

	/**
	 * Reads up to $length bytes from the stream.
	 * @param int $length The maximum number of bytes to read.
	 * @throws \RuntimeException When the stream is not readable or the read fails.
	 * @return string The bytes read (may be shorter than $length, '' at EOF).
	 * @see StreamInterface::read()
	 */
	public function read(int $length): string
	{
		$resource = $this->getResourceDirect();
		if (!$this->isReadable()) {
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
	 * @throws \RuntimeException When the stream is unreadable or the read fails.
	 * @return string The remaining contents.
	 * @see StreamInterface::getContents()
	 */
	public function getContents(): string
	{
		$resource = $this->getResourceDirect();
		if (!$this->isReadable()) {
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
	// ─── Prado property accessors ────────────────────────────────────────────
	//

	/**
	 * Returns the stream URI (the ->URI property).
	 * @return ?string The stream URI, or null.
	 */
	public function getURI(): ?string
	{
		return $this->getURIDirect();
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
	 * Removes a previously appended/prepended filter ({@see stream_filter_remove()}), by
	 * filter handle or by name.  A name removes the first attached filter that bears it.
	 * @param resource|string $filter A filter handle from append/prepend, or a filter name.
	 * @return bool Whether a filter was removed.
	 */
	public function removeFilter(mixed $filter): bool
	{
		if (is_string($filter)) {
			$index = $this->getFilterIndex($filter);
			if ($index === null) {
				return false;
			}
			$filter = $this->getFiltersDirect()[$index]['filter'];
		}
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
			$stream->seek(0);
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
