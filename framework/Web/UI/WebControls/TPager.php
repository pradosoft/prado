<?php
/**
 * TPager class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

Prado::using('System.Web.UI.WebControls.TDataBoundControl');
Prado::using('System.Web.UI.WebControls.TPanelStyle');
Prado::using('System.Collections.TPagedDataSource');
Prado::using('System.Collections.TDummyDataSource');

/**
 * TPager class.
 *
 * TPager creates a pager that controls the paging of the data populated
 * to a data-bound control specified by {@link setControlToPaginate ControlToPaginate}.
 * To specify the number of data items displayed on each page, set
 * the {@link setPageSize PageSize} property, and to specify which
 * page of data to be displayed, set {@link setCurrentPageIndex CurrentPageIndex}.
 *
 * When the size of the original data is too big to be loaded all in the memory,
 * one can enable custom paging. In custom paging, the total number of data items
 * is specified manually via {@link setVirtualItemCount VirtualItemCount}, and the data source
 * only needs to contain the current page of data. To enable custom paging,
 * set {@link setAllowCustomPaging AllowCustomPaging} to true.
 *
 * TPager can be in one of three {@link setMode Mode}:
 * - NextPrev: a next page and a previous page button are rendered.
 * - Numeric: a list of page index buttons are rendered.
 * - List: a dropdown list of page indices are rendered.
 *
 * TPager raises an {@link onPageIndexChanged OnPageIndexChanged} event when
 * the end-user interacts with it and specifies a new page (e.g. clicking
 * on a page button that leads to a new page.) The new page index may be obtained
 * from the event parameter's property {@link TPagerPageChangedEventParameter::getNewPageIndex NewPageIndex}.
 *
 * When multiple pagers are associated with the same data-bound control,
 * these pagers will do synchronization among each other so that the interaction
 * with one pager will automatically update the UI of the other relevant pagers.
 *
 * The following example shows a typical usage of TPager:
 * <code>
 * $pager->ControlToPaginate="Path.To.Control";
 * $pager->DataSource=$data;
 * $pager->dataBind();
 * </code>
 * Note, the data is assigned to the pager and dataBind() is invoked against the pager.
 * Without the pager, one has to set datasource for the target control and call
 * its dataBind() directly.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0.2
 */
class TPager extends TDataBoundControl implements INamingContainer
{
	/**
	 * Command name that TPager understands.
	 */
	const CMD_PAGE='Page';
	const CMD_PAGE_NEXT='Next';
	const CMD_PAGE_PREV='Previous';
	const CMD_PAGE_FIRST='First';
	const CMD_PAGE_LAST='Last';

	/**
	 * @var array list of all pagers, used to synchronize their appearance
	 */
	static private $_pagers=array();

	/**
	 * Registers the pager itself to a global list.
	 * This method overrides the parent implementation and is invoked during
	 * OnInit control lifecycle.
	 * @param mixed event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		self::$_pagers[]=$this;
	}

	/**
	 * Unregisters the pager from a global list.
	 * This method overrides the parent implementation and is invoked during
	 * OnUnload control lifecycle.
	 * @param mixed event parameter
	 */
	public function onUnload($param)
	{
		parent::onUnload($param);
		if(($index=array_search($this,self::$_pagers,true))!==false)
			unset(self::$_pagers[$index]);
	}

	/**
	 * Restores the pager state.
	 * This method overrides the parent implementation and is invoked when
	 * the control is loading persistent state.
	 */
	public function loadState()
	{
		parent::loadState();
		if(!$this->getEnableViewState(true))
			return;
		if(!$this->getIsDataBound())
			$this->restoreFromViewState();
	}

	/**
	 * @return string the ID path of the control whose content would be paginated.
	 */
	public function getControlToPaginate()
	{
		return $this->getViewState('ControlToPaginate','');
	}

	/**
	 * Sets the ID path of the control whose content would be paginated.
	 * The ID path is the dot-connected IDs of the controls reaching from
	 * the pager's naming container to the target control.
	 * @param string the ID path
	 */
	public function setControlToPaginate($value)
	{
		$this->setViewState('ControlToPaginate',$value,'');
	}

