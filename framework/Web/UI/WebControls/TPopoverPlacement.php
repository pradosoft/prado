<?php

/**
 * TPopoverPlacement class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPopoverPlacement class.
 *
 * TPopoverPlacement defines the side of its anchor where a {@see TPopover} shows,
 * rendered as its `data-placement` attribute. The anchor is the invoker button
 * that opened the popover, or the `source` element passed to `showPopover()`.
 * The stylesheet places the popover with CSS anchor positioning and flips it to
 * the opposite side when it does not fit.
 *
 * | Value | `data-placement` | The popover shows |
 * |---|---|---|
 * | None | not rendered | at the browser default position, centered in the viewport |
 * | Top | `top` | above the anchor |
 * | Bottom | `bottom` | below the anchor |
 * | Start | `start` | at the inline start of the anchor; left in a left-to-right page |
 * | End | `end` | at the inline end of the anchor; right in a left-to-right page |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPopoverPlacement extends \Prado\TEnumerable
{
	public const None = 'None';
	public const Top = 'Top';
	public const Bottom = 'Bottom';
	public const Start = 'Start';
	public const End = 'End';
}
