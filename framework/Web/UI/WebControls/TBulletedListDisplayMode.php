<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TBulletedListDisplayMode class.
 * TBulletedListDisplayMode defines the enumerable type for the possible display mode
 * of a {@link TBulletedList} control.
 *
 * The following enumerable values are defined:
 * - Text: the bulleted list items are displayed as plain texts
 * - HyperLink: the bulleted list items are displayed as hyperlinks
 * - LinkButton: the bulleted list items are displayed as link buttons that can cause postbacks
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TBulletedListDisplayMode extends TEnumerable
{
	const Text='Text';
	const HyperLink='HyperLink';
	const LinkButton='LinkButton';
}