	/**
	 * @return integer the zero-based index of the current page. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->getViewState('CurrentPageIndex',0);
	}

	/**
	 * @param integer the zero-based index of the current page
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	public function setCurrentPageIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('pager_currentpageindex_invalid');
		$this->setViewState('CurrentPageIndex',$value,0);
	}

	/**
	 * @return integer the number of data items on each page. Defaults to 10.
	 */
	public function getPageSize()
	{
		return $this->getViewState('PageSize',10);
	}

	/**
	 * @param integer the number of data items on each page.
	 * @throws TInvalidDataValueException if the value is less than 1
	 */
	public function setPageSize($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<1)
			throw new TInvalidDataValueException('pager_pagesize_invalid');
		$this->setViewState('PageSize',TPropertyValue::ensureInteger($value),10);
	}

	/**
	 * @return integer number of pages
	 */
	public function getPageCount()
	{
		if(($count=$this->getItemCount())<1)
			return 1;
		else
		{
			$pageSize=$this->getPageSize();
			return (int)(($count+$pageSize-1)/$pageSize);
		}
	}

	/**
	 * @return boolean whether the custom paging is enabled. Defaults to false.
	 */
	public function getAllowCustomPaging()
	{
		return $this->getViewState('AllowCustomPaging',false);
	}

	/**
	 * Sets a value indicating whether the custom paging should be enabled.
	 * When the pager is in custom paging mode, the {@link setVirtualItemCount VirtualItemCount}
	 * property is used to determine the paging, and the data items in the
	 * {@link setDataSource DataSource} are considered to be in the current page.
	 * @param boolean whether the custom paging is enabled
	 */
	public function setAllowCustomPaging($value)
	{
		$this->setViewState('AllowCustomPaging',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return integer virtual number of data items in the data source. Defaults to 0.
	 * @see setAllowCustomPaging
	 */
	public function getVirtualItemCount()
	{
		return $this->getViewState('VirtualItemCount',0);
	}

	/**
	 * @param integer virtual number of data items in the data source.
	 * @throws TInvalidDataValueException if the value is less than 0
	 * @see setAllowCustomPaging
	 */
	public function setVirtualItemCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('pager_virtualitemcount_invalid');
		$this->setViewState('VirtualItemCount',$value,0);
	}

	/**
	 * @return integer total number of items in the datasource.
	 */
	public function getItemCount()
	{
		return $this->getViewState('ItemCount',0);
	}

	/**
	 * @return string pager mode. Defaults to 'NextPrev'.
	 */
	public function getMode()
	{
		return $this->getViewState('Mode','NextPrev');
	}

	/**
	 * @param string pager mode. Valid values include 'NextPrev', 'Numeric' and 'List'.
	 */
	public function setMode($value)
	{
		$this->setViewState('Mode',TPropertyValue::ensureEnum($value,'NextPrev','Numeric','List'),'NextPrev');
	}

	/**
	 * @return string the type of command button for paging. Defaults to 'LinkButton'.
	 */
	public function getButtonType()
	{
		return $this->getViewState('ButtonType','LinkButton');
	}

	/**
	 * @param string the type of command button for paging. Valid values include 'LinkButton' and 'PushButton'.
	 */
	public function setButtonType($value)
	{
		$this->setViewState('ButtonType',TPropertyValue::ensureEnum($value,'LinkButton','PushButton'));
	}

	/**
	 * @return string text for the next page button. Defaults to '>'.
	 */
	public function getNextPageText()
	{
		return $this->getViewState('NextPageText','>');
	}

	/**
	 * @param string text for the next page button.
	 */
	public function setNextPageText($value)
	{
		$this->setViewState('NextPageText',$value,'>');
	}

	/**
	 * @return string text for the previous page button. Defaults to '<'.
	 */
	public function getPrevPageText()
	{
		return $this->getViewState('PrevPageText','<');
	}

	/**
	 * @param string text for the next page button.
	 */
	public function setPrevPageText($value)
	{
		$this->setViewState('PrevPageText',$value,'<');
	}

	/**
	 * @return string text for the first page button. Defaults to '<<'.
	 */
	public function getFirstPageText()
	{
		return $this->getViewState('FirstPageText','<<');
	}

	/**
	 * @param string text for the first page button. If empty, the first page button will not be rendered.
	 */
	public function setFirstPageText($value)
	{
		$this->setViewState('FirstPageText',$value,'<<');
	}

	/**
	 * @return string text for the last page button. Defaults to '>>'.
	 */
	public function getLastPageText()
	{
		return $this->getViewState('LastPageText','>>');
	}

	/**
	 * @param string text for the last page button. If empty, the last page button will not be rendered.
	 */
	public function setLastPageText($value)
	{
		$this->setViewState('LastPageText',$value,'>>');
	}

	/**
	 * @return integer maximum number of pager buttons to be displayed. Defaults to 10.
	 */
	public function getPageButtonCount()
	{
		return $this->getViewState('PageButtonCount',10);
	}

	/**
	 * @param integer maximum number of pager buttons to be displayed
	 * @throws TInvalidDataValueException if the value is less than 1.
	 */
	public function setPageButtonCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<1)
			throw new TInvalidDataValueException('pager_pagebuttoncount_invalid');
		$this->setViewState('PageButtonCount',$value,10);
	}

	/**
	 * @return TPagedDataSource creates a paged data source
	 */
	private function createPagedDataSource()
	{
		$ds=new TPagedDataSource;
		$ds->setAllowPaging(true);
		$customPaging=$this->getAllowCustomPaging();
		$ds->setAllowCustomPaging($customPaging);
		$ds->setCurrentPageIndex($this->getCurrentPageIndex());
		$ds->setPageSize($this->getPageSize());
		if($customPaging)
			$ds->setVirtualItemCount($this->getVirtualItemCount());
		return $ds;
	}

	/**
	 * Removes the existing child controls.
	 */
	protected function reset()
	{
		$this->getControls()->clear();
	}

	/**
	 * Restores the pager from viewstate.
	 */
	protected function restoreFromViewState()
	{
		$this->reset();
		$ds=$this->createPagedDataSource();
		$ds->setDataSource(new TDummyDataSource($this->getItemCount()));
		$this->buildPager($ds);
	}

	/**
	 * Performs databinding to populate data items from data source.
	 * This method is invoked by {@link dataBind()}.
	 * You may override this function to provide your own way of data population.
	 * @param Traversable the bound data
	 */
	protected function performDataBinding($data)
	{
		$this->reset();

		$controlID=$this->getControlToPaginate();
		if(($targetControl=$this->getNamingContainer()->findControl($controlID))===null || !($targetControl instanceof TDataBoundControl))
			throw new TConfigurationException('pager_controltopaginate_invalid',$controlID);

		$ds=$this->createPagedDataSource();
		$ds->setDataSource($this->getDataSource());
		$this->setViewState('ItemCount',$ds->getDataSourceCount());

		$this->buildPager($ds);
		$this->synchronizePagers($targetControl,$ds);

		$targetControl->setDataSource($ds);
		$targetControl->dataBind();
	}

	/**
	 * Synchronizes the state of all pagers who have the same {@link getControlToPaginate ControlToPaginate}.
	 * @param TDataBoundControl the control whose content is to be paginated
	 * @param TPagedDataSource the paged data source associated with the pager
	 */
	protected function synchronizePagers($targetControl,$dataSource)
	{
		foreach(self::$_pagers as $pager)
		{
			if($pager!==$this && $pager->getNamingContainer()->findControl($pager->getControlToPaginate())===$targetControl)
			{
				$pager->reset();
				$pager->setCurrentPageIndex($dataSource->getCurrentPageIndex());
				$customPaging=$dataSource->getAllowCustomPaging();
				$pager->setAllowCustomPaging($customPaging);
				$pager->setViewState('ItemCount',$dataSource->getDataSourceCount());
				if($customPaging)
					$pager->setVirtualItemCount($dataSource->getVirtualItemCount());
				$pager->buildPager($dataSource);
			}
		}
	}

	/**
	 * Builds the pager content based on the pager mode.
	 * Current implementation includes building 'NextPrev', 'Numeric' and 'List' pagers.
	 * Derived classes may override this method to provide additional pagers.
	 * @param TPagedDataSource data source bound to the target control
	 */
	protected function buildPager($dataSource)
	{
		switch($this->getMode())
		{
			case 'NextPrev':
				$this->buildNextPrevPager($dataSource);
				break;
			case 'Numeric':
				$this->buildNumericPager($dataSource);
				break;
			case 'List':
				$this->buildListPager($dataSource);
				break;
		}
	}

	/**
	 * Creates a pager button.
	 * Depending on the button type, a TLinkButton or a TButton may be created.
	 * If it is enabled (clickable), its command name and parameter will also be set.
	 * Derived classes may override this method to create additional types of buttons, such as TImageButton.
	 * @param string button type, either LinkButton or PushButton
	 * @param boolean whether the button should be enabled
	 * @param string caption of the button
	 * @param string CommandName corresponding to the OnCommand event of the button
	 * @param string CommandParameter corresponding to the OnCommand event of the button
	 * @return mixed the button instance
	 */
	protected function createPagerButton($buttonType,$enabled,$text,$commandName,$commandParameter)
	{
		if($buttonType==='LinkButton')
		{
			if($enabled)
				$button=new TLinkButton;
			else
			{
				$button=new TLabel;
				$button->setText($text);
				return $button;
			}
		}
		else
		{
			$button=new TButton;
			if(!$enabled)
				$button->setEnabled(false);
		}
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCommandParameter($commandParameter);
		$button->setCausesValidation(false);
		return $button;
	}

	/**
	 * Builds a next-prev pager
	 * @param TPagedDataSource data source bound to the pager
	 */
	protected function buildNextPrevPager($dataSource)
	{
		$buttonType=$this->getButtonType();
		$controls=$this->getControls();
		if($dataSource->getIsFirstPage())
		{
			if(($text=$this->getFirstPageText())!=='')
			{
				$label=$this->createPagerButton($buttonType,false,$text,'','');
				$controls->add($label);
				$controls->add("\n");
			}
			$label=$this->createPagerButton($buttonType,false,$this->getPrevPageText(),'','');
			$controls->add($label);
		}
		else
		{
			if(($text=$this->getFirstPageText())!=='')
			{
				$button=$this->createPagerButton($buttonType,true,$text,self::CMD_PAGE_FIRST,'');
				$controls->add($button);
				$controls->add("\n");
			}
			$button=$this->createPagerButton($buttonType,true,$this->getPrevPageText(),self::CMD_PAGE_PREV,'');
			$controls->add($button);
		}
		$controls->add("\n");
		if($dataSource->getIsLastPage())
		{
			$label=$this->createPagerButton($buttonType,false,$this->getNextPageText(),'','');
			$controls->add($label);
			if(($text=$this->getLastPageText())!=='')
			{
				$controls->add("\n");
				$label=$this->createPagerButton($buttonType,false,$text,'','');
				$controls->add($label);
			}
		}
		else
		{
			$button=$this->createPagerButton($buttonType,true,$this->getNextPageText(),self::CMD_PAGE_NEXT,'');
			$controls->add($button);
			if(($text=$this->getLastPageText())!=='')
			{
				$controls->add("\n");
				$button=$this->createPagerButton($buttonType,true,$text,self::CMD_PAGE_LAST,'');
				$controls->add($button);
			}
		}
	}

	/**
	 * Builds a numeric pager
	 * @param TPagedDataSource data source bound to the pager
	 */
	protected function buildNumericPager($dataSource)
	{
		$buttonType=$this->getButtonType();
		$controls=$this->getControls();
		$pageCount=$dataSource->getPageCount();
		$pageIndex=$dataSource->getCurrentPageIndex()+1;
		$maxButtonCount=$this->getPageButtonCount();
		$buttonCount=$maxButtonCount>$pageCount?$pageCount:$maxButtonCount;
		$startPageIndex=1;
		$endPageIndex=$buttonCount;
		if($pageIndex>$endPageIndex)
		{
			$startPageIndex=((int)(($pageIndex-1)/$maxButtonCount))*$maxButtonCount+1;
			if(($endPageIndex=$startPageIndex+$maxButtonCount-1)>$pageCount)
				$endPageIndex=$pageCount;
			if($endPageIndex-$startPageIndex+1<$maxButtonCount)
			{
				if(($startPageIndex=$endPageIndex-$maxButtonCount+1)<1)
					$startPageIndex=1;
			}
		}

		if($startPageIndex>1)
		{
			if(($text=$this->getFirstPageText())!=='')
			{
				$button=$this->createPagerButton($buttonType,true,$text,self::CMD_PAGE_FIRST,'');
				$controls->add($button);
				$controls->add("\n");
			}
			$prevPageIndex=$startPageIndex-1;
			$button=$this->createPagerButton($buttonType,true,$this->getPrevPageText(),self::CMD_PAGE,"$prevPageIndex");
			$controls->add($button);
			$controls->add("\n");
		}

		for($i=$startPageIndex;$i<=$endPageIndex;++$i)
		{
			if($i===$pageIndex)
			{
				$label=$this->createPagerButton($buttonType,false,"$i",'','');
				$controls->add($label);
			}
			else
			{
				$button=$this->createPagerButton($buttonType,true,"$i",self::CMD_PAGE,"$i");
				$controls->add($button);
			}
			if($i<$endPageIndex)
				$controls->add("\n");
		}

		if($pageCount>$endPageIndex)
		{
			$controls->add("\n");
			$nextPageIndex=$endPageIndex+1;
			$button=$this->createPagerButton($buttonType,true,$this->getNextPageText(),self::CMD_PAGE,"$nextPageIndex");
			$controls->add($button);
			if(($text=$this->getLastPageText())!=='')
			{
				$controls->add("\n");
				$button=$this->createPagerButton($buttonType,true,$text,self::CMD_PAGE_LAST,'');
				$controls->add($button);
			}
		}
	}

	/**
	 * Builds a dropdown list pager
	 * @param TPagedDataSource data source bound to the pager
	 */
	protected function buildListPager($dataSource)
	{
		$list=new TDropDownList;
		$this->getControls()->add($list);
		$list->setDataSource(range(1,$dataSource->getPageCount()));
		$list->dataBind();
		$list->setSelectedIndex($dataSource->getCurrentPageIndex());
		$list->setAutoPostBack(true);
		$list->attachEventHandler('OnSelectedIndexChanged',array($this,'listIndexChanged'));
	}

	/**
	 * Event handler to the OnSelectedIndexChanged event of the dropdown list.
	 * This handler will raise {@link onPageIndexChanged OnPageIndexChanged} event.
	 * @param TDropDownList the dropdown list control raising the event
	 * @param TEventParameter event parameter
	 */
	public function listIndexChanged($sender,$param)
	{
		$pageIndex=$sender->getSelectedIndex();
		$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender,$pageIndex));
	}

	/**
	 * This event is raised when page index is changed due to a page button click.
	 * @param TPagerPageChangedEventParameter event parameter
	 */
	public function onPageIndexChanged($param)
	{
		$this->raiseEvent('OnPageIndexChanged',$this,$param);
	}

	/**
	 * Processes a bubbled event.
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$command=$param->getCommandName();
			if(strcasecmp($command,self::CMD_PAGE)===0)
			{
				$pageIndex=TPropertyValue::ensureInteger($param->getCommandParameter())-1;
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender,$pageIndex));
				return true;
			}
			else if(strcasecmp($command,self::CMD_PAGE_NEXT)===0)
			{
				$pageIndex=$this->getCurrentPageIndex()+1;
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender,$pageIndex));
				return true;
			}
			else if(strcasecmp($command,self::CMD_PAGE_PREV)===0)
			{
				$pageIndex=$this->getCurrentPageIndex()-1;
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender,$pageIndex));
				return true;
			}
			else if(strcasecmp($command,self::CMD_PAGE_FIRST)===0)
			{
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender,0));
				return true;
			}
			else if(strcasecmp($command,self::CMD_PAGE_LAST)===0)
			{
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender,$this->getPageCount()-1));
				return true;
			}
			return false;
		}
		else
			return false;
	}
}

/**
 * TPagerPageChangedEventParameter class
 *
 * TPagerPageChangedEventParameter encapsulates the parameter data for
 * {@link TPager::onPageIndexChanged PageIndexChanged} event of {@link TPager} controls.
 *
 * The {@link getCommandSource CommandSource} property refers to the control
 * that originally raises the OnCommand event, while {@link getNewPageIndex NewPageIndex}
 * returns the new page index carried with the page command.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0.2
 */
class TPagerPageChangedEventParameter extends TEventParameter
{
	/**
	 * @var integer new page index
	 */
	private $_newIndex;
	/**
	 * @var TControl original event sender
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TControl the control originally raises the <b>OnCommand</b> event.
	 * @param integer new page index
	 */
	public function __construct($source,$newPageIndex)
	{
		$this->_source=$source;
		$this->_newIndex=$newPageIndex;
	}

	/**
	 * @return TControl the control originally raises the <b>OnCommand</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}

	/**
	 * @return integer new page index
	 */
	public function getNewPageIndex()
	{
		return $this->_newIndex;
	}
}

?>