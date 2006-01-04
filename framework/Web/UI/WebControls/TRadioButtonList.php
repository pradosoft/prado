<?php
/**
 * TRadioButtonList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

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
 * @version $Revision: $  $Date: $
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
		return new TRadioButton;
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
}

?>