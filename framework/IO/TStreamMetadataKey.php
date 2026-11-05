<?php

/**
 * TStreamMetadataKey class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TEnumerable;

/**
 * TStreamMetadataKey class.
 *
 * Enumerates the array keys returned by {@see stream_get_meta_data()}, so a call to
 * {@see TResource::getMetadata()} reads `$res->getMetadata(TStreamMetadataKey::Mode)`
 * instead of a bare `'mode'` string.
 *
 * | Constant     | Key value      | Meaning                                              |
 * |--------------|----------------|------------------------------------------------------|
 * | TimedOut     | 'timed_out'    | The stream timed out waiting for data on the last call. |
 * | Blocked      | 'blocked'      | The stream is in blocking IO mode.                   |
 * | Eof          | 'eof'          | The stream has reached end of file.                  |
 * | UnreadBytes  | 'unread_bytes' | The number of bytes in the read buffer.              |
 * | StreamType   | 'stream_type'  | The implementation label of the stream.              |
 * | WrapperType  | 'wrapper_type' | The wrapper protocol label.                          |
 * | WrapperData  | 'wrapper_data' | Wrapper-specific data attached to the stream.        |
 * | Mode         | 'mode'         | The access mode the stream was opened with.          |
 * | Seekable     | 'seekable'     | Whether the stream can be sought.                    |
 * | Uri          | 'uri'          | The URI or filename the stream was opened from.      |
 * | MediaType    | 'mediatype'    | The media type, present for data:// and HTTP wrappers. |
 * | Crypto       | 'crypto'       | TLS details, present once crypto is enabled.         |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamMetadataKey extends TEnumerable
{
	public const TimedOut = 'timed_out';
	public const Blocked = 'blocked';
	public const Eof = 'eof';
	public const UnreadBytes = 'unread_bytes';
	public const StreamType = 'stream_type';
	public const WrapperType = 'wrapper_type';
	public const WrapperData = 'wrapper_data';
	public const Mode = 'mode';
	public const Seekable = 'seekable';
	public const Uri = 'uri';
	public const MediaType = 'mediatype';
	public const Crypto = 'crypto';
}
