<?php
/**
 * TPanelStyle class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TScrollBars enum.
 * TScrollBars defines the enumerable type for the possible scroll bar mode
 * that a {@link TPanel} control could use.
 *
 * The following enumerable values are defined:
 * - None: no scroll bars.
 * - Auto: scroll bars automatically appeared when needed.
 * - Both: show both horizontal and vertical scroll bars all the time.
 * - Horizontal: horizontal scroll bar only
 * - Vertical: vertical scroll bar only
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TScrollBars: string
{
	case None = 'None';
	case Auto = 'Auto';
	case Both = 'Both';
	case Horizontal = 'Horizontal';
	case Vertical = 'Vertical';
}
