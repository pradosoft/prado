<?php
/**
 * TListBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TListSelectionMode enum.
 * TListSelectionMode defines the enumerable type for the possible selection modes of a {@link TListBox}.
 *
 * The following enumerable values are defined:
 * - Single: single selection
 * - Multiple: allow multiple selection
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TListSelectionMode: string
{
	case Single = 'Single';
	case Multiple = 'Multiple';
}
