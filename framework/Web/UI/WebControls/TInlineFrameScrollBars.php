<?php
/**
 * TInlineFrame class file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TInlineFrameScrollBars class.
 * TInlineFrameScrollBars defines the enumerable type for the possible scroll bar mode
 * that a {@link TInlineFrame} control could use.
 *
 * The following enumerable values are defined:
 * - None: no scroll bars.
 * - Auto: scroll bars automatically appeared when needed.
 * - Both: show both horizontal and vertical scroll bars all the time.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 * @deprecated obsolete since html5
 */
class TInlineFrameScrollBars extends \Prado\TEnumerable
{
	const None = 'None';
	const Auto = 'Auto';
	const Both = 'Both';
}
