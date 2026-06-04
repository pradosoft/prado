<?php

/**
 * TFileStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TFileStream class.
 *
 * Opens a file path or registered stream-wrapper URI (e.g. 'compress.zlib://archive.gz')
 * as a {@see TStream}.  It adds no behavior beyond the convenience constructor.
 *
 * ```php
 * $s = new TFileStream('/path/to/file.txt', 'r+b');
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFileStream extends TStream
{
	/**
	 * Opens the given file or stream-wrapper URI.
	 * @param string $filename The file path or stream-wrapper URI.
	 * @param string $mode The fopen mode. Default 'rb'.
	 * @param bool $useIncludePath Whether to search the include path. Default false.
	 * @param mixed $context An optional stream context.
	 * @throws TIOException When the file/URI cannot be opened.
	 */
	public function __construct(string $filename, string $mode = 'rb', bool $useIncludePath = false, mixed $context = null)
	{
		$resource = $context === null
			? @fopen($filename, $mode, $useIncludePath)
			: @fopen($filename, $mode, $useIncludePath, $context);
		if ($resource === false) {
			throw new TIOException('stream_open_failed', $filename, $mode);
		}
		parent::__construct($resource);
	}
}
