<?php

/**
 * TDataGridColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TButtonColumnType class.
 * TButtonColumnType defines the enumerable type for the possible types of buttons
 * that can be used in a {@see \Prado\Web\UI\WebControls\TButtonColumn}.
 *
 * The following enumerable values are defined:
 * - LinkButton: link buttons
 * - PushButton: form buttons
 * - ImageButton: image buttons
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TButtonColumnType extends \Prado\TEnumerable
{
	public const LinkButton = 'LinkButton';
	public const PushButton = 'PushButton';
	public const ImageButton = 'ImageButton';
}
