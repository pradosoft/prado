<?php
/**
 * TRadioButtonList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TRadioButton class
 */
Prado::using('System.Web.UI.WebControls.TRadioButton');
/**
 * Includes TCheckBoxList class
 */
Prado::using('System.Web.UI.WebControls.TCheckBoxList');

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
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRadioButtonList extends TCheckBoxList
{
	/**
	 * @return boolean whether this control supports multiple selection. Always false for radiobutton list.
	 */
	protected function getIsMultiSelect()
	{
		return false;
	}

	/**
	 * Creates a control used for repetition (used as a template).
	 * @return TControl the control to be repeated
	 */
	protected function createRepeatedControl()
	{
		return new TRadioButtonItem;
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values)
	{
		$value=isset($values[$key])?$values[$key]:'';
		$oldSelection=$this->getSelectedIndex();
		$this->ensureDataBound();
		foreach($this->getItems() as $index=>$item)
		{
			if($item->getEnabled() && $item->getValue()===$value)
			{
				if($index===$oldSelection)
					return false;
				else
				{
					$this->setSelectedIndex($index);
					return true;
				}
			}
		}
		return false;
	}

	/**
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

class TRadioButtonItem extends TRadioButton {
	/**
	 * Override client implementation to avoid emitting the javascript
	 */
	protected function renderClientControlScript($writer)
	{
	}
}
