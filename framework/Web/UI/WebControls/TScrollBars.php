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
 * TScrollBars class.
 * TScrollBars defines the enumerable type for the possible scroll bar mode
 * that a {@see \Prado\Web\UI\WebControls\TPanel} control could use.
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
class TScrollBars extends \Prado\TEnumerable
{
	public const None = 'None';
	public const Auto = 'Auto';
	public const Both = 'Both';
	public const Horizontal = 'Horizontal';
	public const Vertical = 'Vertical';
}
