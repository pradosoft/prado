<?php
/**
 * TRepeater class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Using TDataBoundControl cass
 */
Prado::using('System.Web.UI.WebControls.TDataBoundControl');

/**
 * TRepeater class
 *
 * TRepeater displays its content defined in templates repeatedly based on
 * the given data specified by the {@link setDataSource DataSource} or
 * {@link setDataSourceID DataSourceID} property. The templates can contain
 * static text, controls and special tags.
 *
 * The {@link setHeaderTemplate HeaderTemplate} property specifies the content
 * template that will be displayed at the beginning, while
 * {@link setFooterTemplate FooterTemplate} at the end.
 * If present, these two templates will only be rendered when the repeater is
 * given non-empty data. In this case, for each data item the content defined
 * by {@link setItemTemplate ItemTemplate} will be generated and displayed once.
 * If {@link setAlternatingItemTemplate AlternatingItemTemplate} is not empty,
 * then the corresponding content will be displayed alternatively with that
 * in {@link setItemTemplate ItemTemplate}. The content in
 * {@link setSeparatorTemplate SeparatorTemplate}, if not empty, will be
 * displayed between items.
 *
 * You can retrive the repeated contents by the {@link getItems Items} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeater extends TDataBoundControl implements INamingContainer
{
	/**
	 * Number of seconds that a cached template will expire after
	 */
	const CACHE_EXPIRY=18000;
	/**
	 * @var string template for each item
	 */
	private $_itemTemplate='';
	/**
	 * @var string template for each alternating item
	 */
	private $_alternatingItemTemplate='';
	/**
	 * @var string template for header
	 */
	private $_headerTemplate='';
	/**
	 * @var string template for footer
	 */
	private $_footerTemplate='';
	/**
	 * @var string template for separator
	 */
	private $_separatorTemplate='';
	/**
	 * @var TRepeaterItemCollection list of repeater items
	 */
	private $_items=null;
	/**
	 * @var TRepeaterItem header item
	 */
	private $_header=null;
	/**
	 * @var TRepeaterItem footer item
	 */
	private $_footer=null;
	/**
	 * @var array in-memory cache of parsed templates
	 */
	private static $_templates=array();

	/**
	 * No body content should be added to repeater.
	 * This method is invoked when body content is parsed and added to this control.
	 * @param mixed body content to be added
	 */
	public function addParsedObject($object)
	{
	}

	/**
	 * @return string the template string for the item
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * Sets the template string for the item
	 * @param string the item template
	 */
	public function setItemTemplate($value)
	{
		$this->_itemTemplate=$value;
	}

	/**
	 * @return string the alternative template string for the item
	 */
	public function getAlternatingItemTemplate()
	{
		return $this->_alternatingItemTemplate;
	}

	/**
	 * Sets the alternative template string for the item
	 * @param string the alternative item template
	 */
	public function setAlternatingItemTemplate($value)
	{
		$this->_alternatingItemTemplate=$value;
	}

	/**
	 * @return string the header template string
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * Sets the header template.
	 * @param string the header template
	 */
	public function setHeaderTemplate($value)
	{
		$this->_headerTemplate=$value;
	}

	/**
	 * @return string the footer template string
	 */
	public function getFooterTemplate()
	{
		return $this->_footerTemplate;
	}

	/**
	 * Sets the footer template.
	 * @param string the footer template
	 */
	public function setFooterTemplate($value)
	{
		$this->_footerTemplate=$value;
	}

	/**
	 * @return string the separator template string
	 */
	public function getSeparatorTemplate()
	{
		return $this->_separatorTemplate;
	}

	/**
	 * Sets the separator template string
	 * @param string the separator template
	 */
	public function setSeparatorTemplate($value)
	{
		$this->_separatorTemplate=$value;
	}

	/**
	 * @return TRepeaterItem the header item
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @return TRepeaterItem the footer item
	 */
	public function getFooter()
	{
		return $this->_footer;
	}

	/**
	 * @return TRepeaterItemCollection list of {@link TRepeaterItem} controls
	 */
	public function getItems()
	{
		if(!$this->_items)
			$this->_items=new TRepeaterItemCollection;
		return $this->_items;
	}

	/**
	 * Creates a repeater item instance based on the item type and index.
	 * @param integer zero-based item index
	 * @param string item type, may be 'Header', 'Footer', 'Item', 'Separator', 'AlternatingItem', 'SelectedItem', 'EditItem'.
	 * @return TRepeaterItem created repeater item
	 */
	protected function createItem($itemIndex,$itemType)
	{
		return new TRepeaterItem($itemIndex,$itemType);
	}

	/**
	 * Creates a repeater item and does databinding if needed.
	 * This method invokes {@link createItem} to create a new repeater item.
	 * @param integer zero-based item index.
	 * @param string item type, may be 'Header', 'Footer', 'Item', 'Separator', 'AlternatingItem', 'SelectedItem', 'EditItem'.
	 * @param boolean whether to do databinding for the item
	 * @param mixed data to be associated with the item
	 */
	private function createItemInternal($itemIndex,$itemType,$dataBind,$dataItem)
	{
		$item=$this->createItem($itemIndex,$itemType);
		$this->initializeItem($item);
		$param=new TRepeaterItemEventParameter($item);
		if($dataBind)
		{
			$item->setDataItem($dataItem);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			$item->dataBind();
			$this->onItemDataBound($param);
			$item->setDataItem(null);
		}
		else
		{
			$this->onItemCreated($param);
			$this->getControls()->add($item);
		}
		return $item;
	}

	/**
	 * Initializes a repeater item.
	 * The item is added as a child of the repeater and the corresponding
	 * template is instantiated within the item.
	 * @param TRepeaterItem item to be initialized
	 */
	protected function initializeItem($item)
	{
		$tplContent='';
		switch($item->getItemType())
		{
			case 'Header': $tplContent=$this->_headerTemplate; break;
			case 'Footer': $tplContent=$this->_footerTemplate; break;
			case 'Item': $tplContent=$this->_itemTemplate; break;
			case 'Separator': $tplContent=$this->_separatorTemplate; break;
			case 'AlternatingItem': $tplContent=$this->_alternatingItemTemplate==='' ? $this->_itemTemplate : $this->_alternatingItemTemplate; break;
			case 'SelectedItem':
			case 'EditItem':
			default:
				break;
		}
		if($tplContent!=='')
		{
			$key=md5($tplContent);
			$contextPath=$this->getTemplateControl()->getTemplate()->getContextPath();
			if(($cache=$this->getApplication()->getCache())!==null)
			{
				if(($template=$cache->get($key))===null)
				{
					$template=new TTemplate($tplContent,$contextPath);
					$cache->set($key,$template,self::CACHE_EXPIRY);
				}
			}
			else
			{
				if(isset(self::$_templates[$key]))
					$template=self::$_templates[$key];
				else
				{
					$template=new TTemplate($tplContent,$contextPath);
					self::$_templates[$key]=$template;
				}
			}
			$template->instantiateIn($item);
		}
	}

	/**
	 * Renders the repeater.
	 * This method overrides the parent implementation by rendering the body
	 * content as the whole presentation of the repeater. Outer tag is not rendered.
	 * @param THtmlWriter writer
	 */
	protected function render($writer)
	{
		$this->renderContents($writer);
	}

	/**
	 * Saves item count in viewstate.
	 * This method is invoked right before control state is to be saved.
	 * @param mixed event parameter
	 */
	protected function onSaveState($param)
	{
		if($this->_items)
			$this->setViewState('ItemCount',$this->_items->getCount(),0);
		else
			$this->clearViewState('ItemCount');
	}

	/**
	 * Loads item count information from viewstate.
	 * This method is invoked right after control state is loaded.
	 * @param mixed event parameter
	 */
	protected function onLoadState($param)
	{
		if(!$this->getIsDataBound())
			$this->restoreItemsFromViewState();
		$this->clearViewState('ItemCount');
	}

	/**
	 * Creates repeater items based on viewstate information.
	 */
	protected function restoreItemsFromViewState()
	{
		$this->getControls()->clear();
		$items=$this->getItems();
		$items->clear();
		$this->_header=null;
		$this->_footer=null;
		if(($itemCount=$this->getViewState('ItemCount',0))>0)
		{
			if($this->_headerTemplate!=='')
				$this->_header=$this->createItemInternal(-1,'Header',false,null);
			$hasSeparator=$this->_separatorTemplate!=='';
			for($i=0;$i<$itemCount;++$i)
			{
				if($hasSeparator && $i>0)
					$this->createItemInternal($i-1,'Separator',false,null);
				$itemType=$i%2==0?'Item':'AlternatingItem';
				$items->add($this->createItemInternal($i,$itemType,false,null));
			}
			if($this->_footerTemplate!=='')
				$this->_footer=$this->createItemInternal(-1,'Footer',false,null);
		}
		$this->clearChildState();
	}

	/**
	 * Performs databinding to populate repeater items from data source.
	 * This method is invoked by dataBind().
	 * You may override this function to provide your own way of data population.
	 * @param Traversable the data
	 */
	protected function performDataBinding($data)
	{
		$this->getControls()->clear();
		$this->clearChildState();
		$items=$this->getItems();
		$items->clear();
		$itemIndex=0;
		$hasSeparator=$this->_separatorTemplate!=='';
		foreach($data as $dataItem)
		{
			if($itemIndex===0 && $this->_headerTemplate!=='')
				$this->_header=$this->createItemInternal(-1,'Header',true,null);
			if($hasSeparator && $itemIndex>0)
				$this->createItemInternal($itemIndex-1,'Separator',true,null);
			$itemType=$itemIndex%2==0?'Item':'AlternatingItem';
			$items->add($this->createItemInternal($itemIndex,$itemType,true,$dataItem));
			$itemIndex++;
		}
		if($itemIndex>0 && $this->_footerTemplate!=='')
			$this->_footer=$this->createItemInternal(-1,'Footer',true,null);
		$this->setViewState('ItemCount',$itemIndex,0);
	}

	/**
	 * Raises <b>ItemCreated</b> event.
	 * This method is invoked after a repeater item is created and instantiated with
	 * template, but before added to the page hierarchy.
	 * The {@link TRepeaterItem} control responsible for the event
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * can be determined from the event parameter.
	 * @param TRepeaterItemEventParameter event parameter
	 */
	protected function onItemCreated($param)
	{
		$this->raiseEvent('ItemCreated',$this,$param);
	}

	/**
	 * Raises <b>ItemDataBound</b> event.
	 * This method is invoked right after an item is data bound.
	 * The {@link TRepeaterItem} control responsible for the event
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * can be determined from the event parameter.
	 * @param TRepeaterItemEventParameter event parameter
	 */
	protected function onItemDataBound($param)
	{
		$this->raiseEvent('ItemDataBound',$this,$param);
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation to handle
	 * {@link onItemCommand ItemCommand} event which is bubbled from
	 * {@link TRepeaterItem} child controls.
	 * This method should only be used by control developers.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	protected function onBubbleEvent($sender,$param)
	{
		if($param instanceof TRepeaterCommandEventParameter)
		{
			$this->onItemCommand($param);
			return true;
		}
		else
			return false;
	}

	/**
	 * Raises <b>ItemCommand</b> event.
	 * This method is invoked after a button control in
	 * a template raises <b>Command</b> event.
	 * The {@link TRepeaterItem} control responsible for the event
	 * can be determined from the event parameter.
	 * The event parameter also contains the information about
	 * the initial sender of the <b>Command</b> event, command name
	 * and command parameter.
	 * You may override this method to provide customized event handling.
	 * Be sure to call parent's implementation so that
	 * event handlers have chance to respond to the event.
	 * @param TRepeaterCommandEventParameter event parameter
	 */
	protected function onItemCommand($param)
	{
		$this->raiseEvent('ItemCommand',$this,$param);
	}
}

