<?php

/**
 * TFnStreamMethod class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TEnumerable;

/**
 * TFnStreamMethod class.
 *
 * Enumerates the method names of the PSR-7 {@see \Psr\Http\Message\StreamInterface}, so a
 * closure map keys on `TFnStreamMethod::Read` instead of a bare `'read'` string.
 * {@see TFnStream} keys its implementation map by these names.
 *
 * The constant value is the exact method name as PHP reports it from the interface.
 *
 * | Constant     | Method        |
 * |--------------|---------------|
 * | ToString     | '__toString'  |
 * | Close        | 'close'       |
 * | Detach       | 'detach'      |
 * | GetSize      | 'getSize'     |
 * | Tell         | 'tell'        |
 * | Eof          | 'eof'         |
 * | IsSeekable   | 'isSeekable'  |
 * | Seek         | 'seek'        |
 * | Rewind       | 'rewind'      |
 * | IsWritable   | 'isWritable'  |
 * | Write        | 'write'       |
 * | IsReadable   | 'isReadable'  |
 * | Read         | 'read'        |
 * | GetContents  | 'getContents' |
 * | GetMetadata  | 'getMetadata' |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFnStreamMethod extends TEnumerable
{
	public const ToString = '__toString';
	public const Close = 'close';
	public const Detach = 'detach';
	public const GetSize = 'getSize';
	public const Tell = 'tell';
	public const Eof = 'eof';
	public const IsSeekable = 'isSeekable';
	public const Seek = 'seek';
	public const Rewind = 'rewind';
	public const IsWritable = 'isWritable';
	public const Write = 'write';
	public const IsReadable = 'isReadable';
	public const Read = 'read';
	public const GetContents = 'getContents';
	public const GetMetadata = 'getMetadata';
}
