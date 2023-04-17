<?php
/**
 * TDataBoundControl class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TListItemType enum.
 * TListItemType defines the enumerable type for the possible types
 * that databound list items could take.
 *
 * The following enumerable values are defined:
 * - Header: header item
 * - Footer: footer item
 * - Item: content item (neither header nor footer)
 * - Separator: separator between items
 * - AlternatingItem: alternating content item
 * - EditItem: content item in edit mode
 * - SelectedItem: selected content item
 * - Pager: pager
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TListItemType: string
{
	case Header = 'Header';
	case Footer = 'Footer';
	case Item = 'Item';
	case Separator = 'Separator';
	case AlternatingItem = 'AlternatingItem';
	case EditItem = 'EditItem';
	case SelectedItem = 'SelectedItem';
	case Pager = 'Pager';
}
