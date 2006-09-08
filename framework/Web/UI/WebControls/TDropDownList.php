<?php
/**
 * TDropDownList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TListControl class
 */
Prado::using('System.Web.UI.WebControls.TListControl');

/**
 * TDropDownList class
 *
 * TDropDownList displays a dropdown list on a Web page.
 * It inherits all properties and events from {@link TListControl}.
 *
 * Since v3.0.3, TDropDownList starts to support optgroup. To specify an option group for
 * a list item, set a Group attribute with it,
 * <code>
 *  $listitem->Attributes->Group="Group Name";
 *  // or <com:TListItem Attributes.Group="Group Name" .../> in template
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDropDownList extends TListControl implements IPostBackDataHandler, IValidatable
{
	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('name',$this->getUniqueID());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TDropDownList';
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the component has been changed
	 */
	public function loadPostData($key,$values)
	{
		if(!$this->getEnabled(true))
			return false;
		$this->ensureDataBound();
		$selection=isset($values[$key])?$values[$key]:null;
		if($selection!==null)
		{
			$index=$this->getItems()->findIndexByValue($selection,false);
			if($this->getSelectedIndex()!==$index)
			{
				$this->setSelectedIndex($index);
				return true;
			}
		}
		return false;
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by {@link IPostBackDataHandler} interface.
	 * It is invoked by the framework when {@link getSelectedIndex SelectedIndex} property
	 * is changed on postback.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		if($this->getAutoPostBack() && $this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onSelectedIndexChanged(null);
	}

	/**
	 * @return integer the index (zero-based) of the item being selected.
	 * If none is selected, the return value is 0 meaning the first item is selected.
	 * If there is no items, it returns -1.
	 */
	public function getSelectedIndex()
	{
		$index=parent::getSelectedIndex();
		if($index<0 && $this->getHasItems())
		{
			$this->setSelectedIndex(0);
			return 0;
		}
		else
			return $index;
	}

	/**
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndices($indices)
	{
		throw new TNotSupportedException('dropdownlist_selectedindices_unsupported');
	}

	/**
	 * Returns the value to be validated.
	 * This methid is required by IValidatable interface.
	 * @return mixed the value of the property to be validated.
	 */
	public function getValidationPropertyValue()
	{
		return $this->getSelectedValue();
	}
}
?>