/**
 * TRepeaterItemEventParameter class
 *
 * TRepeaterItemEventParameter encapsulates the parameter data for
 * {@link TRepeater::onItemCreated ItemCreated} event of {@link TRepeater} controls.
 * The {@link getItem Item} property indicates the repeater item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeaterItemEventParameter extends TEventParameter
{
	/**
	 * The TRepeaterItem control responsible for the event.
	 * @var TRepeaterItem
	 */
	private $_item=null;

	/**
	 * Constructor.
	 * @param TRepeaterItem repeater item related with the corresponding event
	 */
	public function __construct(TRepeaterItem $item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TRepeaterItem repeater item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}

/**
 * TRepeaterCommandEventParameter class
 *
 * TRepeaterCommandEventParameter encapsulates the parameter data for
 * {@link TRepeater::onItemCommand ItemCommand} event of {@link TRepeater} controls.
 *
 * The {@link getItem Item} property indicates the repeater item related with the event.
 * The {@link getCommandSource CommandSource} refers to the control that originally
 * raises the Command event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeaterCommandEventParameter extends TCommandEventParameter
{
	/**
	 * @var TRepeaterItem the TRepeaterItem control responsible for the event.
	 */
	private $_item=null;
	/**
	 * @var TControl the control originally raises the <b>Command</b> event.
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TRepeaterItem repeater item responsible for the event
	 * @param TControl original event sender
	 * @param TCommandEventParameter original event parameter
	 */
	public function __construct($item,$source,TCommandEventParameter $param)
	{
		$this->_item=$item;
		$this->_source=$source;
		parent::__construct($param->getCommandName(),$param->getCommandParameter());
	}

	/**
	 * @return TRepeaterItem the TRepeaterItem control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return TControl the control originally raises the <b>Command</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}
}

/**
 * TRepeaterItem class
 *
 * A TRepeaterItem control represents an item in the {@link TRepeater} control,
 * such as heading section, footer section, or a data item.
 * The index and data value of the item can be accessed via {@link getItemIndex ItemIndex}>
 * and {@link getDataItem DataItem} properties, respectively. The type of the item
 * is given by {@link getItemType ItemType} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeaterItem extends TControl implements INamingContainer
{
	/**
	 * index of the data item in the Items collection of repeater
	 */
	private $_itemIndex='';
	/**
	 * type of the TRepeaterItem
	 * @var string
	 */
	private $_itemType='';
	/**
	 * value of the data item
	 * @var mixed
	 */
	private $_dataItem=null;

	/**
	 * Constructor.
	 * @param integer zero-based index of the item in the item collection of repeater
	 * @param string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'.
	 */
	public function __construct($itemIndex,$itemType)
	{
		$this->_itemIndex=$itemIndex;
		$this->_itemType=TPropertyValue::ensureEnum($itemType,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
	}

	/**
	 * @return string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @return integer zero-based index of the item in the item collection of repeater
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * @return mixed data associated with the item
	 */
	public function getDataItem()
	{
		return $this->_dataItem;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setDataItem($value)
	{
		$this->_dataItem=$value;
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>Command</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	protected function onBubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$this->raiseBubbleEvent($this,new TRepeaterCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
	}
}

/**
 * TRepeaterItemCollection class.
 *
 * TRepeaterItemCollection represents a collection of repeater items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeaterItemCollection extends TList
{
	/**
	 * Returns true only when the item to be added is a {@link TRepeaterItem}.
	 * This method is invoked before adding an item to the list.
	 * If it returns true, the item will be added to the list, otherwise not.
	 * @param mixed item to be added
	 * @return boolean whether the item can be added to the list
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof TRepeaterItem);
	}
}

?>