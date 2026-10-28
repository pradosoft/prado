<?php

/**
 * TMenuList class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TMenuList class
 * TMenuList renders the items of a {@see TBulletedList} in the HTML `<menu>`
 * element, a toolbar of commands. Items post back with
 * {@see TBulletedList::setDisplayMode() DisplayMode} `LinkButton` and raise
 * {@see TBulletedList::onClick() OnClick}.
 * ```html
 * <com:TMenuList DisplayMode="LinkButton" OnClick="menuClicked" Aria.label="Edit">
 *     <com:TListItem Text="Cut" Value="cut" />
 *     <com:TListItem Text="Copy" Value="copy" />
 * </com:TMenuList>
 * ```
 * A `<menu>` is an unordered list, so a numbered
 * {@see TBulletedList::setBulletStyle() BulletStyle} changes only the markers.
 * Accessibility: browsers expose `<menu>` with the `list` role, and screen
 * readers announce the number of items. It is not an ARIA `menu`; `role="menu"`
 * requires arrow-key navigation that TMenuList does not provide. A page with more
 * than one toolbar names each with `Aria.label`. `LinkButton` items are links, and
 * screen readers announce them as links.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMenuList extends TBulletedList
{
	/**
	 * @return string the tag name of the list, `menu`.
	 */
	protected function getTagName()
	{
		return 'menu';
	}
}
