<?php
/**
 * TInlineFrame class file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TInlineFrameScrollBars enum.
 * TInlineFrameScrollBars defines the enumerable type for the possible scroll bar mode
 * that a {@link TInlineFrame} control could use.
 *
 * The following enumerable values are defined:
 * - None: no scroll bars.
 * - Auto: scroll bars automatically appeared when needed.
 * - Both: show both horizontal and vertical scroll bars all the time.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 * @deprecated obsolete since html5
 */
enum TInlineFrameScrollBars: string
{
	case None = 'None';
	case Auto = 'Auto';
	case Both = 'Both';
}
