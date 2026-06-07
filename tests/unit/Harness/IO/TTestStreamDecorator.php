<?php

/**
 * TTestStreamDecorator class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\Stream\TStreamDecorator;
use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * TTestStreamDecorator is a concrete {@see \Prado\IO\Stream\TStreamDecorator} for unit tests.
 *
 * {@see TStreamDecorator} is abstract, so exercising its forwarding contract and its lazy
 * {@see \Prado\IO\Stream\TStreamDecorator::getStream()} override seam needs a concrete
 * subclass.  This harness serves both roles, and as a controllable inner-stream wrapper for
 * any test that needs a plain decorator.
 *
 * Two construction modes:
 *
 * - Eager: `new TTestStreamDecorator($inner)` forwards everything to the given inner stream,
 *   overriding nothing (the bare forwarding contract).
 * - Lazy: `new TTestStreamDecorator(null, 'contents')` builds its inner stream from the
 *   contents on the first {@see getStream()} call.  {@see $builds} counts how many times the
 *   inner stream is built, so a test can confirm it is built once and reused.
 *
 * Constructed with neither an inner stream nor lazy contents, the first forwarded call hits
 * the uninitialized inner stream and throws, matching the decorator's contract that a null
 * inner requires a getStream() override.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestStreamDecorator extends TStreamDecorator
{
	/** @var int The number of times the lazy inner stream has been built. */
	public int $builds = 0;

	/** @var ?string The contents the lazy inner stream is built from, or null for eager mode. */
	private ?string $_lazyContents;

	/** @var bool Whether the lazy inner stream has been built. */
	private bool $_built = false;

	/**
	 * @param ?StreamInterface $stream The inner stream (eager mode), or null for lazy/bare mode.
	 * @param ?string $lazyContents The contents to build the inner stream from on first use; null disables lazy build.
	 */
	public function __construct(?StreamInterface $stream = null, ?string $lazyContents = null)
	{
		$this->_lazyContents = $lazyContents;
		parent::__construct($stream);
	}

	/**
	 * Returns the inner stream, building it once from the lazy contents when in lazy mode.
	 * In eager or bare mode it defers to the base accessor.
	 * @return StreamInterface The inner stream.
	 */
	public function getStream(): StreamInterface
	{
		if ($this->_lazyContents !== null) {
			if (!$this->_built) {
				$this->setStreamDirect(TStream::fromString($this->_lazyContents));
				$this->_built = true;
				$this->builds++;
			}
			return $this->getStreamDirect();
		}
		return parent::getStream();
	}
}
