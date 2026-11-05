<?php

/**
 * TStreamFactory class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TComponent;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * TStreamFactory class.
 *
 * Implements the PSR-17 {@see StreamFactoryInterface}, producing {@see TStream}
 * instances.  Hand this to any PSR-17 consumer (a PSR-7 message factory, an HTTP
 * client, …) to have it build Prado streams.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamFactory extends TComponent implements StreamFactoryInterface
{
	/**
	 * Creates a stream from a string, positioned at the start.
	 * @param string $content The stream contents.
	 * @return StreamInterface The new stream.
	 */
	public function createStream(string $content = ''): StreamInterface
	{
		return TStream::fromString($content);
	}

	/**
	 * Opens a file (or stream-wrapper URI) as a stream.
	 * @param string $filename The file path or wrapper URI.
	 * @param string $mode The fopen mode. Default 'r'.
	 * @return StreamInterface The opened stream (owned).
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
	{
		return TStream::fromFile($filename, $mode);
	}

	/**
	 * Wraps an already-open resource.  The returned stream takes ownership and will
	 * close the resource when it is closed/destroyed (PSR-17 / Guzzle convention).
	 * @param mixed $resource An open PHP stream resource.
	 * @return StreamInterface The wrapping stream.
	 */
	public function createStreamFromResource($resource): StreamInterface
	{
		return TStream::fromResource($resource, true);
	}
}
