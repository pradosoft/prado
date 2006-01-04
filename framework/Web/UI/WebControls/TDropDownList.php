<?php
/**
 * TDropDownList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TDropDownList class
 *
 * TDropDownList displays a dropdown list on a Web page.
 * It inherits all properties and events from {@link TListControl}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDropDownList extends TListControl implements IPostBackDataHandler
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
		$page=$this->getPage();
		if($this->getAutoPostBack() && !$page->getPostBackEventTarget())
		{
			$page->setPostBackEventTarget($this);
			if($this->getCausesValidation())
				$page->validate($this->getValidationGroup());
		}
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
}
?>