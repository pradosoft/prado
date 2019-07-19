<?php
/**
 * TRadioButtonList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;

/**
 * TRadioButtonList class
 *
 * TRadioButtonList displays a list of radiobuttons on a Web page.
 *
 * TRadioButtonList inherits all properties and events of {@link TCheckBoxList}.
 * Each TRadioButtonList displays one group of radiobuttons, i.e., at most
 * one radiobutton can be selected at a time.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TRadioButtonList extends TCheckBoxList
{
	/**
	 * @return bool whether this control supports multiple selection. Always false for radiobutton list.
	 */
	protected function getIsMultiSelect()
	{
		return false;
	}

	/**
	 * Creates a control used for repetition (used as a template).
	 * @return TRadioButtonItem the control to be repeated
	 */
	protected function createRepeatedControl()
	{
		return new TRadioButtonItem;
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the control has been changed
	 */
	public function loadPostData($key, $values)
	{
		$value = $values[$key] ?? '';
		$oldSelection = $this->getSelectedIndex();
		$this->ensureDataBound();
		foreach ($this->getItems() as $index => $item) {
			if ($item->getEnabled() && $item->getValue() === $value) {
				if ($index === $oldSelection) {
					return false;
				} else {
					$this->setSelectedIndex($index);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param mixed $indices
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndices($indices)
	{
		throw new TNotSupportedException('radiobuttonlist_selectedindices_unsupported');
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TRadioButtonList';
	}
}
