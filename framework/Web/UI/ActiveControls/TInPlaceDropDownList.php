<?php

/**
 * TInPlaceDropDownList class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TDropDownList;

/**
 * TInPlaceDropDownList class.
 *
 * TInPlaceDropDownList is a drop down list rendered as a label showing the
 * selected item text. Clicking the label, or the control given by
 * {@see setEditTriggerControlID EditTriggerControlID}, swaps the label for
 * the drop down list. When no item is selected or the selected item text is
 * empty, the label shows {@see setEmptyDisplayText EmptyDisplayText}.
 *
 * When {@see \Prado\Web\UI\WebControls\TListControl::setAutoPostBack AutoPostBack}
 * is true (the default), changing the selection makes a callback request that
 * raises {@see \Prado\Web\UI\WebControls\TListControl::onSelectedIndexChanged OnSelectedIndexChanged}
 * and {@see onCallback OnCallback}. During the request the drop down list is
 * disabled. After the request returns successfully, the label shows the new
 * selection and, when {@see setAutoHideEditor AutoHideEditor} is true, the
 * drop down list is hidden and the label is shown.
 *
 * If the {@see onLoadingItems OnLoadingItems} event is handled, a callback
 * request is made when the label is clicked. The event allows the item list
 * to be updated, through the active list adapter, before the client selects
 * a value.
 *
 * The {@see setReadOnly ReadOnly} property prevents entering edit mode. The
 * property can be changed during a callback.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInPlaceDropDownList extends TActiveDropDownList
{
	use TInPlaceControlTrait, TInPlaceListControlTrait {
		TInPlaceListControlTrait::onPreRender insteadof TInPlaceControlTrait;
	}

	/**
	 * Renders the drop down list attributes, always registering the in-place
	 * client class.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	protected function renderListControlAttributes($writer)
	{
		TDropDownList::addAttributesToRender($writer);
	}

	/**
	 * @return string corresponding javascript class name for this TInPlaceDropDownList
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TInPlaceDropDownList';
	}
}
