<?php

/**
 * TActiveSuggestionList class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TSuggestionList;

/**
 * TActiveSuggestionList class.
 *
 * TActiveSuggestionList is the active control counterpart of {@see TSuggestionList}.
 * Items added, removed, or data bound during a callback, after the
 * {@see \Prado\Web\UI\TControl::onLoad OnLoad} event, replace the client-side
 * `<datalist>` options when the callback completes. The update requires
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveControl::setEnableUpdate ActiveControl.EnableUpdate}.
 * Changes to the properties of an existing item do not update the client.
 *
 * The list raises no callbacks of its own. A common pattern updates the
 * suggestions from the {@see TActiveTextBox::onCallback OnCallback} event of the
 * text box using them:
 * ```html
 * <com:TActiveTextBox ID="City" SuggestionList="Cities" AutoPostBack="true"
 *     OnCallback="cityChanged" />
 * <com:TActiveSuggestionList ID="Cities" />
 * ```
 * ```php
 * public function cityChanged($sender, $param)
 * {
 *     $this->Cities->setDataSource($this->findCities($this->City->getText()));
 *     $this->Cities->dataBind();
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveSuggestionList extends TSuggestionList implements IActiveControl
{
	/**
	 * Creates a new active control and sets the adapter to TActiveControlAdapter.
	 * The adapter is not a {@see \Prado\Web\UI\WebControls\IListControlAdapter}
	 * because a `<datalist>` has no selection.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveControl basic active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Creates a TActiveListItemCollection to track item changes for the client update.
	 * @return TActiveListItemCollection the collection object
	 */
	protected function createListItemCollection()
	{
		$collection = new TActiveListItemCollection();
		$collection->setControl($this);
		return $collection;
	}

	/**
	 * Replaces the client-side options when the items changed after the OnLoad event.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->updateListItems();
	}

	/**
	 * Sends the items to the client when they changed after the OnLoad event.
	 */
	public function updateListItems()
	{
		if (!$this->getActiveControl()->canUpdateClientSide()) {
			return;
		}
		$items = $this->getItems();
		if ($items instanceof TActiveListItemCollection && $items->getListHasChanged()) {
			$items->updateClientSide();
		}
	}
}
