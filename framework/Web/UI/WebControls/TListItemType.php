<?php
/**
 * TDataBoundControl class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
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
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TListItemType extends \Prado\TEnumerable
{
	const Header = 'Header';
	const Footer = 'Footer';
	const Item = 'Item';
	const Separator = 'Separator';
	const AlternatingItem = 'AlternatingItem';
	const EditItem = 'EditItem';
	const SelectedItem = 'SelectedItem';
	const Pager = 'Pager';
}
