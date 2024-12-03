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
 * TListItemType class.
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
class TListItemType extends \Prado\TEnumerable
{
	public const Header = 'Header';
	public const Footer = 'Footer';
	public const Item = 'Item';
	public const Separator = 'Separator';
	public const AlternatingItem = 'AlternatingItem';
	public const EditItem = 'EditItem';
	public const SelectedItem = 'SelectedItem';
	public const Pager = 'Pager';
}
