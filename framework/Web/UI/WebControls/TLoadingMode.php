<?php

/**
 * TLoadingMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TLoadingMode class.
 *
 * TLoadingMode defines the HTML `loading` attribute of {@see TImage} and
 * {@see TInlineFrame}.
 * - NotSet → no `loading` attribute; the browser loads the resource at once.
 * - Eager → `eager`: load the resource at once.
 * - Lazy → `lazy`: defer the load until the element nears the viewport.
 *
 * A lazy image keeps its layout space only with an intrinsic width and height.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLoadingMode extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const Eager = 'Eager';
	public const Lazy = 'Lazy';
}
