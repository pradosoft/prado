<?php

/**
 * TFnStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

/**
 * TFnStream class.
 *
 * Builds a {@see StreamInterface} from a map of closures, one per PSR-7 method.  It adapts
 * a source that is not a stream into one, and overrides selected methods of an existing
 * stream without writing a dedicated decorator class.  Each public method delegates to the
 * closure registered under its name; a method with no closure returns an inert default.
 *
 * The map keys are PSR-7 method names, enumerated by {@see TFnStreamMethod} (for example
 * `TFnStreamMethod::Read` is `'read'`).  Each closure receives the same arguments as the
 * method it backs and returns the same kind of value; the result is coerced to the method's
 * declared return type, so `tell`/`write` are cast to int, the capability and `eof`
 * closures to bool, and `read`/`__toString`/`getContents` to string.
 *
 * Defaults for an unset method:
 *
 * | Constant                    | Method      | Arguments        | Behavior when no closure is set        |
 * |-----------------------------|-------------|------------------|----------------------------------------|
 * | TFnStreamMethod::ToString   | __toString  | —                | ''                                     |
 * | TFnStreamMethod::Close      | close       | —                | no-op                                  |
 * | TFnStreamMethod::Detach     | detach      | —                | null                                   |
 * | TFnStreamMethod::GetSize    | getSize     | —                | null (unknown size)                    |
 * | TFnStreamMethod::Tell       | tell        | —                | 0                                      |
 * | TFnStreamMethod::Eof        | eof         | —                | true (reports end of stream)           |
 * | TFnStreamMethod::IsSeekable | isSeekable  | —                | false                                  |
 * | TFnStreamMethod::Seek       | seek        | $offset, $whence | throws {@see \RuntimeException}        |
 * | TFnStreamMethod::Rewind     | rewind      | —                | calls {@see seek()} with offset 0      |
 * | TFnStreamMethod::IsWritable | isWritable  | —                | false                                  |
 * | TFnStreamMethod::Write      | write       | $string          | throws {@see \RuntimeException}        |
 * | TFnStreamMethod::IsReadable | isReadable  | —                | false                                  |
 * | TFnStreamMethod::Read       | read        | $length          | throws {@see \RuntimeException}        |
 * | TFnStreamMethod::GetContents| getContents | —                | throws {@see \RuntimeException}        |
 * | TFnStreamMethod::GetMetadata| getMetadata | $key             | [] for the whole array, null for a key |
 *
 * The capability flags default to false, and the four transfer methods (`seek`, `write`,
 * `read`, `getContents`) throw without their closure, matching the PSR-7 contract that an
 * unsupported operation fails rather than silently doing nothing.  A compliant consumer
 * checks the capability flags first and never reaches those throws.  The observers stay
 * inert, so a partial map is still a valid stream object.  No resource is released on its
 * own; a `close` or `detach` closure frees whatever the source holds.  {@see rewind()} uses
 * the `rewind` closure when present and otherwise calls {@see seek()} with offset 0, so
 * supplying only a `seek` closure makes rewind work.
 *
 * PSR-7 forbids {@see __toString()} to throw, so an exception escaping the `__toString`
 * closure is swallowed and '' returned.  The stream also refuses to wake from
 * {@see https://www.php.net/unserialize unserialize()}: the map may hold any callable, so a
 * crafted serialized payload would otherwise choose what runs when the stream is used.
 *
 * Adapting a generated source into a stream:
 * ```php
 * $payload = 'hello world';
 * $fixed = new TFnStream([
 *     'read'       => fn (int $n) => substr($payload, 0, $n),
 *     'getSize'    => fn () => strlen($payload),
 *     'eof'        => fn () => true,
 *     'isReadable' => fn () => true,
 * ]);
 * ```
 *
 * Overriding a few methods of an existing stream by closing over it and forwarding the rest:
 * ```php
 * $inner = TStream::fromFile('big.log', 'rb');
 * $readOnly = new TFnStream([
 *     'read'       => fn (int $n) => $inner->read($n),
 *     'eof'        => fn () => $inner->eof(),
 *     'getSize'    => fn () => $inner->getSize(),
 *     'isReadable' => fn () => true,
 *     'close'      => fn () => $inner->close(),
 * ]);
 * ```
 *
 * The closure map is fixed at construction and reached through the {@see getFnsDirect()}
 * accessor.  For a full subclassable decorator that forwards every method by default, use
 * {@see TStreamDecorator}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFnStream extends TComponent implements StreamInterface
{
	/** @var array<string, callable> The method implementations, keyed by method name. */
	private array $_fns;

	/**
	 * @param array<string, callable> $fns The method closures, keyed by PSR-7 method name.
	 */
	public function __construct(array $fns)
	{
		$this->setFnsDirect($fns);
		parent::__construct();
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw method-closure map.
	 * @return array<string, callable> The raw method-closure map.
	 */
	protected function getFnsDirect(): array
	{
		return $this->_fns;
	}

	/**
	 * Sets the raw method-closure map.
	 * @param array<string, callable> $value The raw method-closure map.
	 */
	protected function setFnsDirect(array $value): void
	{
		$this->_fns = $value;
	}

	/**
	 * Calls the closure for a method, or returns the supplied default when none is set.
	 * @param string $method The PSR-7 method name.
	 * @param array<int, mixed> $args The call arguments.
	 * @param mixed $default The value to return when no closure is provided.
	 * @return mixed The closure result or the default.
	 */
	private function call(string $method, array $args, mixed $default): mixed
	{
		$fns = $this->getFnsDirect();
		return isset($fns[$method]) ? ($fns[$method])(...$args) : $default;
	}

	/**
	 * Calls the closure for a transfer method, throwing when none is set, since PSR-7 reports
	 * an unsupported seek, write, or read as a failure.
	 * @param string $method The PSR-7 method name.
	 * @param array<int, mixed> $args The call arguments.
	 * @throws \RuntimeException When no closure backs the method.
	 * @return mixed The closure result.
	 */
	private function invoke(string $method, array $args): mixed
	{
		$fns = $this->getFnsDirect();
		if (!isset($fns[$method])) {
			throw new \RuntimeException("TFnStream has no {$method} closure; the stream does not support {$method}");
		}
		return ($fns[$method])(...$args);
	}

	/**
	 * Blocks unserialization: the closure map may hold any callable, so a crafted serialized
	 * payload would choose what runs when the stream is used.
	 * @throws \LogicException Always.
	 */
	public function __wakeup()
	{
		throw new \LogicException('TFnStream cannot be unserialized');
	}

	/**
	 * Returns the full stream contents (delegates to the `__toString` closure).  An exception
	 * from the closure is swallowed and '' returned, since PSR-7 forbids __toString to throw.
	 * @return string The contents, or '' when no closure is set or it throws.
	 */
	public function __toString(): string
	{
		try {
			return (string) $this->call('__toString', [], '');
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Closes the stream (delegates to the `close` closure).
	 */
	public function close(): void
	{
		$this->call('close', [], null);
	}

	/**
	 * Detaches the stream (delegates to the `detach` closure).
	 * @return null|resource The detached resource, or null.
	 */
	public function detach()
	{
		return $this->call('detach', [], null);
	}

	/**
	 * Returns the stream size (delegates to the `getSize` closure).
	 * @return ?int The size, or null.
	 */
	public function getSize(): ?int
	{
		return $this->call('getSize', [], null);
	}

	/**
	 * Returns the current position (delegates to the `tell` closure).
	 * @return int The position.
	 */
	public function tell(): int
	{
		return (int) $this->call('tell', [], 0);
	}

	/**
	 * Indicates end of stream (delegates to the `eof` closure).
	 * @return bool Whether at end of stream.
	 */
	public function eof(): bool
	{
		return (bool) $this->call('eof', [], true);
	}

	/**
	 * Indicates whether the stream is seekable (delegates to the `isSeekable` closure).
	 * @return bool Whether seekable.
	 */
	public function isSeekable(): bool
	{
		return (bool) $this->call('isSeekable', [], false);
	}

	/**
	 * Seeks to a position (delegates to the `seek` closure).
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
	 * @throws \RuntimeException When no `seek` closure is set.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$this->invoke('seek', [$offset, $whence]);
	}

	/**
	 * Seeks to the beginning (delegates to the `rewind` closure, else `seek(0)`).
	 */
	public function rewind(): void
	{
		$fns = $this->getFnsDirect();
		if (isset($fns['rewind'])) {
			($fns['rewind'])();
			return;
		}
		$this->seek(0);
	}

	/**
	 * Indicates whether the stream is writable (delegates to the `isWritable` closure).
	 * @return bool Whether writable.
	 */
	public function isWritable(): bool
	{
		return (bool) $this->call('isWritable', [], false);
	}

	/**
	 * Writes data (delegates to the `write` closure).
	 * @param string $string The bytes to write.
	 * @throws \RuntimeException When no `write` closure is set.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		return (int) $this->invoke('write', [$string]);
	}

	/**
	 * Indicates whether the stream is readable (delegates to the `isReadable` closure).
	 * @return bool Whether readable.
	 */
	public function isReadable(): bool
	{
		return (bool) $this->call('isReadable', [], false);
	}

	/**
	 * Reads up to $length bytes (delegates to the `read` closure).
	 * @param int $length The maximum number of bytes to read.
	 * @throws \RuntimeException When no `read` closure is set.
	 * @return string The bytes read.
	 */
	public function read(int $length): string
	{
		return (string) $this->invoke('read', [$length]);
	}

	/**
	 * Returns the remaining contents (delegates to the `getContents` closure).
	 * @throws \RuntimeException When no `getContents` closure is set.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		return (string) $this->invoke('getContents', []);
	}

	/**
	 * Returns stream metadata (delegates to the `getMetadata` closure).
	 * @param ?string $key A specific metadata key, or null for the whole array.
	 * @return mixed The metadata value or array.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		return $this->call('getMetadata', [$key], $key === null ? [] : null);
	}
}
