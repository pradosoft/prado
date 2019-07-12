<?php
/**
 * TDataGridColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TButtonColumnType class.
 * TButtonColumnType defines the enumerable type for the possible types of buttons
 * that can be used in a {@link TButtonColumn}.
 *
 * The following enumerable values are defined:
 * - LinkButton: link buttons
 * - PushButton: form buttons
 * - ImageButton: image buttons
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TButtonColumnType extends \Prado\TEnumerable
{
	const LinkButton = 'LinkButton';
	const PushButton = 'PushButton';
	const ImageButton = 'ImageButton';
}
