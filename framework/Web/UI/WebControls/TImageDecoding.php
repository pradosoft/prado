<?php

/**
 * TImageDecoding class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TImageDecoding class.
 *
 * TImageDecoding defines the HTML `decoding` attribute of {@see TImage}.
 * - NotSet → no `decoding` attribute.
 * - Sync → `sync`: decode with the rest of the page content.
 * - Async → `async`: decode separately, so other content renders first.
 * - Auto → `auto`: the browser decides.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TImageDecoding extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const Sync = 'Sync';
	public const Async = 'Async';
	public const Auto = 'Auto';
}
