<?php

/**
 * TMediaPreload class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TMediaPreload class.
 * TMediaPreload defines the `preload` hint of a {@see TBaseMedia}.
 * - NotSet → no `preload` attribute; the browser decides.
 * - None → `none`: load nothing until the user plays.
 * - Metadata → `metadata`: load the duration and dimensions.
 * - Auto → `auto`: the whole resource may load.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMediaPreload extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const None = 'None';
	public const Metadata = 'Metadata';
	public const Auto = 'Auto';
}
