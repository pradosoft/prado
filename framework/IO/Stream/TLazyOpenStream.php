<?php

/**
 * TLazyOpenStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * TLazyOpenStream class.
 *
 * Defers opening the underlying stream until the first time it is used.  The factory
 * callable runs once, on the first forwarded operation, so constructing the decorator
 * costs nothing until something reads, writes, or inspects it.
 *
 * ```php
 * $log = new TLazyOpenStream(fn () => TStream::fromFile('/var/log/app.log', 'ab'));
 * // the file is not opened here ...
 * $log->write("started\n");   // ... it opens now, on first write
 * ```
 *
 * {@see close()} and {@see detach()} on a stream that never opened are a no-op: there is
 * nothing to release, and running the factory just to discard its stream could create the
 * very resource the caller is disposing of.  The stream also refuses to wake from
 * {@see https://www.php.net/unserialize unserialize()}, since the factory may be any
 * callable and a crafted serialized payload would otherwise choose what runs on first use.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLazyOpenStream extends TStreamDecorator
{
	/** @var callable The factory that opens the inner stream on first use. */
	private $_factory;

	/** @var bool Whether the inner stream has been opened. */
	private bool $_opened = false;

	/**
	 * @param callable $factory A callable returning the {@see StreamInterface} to open lazily.
	 */
	public function __construct(callable $factory)
	{
		$this->setFactoryDirect($factory);
		parent::__construct();
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw factory callable.
	 * @return callable The raw factory callable.
	 */
	protected function getFactoryDirect(): callable
	{
		return $this->_factory;
	}

	/**
	 * Sets the raw factory callable.
	 * @param callable $value The raw factory callable.
	 */
	protected function setFactoryDirect(callable $value): void
	{
		$this->_factory = $value;
	}

	/**
	 * Returns the raw opened flag.
	 * @return bool The raw opened flag.
	 */
	protected function getOpenedDirect(): bool
	{
		return $this->_opened;
	}

	/**
	 * Sets the raw opened flag.
	 * @param bool $value The raw opened flag.
	 */
	protected function setOpenedDirect(bool $value): void
	{
		$this->_opened = $value;
	}

	/**
	 * Returns the inner stream, opening it through the factory on first access.
	 * @return StreamInterface The lazily opened inner stream.
	 */
	public function getStream(): StreamInterface
	{
		if (!$this->getOpenedDirect()) {
			$this->setStreamDirect(($this->getFactoryDirect())());
			$this->setOpenedDirect(true);
		}
		return parent::getStream();   // the inner stream is open now; bypass this override without recursing
	}

	/**
	 * Indicates whether the inner stream has been opened yet.
	 * @return bool Whether the factory has run.
	 */
	public function isOpened(): bool
	{
		return $this->getOpenedDirect();
	}

	/**
	 * Closes the inner stream when it has opened; a no-op otherwise, so disposing of an unused
	 * lazy stream never runs the factory.
	 */
	public function close(): void
	{
		if ($this->getOpenedDirect()) {
			parent::close();
		}
	}

	/**
	 * Detaches the inner stream when it has opened; a no-op otherwise, so disposing of an
	 * unused lazy stream never runs the factory.
	 * @return null|resource The detached resource, or null when the stream never opened.
	 */
	public function detach()
	{
		return $this->getOpenedDirect() ? parent::detach() : null;
	}

	/**
	 * Blocks unserialization: the factory may be any callable, so a crafted serialized payload
	 * would choose what runs on first use.
	 * @throws \LogicException Always.
	 */
	public function __wakeup()
	{
		throw new \LogicException('TLazyOpenStream cannot be unserialized');
	}
}
