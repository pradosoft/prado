<?php
/**
 * TPager class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPagerButtonType enum.
 * TPagerButtonType defines the enumerable type for the possible types of pager buttons.
 *
 * The following enumerable values are defined:
 * - LinkButton: link buttons
 * - PushButton: form submit buttons
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TPagerButtonType: string
{
	case LinkButton = 'LinkButton';
	case PushButton = 'PushButton';
	case ImageButton = 'ImageButton';
}
