<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * THotSpotMode class.
 * THotSpotMode defines the enumerable type for the possible hot spot modes.
 *
 * The following enumerable values are defined:
 * - NotSet: the mode is not specified
 * - Navigate: clicking on the hotspot will redirect the browser to a different page
 * - PostBack: clicking on the hotspot will cause a postback
 * - Inactive: the hotspot is inactive (not clickable)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class THotSpotMode extends \Prado\TEnumerable
{
	const NotSet = 'NotSet';
	const Navigate = 'Navigate';
	const PostBack = 'PostBack';
	const Inactive = 'Inactive';
}
