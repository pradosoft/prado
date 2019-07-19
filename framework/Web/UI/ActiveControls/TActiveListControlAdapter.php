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

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\Web\UI\WebControls\IListControlAdapter;

/**
 * TActiveListControlAdapter class.
 *
 * Adapte the list controls to allows the selections on the client-side to be altered
 * during callback response.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveListControlAdapter extends TActiveControlAdapter implements IListControlAdapter
{
	/**
	 * @return bool true if can update client-side attributes.
	 */
	protected function canUpdateClientSide()
	{
		return $this->getControl()->getActiveControl()->canUpdateClientSide();
	}

	/**
	 * Selects an item based on zero-base index on the client side.
	 * @param int $index the index (zero-based) of the item to be selected
	 */
	public function setSelectedIndex($index)
	{
		if ($this->canUpdateClientSide()) {
			$this->updateListItems();
			// if a prompt is set, we mimic the postback behaviour of not counting it
			// in the index. We assume the prompt is _always_ the first item (Issue #368)
			$promptValue = $this->getControl()->getPromptValue();
			if ($promptValue === '') {
				$promptValue = $this->getControl()->getPromptText();
			}
			if ($promptValue !== '') {
				$index++;
			}

			if ($index >= 0 && $index <= $this->getControl()->getItemCount()) {
				$this->getPage()->getCallbackClient()->select(
					$this->getControl(),
					'Index',
					$index
			);
			}
		}
	}

	/**
	 * Selects a list of item based on zero-base indices on the client side.
	 * @param array $indices list of index of items to be selected
	 */
	public function setSelectedIndices($indices)
	{
		if ($this->canUpdateClientSide()) {
			$this->updateListItems();
			$n = $this->getControl()->getItemCount();

			$promptValue = $this->getControl()->getPromptValue();
			if ($promptValue === '') {
				$promptValue = $this->getControl()->getPromptText();
			}

			$list = [];
			foreach ($indices as $index) {
				$index = (int) $index;
				if ($promptValue !== '') {
					$index++;
				}
				if ($index >= 0 && $index <= $n) {
					$list[] = $index;
				}
			}
			if (count($list) > 0) {
				$this->getPage()->getCallbackClient()->select(
					$this->getControl(),
					'Indices',
					$list
				);
			}
		}
	}

	/**
	 * Sets selection by item value on the client side.
	 * @param string $value the value of the item to be selected.
	 */
	public function setSelectedValue($value)
	{
		if ($this->canUpdateClientSide()) {
			$this->updateListItems();
			$this->getPage()->getCallbackClient()->select(
				$this->getControl(),
				'Value',
				$value
			);
		}
	}

	/**
	 * Sets selection by a list of item values on the client side.
	 * @param array $values list of the selected item values
	 */
	public function setSelectedValues($values)
	{
		if ($this->canUpdateClientSide()) {
			$this->updateListItems();
			$list = [];
			foreach ($values as $value) {
				$list[] = $value;
			}
			if (count($list) > 0) {
				$this->getPage()->getCallbackClient()->select(
					$this->getControl(),
					'Values',
					$list
				);
			}
		}
	}

	/**
	 * Clears all existing selections on the client side.
	 */
	public function clearSelection()
	{
		if ($this->canUpdateClientSide()) {
			$this->updateListItems();
			if ($this->getControl() instanceof TActiveDropDownList) {
				// clearing a TActiveDropDownList's selection actually doesn't select the first item;
				// we mimic the postback behaviour selecting it (Issue #368)
				$this->getPage()->getCallbackClient()->select($this->getControl(), 'Index', 0);
			} else {
				$this->getPage()->getCallbackClient()->select($this->getControl(), 'Clear');
			}
		}
	}

	/**
	 * Update the client-side list options.
	 */
	public function updateListItems()
	{
		if ($this->canUpdateClientSide()) {
			$items = $this->getControl()->getItems();
			if ($items instanceof TActiveListItemCollection
				&& $items->getListHasChanged()) {
				$items->updateClientSide();
			}
		}
	}
}
