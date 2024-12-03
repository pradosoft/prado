<?php

/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBulletedListDisplayMode class.
 * TBulletedListDisplayMode defines the enumerable type for the possible display mode
 * of a {@see \Prado\Web\UI\WebControls\TBulletedList} control.
 *
 * The following enumerable values are defined:
 * - Text: the bulleted list items are displayed as plain texts
 * - HyperLink: the bulleted list items are displayed as hyperlinks
 * - LinkButton: the bulleted list items are displayed as link buttons that can cause postbacks
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TBulletedListDisplayMode extends \Prado\TEnumerable
{
	public const Text = 'Text';
	public const HyperLink = 'HyperLink';
	public const LinkButton = 'LinkButton';
}
