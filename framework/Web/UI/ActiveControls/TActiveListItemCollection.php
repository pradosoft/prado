<?php
/**
 * TActiveListControlAdapter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Collections\TListItemCollection;

/**
 * TActiveListItemCollection class.
 *
 * Allows TActiveDropDownList and TActiveListBox to add new options
 * during callback response. New options can only be added <b>after</b> the
 * {@link TControl::onLoad OnLoad} event.
 *
 * The {@link getListHasChanged ListHasChanged} property is true when the
 * list items has changed. The control responsible for the list needs to
 * repopulate the client-side options.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveListItemCollection extends TListItemCollection
{
	/**
	 * @var IActiveControl control instance.
	 */
	private $_control;
	/**
	 * @var bool true if list items were changed.
	 */
	private $_hasChanged = false;

	/**
	 * @return bool true if active controls can update client-side and
	 * the onLoad event has already been raised.
	 */
	protected function canUpdateClientSide()
	{
		return $this->getControl()->getActiveControl()->canUpdateClientSide()
				&& $this->getControl()->getHasLoaded();
	}

	/**
	 * @param IActiveControl $control a active list control.
	 */
	public function setControl(IActiveControl $control)
	{
		$this->_control = $control;
	}

	/**
	 * @return IActiveControl active control using the collection.
	 */
	public function getControl()
	{
		return $this->_control;
	}

	/**
	 * @return bool true if the list has changed after onLoad event.
	 */
	public function getListHasChanged()
	{
		return $this->_hasChanged;
	}

	/**
	 * Update client-side list items.
	 */
	public function updateClientSide()
	{
		$client = $this->getControl()->getPage()->getCallbackClient();
		$client->setListItems($this->getControl(), $this);
		$this->_hasChanged = false;
	}

	/**
	 * Inserts an item into the collection.
	 * The new option is added on the client-side during callback.
	 * @param int $index the location where the item will be inserted.
	 * The current item at the place and the following ones will be moved backward.
	 * @param TListItem $value the item to be inserted.
	 * @throws TInvalidDataTypeException if the item being inserted is neither a string nor TListItem
	 */
	public function insertAt($index, $value)
	{
		parent::insertAt($index, $value);
		if ($this->canUpdateClientSide()) {
			$this->_hasChanged = true;
		}
	}

	/**
	 * Removes an item from at specified index.
	 * @param int $index zero based index.
	 */
	public function removeAt($index)
	{
		parent::removeAt($index);
		if ($this->canUpdateClientSide()) {
			$this->_hasChanged = true;
		}
	}
}
