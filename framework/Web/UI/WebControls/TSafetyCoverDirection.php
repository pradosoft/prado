<?php

/**
 * TSafetyCoverDirection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSafetyCoverDirection enumeration.
 *
 * TSafetyCoverDirection specifies the direction the overlay of a
 * {@see TSafetyCover} travels or collapses toward when the control opens. It
 * governs the `Slide` and `Collapse` effects and is ignored by `Fade`.
 *
 * Four values are physical and two are logical:
 *
 * | Constant | Resolves to |
 * |---|---|
 * | `Up` | the top edge |
 * | `Down` | the bottom edge |
 * | `Left` | the left edge |
 * | `Right` | the right edge |
 * | `Forward` | the reading-end edge: `Right` in left-to-right content, `Left` in right-to-left |
 * | `Backward` | the reading-start edge: `Left` in left-to-right content, `Right` in right-to-left |
 *
 * The logical values resolve from the control's {@see TPanel::getDirection Direction}
 * during rendering, so a single template behaves correctly in both writing
 * directions.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSafetyCoverDirection extends \Prado\TEnumerable
{
	/** The overlay leaves toward the top edge. */
	public const Up = 'Up';

	/** The overlay leaves toward the bottom edge. */
	public const Down = 'Down';

	/** The overlay leaves toward the left edge. */
	public const Left = 'Left';

	/** The overlay leaves toward the right edge. */
	public const Right = 'Right';

	/** The overlay leaves toward the reading-end edge, flipping with content direction. */
	public const Forward = 'Forward';

	/** The overlay leaves toward the reading-start edge, flipping with content direction. */
	public const Backward = 'Backward';
}
