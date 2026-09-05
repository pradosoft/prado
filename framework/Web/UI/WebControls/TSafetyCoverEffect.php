<?php

/**
 * TSafetyCoverEffect class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSafetyCoverEffect enumeration.
 *
 * TSafetyCoverEffect specifies the geometric transition the overlay of a
 * {@see TSafetyCover} makes when the control opens. Each value renders as a
 * `safety-cover-<effect>` class on the control, which the bundled stylesheet
 * turns into a transition. It combines with the independent
 * {@see TSafetyCover::getOverlayFade OverlayFade} opacity transition.
 *
 * | Constant | CSS mechanism | Appearance |
 * |---|---|---|
 * | `Slide` | `transform: translate` clipped by the container | The overlay slides off the panel toward {@see TSafetyCoverDirection}; its content moves with it. |
 * | `Collapse` | `clip-path: inset` | The overlay is wiped away toward {@see TSafetyCoverDirection}; its content stays put, like a rolling shade. |
 * | `None` | *(none)* | No geometric transition. Pair with `OverlayFade` for a pure fade; with neither, the overlay snaps hidden. Ignores {@see TSafetyCoverDirection}. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSafetyCoverEffect extends \Prado\TEnumerable
{
	/** The overlay slides off the panel, its content moving with it. */
	public const Slide = 'Slide';

	/** The overlay is clipped away in place, its content staying put. */
	public const Collapse = 'Collapse';

	/** No geometric transition; the overlay fades ({@see TSafetyCover::getOverlayFade}) or snaps hidden. */
	public const None = 'None';
}
