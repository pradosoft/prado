<?php
/**
 * TListBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TListSelectionMode class.
 * TListSelectionMode defines the enumerable type for the possible selection modes of a {@link TListBox}.
 *
 * The following enumerable values are defined:
 * - Single: single selection
 * - Multiple: allow multiple selection
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TListSelectionMode extends \Prado\TEnumerable
{
	const Single = 'Single';
	const Multiple = 'Multiple';
}
