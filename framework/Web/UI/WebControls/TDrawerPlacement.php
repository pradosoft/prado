<?php

/**
 * TDrawerPlacement class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDrawerPlacement class.
 *
 * TDrawerPlacement defines the page edge of a {@see TDrawer}, rendered as its
 * `data-placement` attribute. Start and End follow the text direction.
 *
 * | Value | `data-placement` | The drawer shows at |
 * |---|---|---|
 * | Start | `start` | the inline start of the page; left in a left-to-right page |
 * | End | `end` | the inline end of the page; right in a left-to-right page |
 * | Top | `top` | the top of the page |
 * | Bottom | `bottom` | the bottom of the page, as a bottom sheet |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDrawerPlacement extends \Prado\TEnumerable
{
	public const Start = 'Start';
	public const End = 'End';
	public const Top = 'Top';
	public const Bottom = 'Bottom';
}
