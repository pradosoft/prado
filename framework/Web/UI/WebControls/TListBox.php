<?php
/**
 * TListBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TListBox class
 *
 * TListBox displays a list box on a Web page that allows single or multiple selection.
 * The list box allows multiple selections if {@link setSelectionMode SelectionMode}
 * is 'Multiple'. It takes single selection only if 'Single'.
 * The property {@link setRows Rows} specifies how many rows of options are visible
 * at a time. See {@link TListControl} for inherited properties.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TListBox extends TListControl implements IPostBackDataHandler
{
	/**
	 * Adds attribute name-value pairs to renderer.
	 * This method overrides the parent implementation with additional list box specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$rows=$this->getRows();
		$writer->addAttribute('size',"$rows");
		if($this->getSelectionMode()==='Multiple')
			$writer->addAttribute('name',$this->getUniqueID().'[]');
		else
			$writer->addAttribute('name',$this->getUniqueID());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Registers the list control to load post data on postback.
	 * This method overrides the parent implementation.
	 * @param mixed event parameter
	 */
	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		if($this->getEnabled(true))
			$this->getPage()->registerRequiresPostData($this);
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
		$selections=isset($values[$key])?$values[$key]:null;
		if($selections!==null)
		{
			$items=$this->getItems();
			if($this->getSelectionMode()==='Single')
			{
				$selection=is_array($selections)?$selections[0]:$selections;
				$index=$items->findIndexByValue($selection,false);
				if($this->getSelectedIndex()!==$index)
				{
					$this->setSelectedIndex($index);
					return true;
				}
				else
					return false;
			}
			if(!is_array($selections))
				$selections=array($selections);
			$list=array();
			foreach($selections as $selection)
				$list[]=$items->findIndexByValue($selection,false);
			$list2=$this->getSelectedIndices();
			$n=count($list);
			$flag=false;
			if($n===count($list2))
			{
				sort($list,SORT_NUMERIC);
				for($i=0;$i<$n;++$i)
				{
					if($list[$i]!==$list2[$i])
					{
						$flag=true;
						break;
					}
				}
			}
			else
				$flag=true;
			if($flag)
				$this->setSelectedIndices($list);
			return $flag;
		}
		else if($this->getSelectedIndex()!==-1)
		{
			$this->clearSelection();
			return true;
		}
		else
			return false;
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by {@link IPostBackDataHandler} interface.
	 * It is invoked by the framework when {@link getSelectedIndices SelectedIndices} property
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
	 * @return boolean whether this control allows multiple selection
	 */
	protected function getIsMultiSelect()
	{
		return $this->getSelectionMode()==='Multiple';
	}

	/**
	 * @return integer the number of rows to be displayed in the list control
	 */
	public function getRows()
	{
		return $this->getViewState('Rows', 4);
	}

	/**
	 * @param integer the number of rows to be displayed in the list control
	 */
	public function setRows($value)
	{
		$value=TPropertyValue::ensureInteger($value);
		if($value<=0)
			$value=4;
		$this->setViewState('Rows', $value, 4);
	}

	/**
	 * @return string the selection mode (Single, Multiple). Defaults to 'Single'.
	 */
	public function getSelectionMode()
	{
		return $this->getViewState('SelectionMode', 'Single');
	}

	/**
	 * Sets the selection mode of the list control (Single, Multiple)
	 * @param string the selection mode
	 */
	public function setSelectionMode($value)
	{
		$this->setViewState('SelectionMode',TPropertyValue::ensureEnum($value,array('Single','Multiple')),'Single');
	}
}
?>