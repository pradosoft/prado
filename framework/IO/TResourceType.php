<?php

/**
 * TResourceType class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TEnumerable;

/**
 * TResourceType class.
 *
 * Enumerates the {@see get_resource_type()} names that the IO layer distinguishes, so
 * comparisons read `get_resource_type($r) === TResourceType::Process` instead of a bare
 * `'process'` string.
 *
 * | Constant | get_resource_type() value | Produced by                          |
 * |----------|---------------------------|--------------------------------------|
 * | Stream   | 'stream'                  | {@see fopen()}, {@see popen()}, sockets |
 * | Process  | 'process'                 | {@see proc_open()}                   |
 * | Curl     | 'curl'                    | legacy cURL handles (objects since PHP 8) |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TResourceType extends TEnumerable
{
	public const Stream = 'stream';
	public const Process = 'process';
	public const Curl = 'curl';
}
