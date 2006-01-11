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

Prado::using('System.Web.UI.WebControls.TDataBoundControl');

/**
 * TRepeater class
 *
 * TRepeater displays its content defined in templates repeatedly based on
 * the <b>DataSource</b> property. The <b>DataSource</b> property only accepts
 * objects that implement Iterator or IteratorAggregate interface. For convenience,
 * it also accepts an array.
 *
 * The <b>HeaderTemplate</b> property specifies the content template
 * that will be displayed at the beginning, while <b>FooterTemplate</b> at the last.
 * If present, these two templates will only be rendered when <b>DataSource</b> is set (not null).
 * If the <b>DataSource</b> contains item data, then for each item,
 * the content defined by <b>ItemTemplate</b> will be generated and displayed once.
 * If <b>AlternatingItemTemplate</b> is not empty, then the corresponding content will
 * be displayed alternatively with that in <b>ItemTemplate</b>. The content in
 * <b>SeparatorTemplate</b>, if not empty, will be displayed between two items.
 * These templates can contain static text, controls and special tags.
 *
 * Note, the templates are only parsed and instantiated upon <b>OnDataBinding</b>
 * event which is raised by calling <b>TControl::dataBind()</b> method. You may
 * call this method during <b>OnInit</b> or <b>OnLoad</b> life cycles.
 *
 * You can retrive the repeated contents by <b>Items</b>.
 * The number of repeated items is given by <b>ItemCount</b>.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeater extends TDataBoundControl implements INamingContainer
{
	const CACHE_EXPIRY=18000;
	private $_itemTemplate='';
	private $_alternatingItemTemplate='';
	private $_headerTemplate='';
	private $_footerTemplate='';
	private $_separatorTemplate='';
	private $_emptyTemplate='';
	private $_items=null;
	private $_header=null;
	private $_footer=null;
	private static $_templates=array();

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
	 * @return string the template string when there are no items
	 */
	public function getEmptyTemplate()
	{
		return $this->_emptyTemplate;
	}

	/**
	 * Sets the template string when there are no items
	 * @param string the empty template
	 */
	public function setEmptyTemplate($value)
	{
		$this->_emptyTemplate=$value;
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
	 * The template will be parsed immediately.
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
	 * The template will be parsed immediately.
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
	 * @return array list of TRepeaterItem control
	 */
	public function getItems()
	{
		if(!$this->_items)
			$this->_items=new TList;
		return $this->_items;
	}

	protected function createItem($itemIndex,$itemType)
	{
		return new TRepeaterItem($itemIndex,$itemType);
	}

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
			$template->instantiateIn($item,$this->getPage());
			$this->getControls()->add($item);
		}
	}

	private function createItemInternal($itemIndex,$itemType,$dataBind,$dataItem)
	{
		$item=$this->createItem($itemIndex,$itemType);
		$this->initializeItem($item);
		$param=new TRepeaterItemEventParameter($item);
		if($dataBind)
		{
			$item->setDataItem($dataItem);
			$this->onItemCreated($param);
			$item->dataBind();
			$this->onItemDataBound($param);
			$item->setDataItem(null);
		}
		else
			$this->onItemCreated($param);
		return $item;
	}

	protected function createChildControls()
	{
		$this->getControls()->clear();
		$items=$this->getItems();
		$items->clear();
		$this->_header=null;
		$this->_footer=null;
		if(($itemCount=$this->getViewState('ItemCount',null))!==null)
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
	 * Performs databinding to populate list items from data source.
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
		if($data!==null)
		{
			if($this->_headerTemplate!=='')
				$this->_header=$this->createItemInternal(-1,'Header',true,null);
			$hasSeparator=$this->_separatorTemplate!=='';
			foreach($data as $dataItem)
			{
				if($hasSeparator && $itemIndex>0)
					$this->createItemInternal($itemIndex-1,'Separator',true,null);
				$itemType=$itemIndex%2==0?'Item':'AlternatingItem';
				$items->add($this->createItemInternal($itemIndex,$itemType,true,$dataItem));
				$itemIndex++;
			}
			if($this->_footerTemplate!=='')
				$this->_footer=$this->createItemInternal(-1,'Footer',true,null);
			$this->setViewState('ItemCount',$itemIndex,0);
		}
		else
			$this->setViewState('ItemCount',$itemIndex,-1);
		$this->setChildControlsCreated(true);
	}

	/**
	 * Raises <b>OnItemCreated</b> event.
	 * This method is invoked after a repeater item is created.
	 * You may override this method to provide customized event handling.
	 * Be sure to call parent's implementation so that
	 * event handlers have chance to respond to the event.
	 * The TRepeaterItem control responsible for the event
	 * can be determined from the event parameter's <b>item</b>
	 * field.
	 * @param TRepeaterItemEventParameter event parameter
	 */
	protected function onItemCreated($param)
	{
		$this->raiseEvent('ItemCreated',$this,$param);
	}

	/**
	 * Handles <b>OnBubbleEvent</b>.
	 * This method overrides parent's implementation to handle
	 * <b>OnItemCommand</b> event that is bubbled from
	 * TRepeaterItem child controls.
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
	 * Raises <b>OnItemCommand</b> event.
	 * This method is invoked after a button control in
	 * a template raises <b>OnCommand</b> event.
	 * You may override this method to provide customized event handling.
	 * Be sure to call parent's implementation so that
	 * event handlers have chance to respond to the event.
	 * The TRepeaterItem control responsible for the event
	 * can be determined from the event parameter's <b>item</b>
	 * field. The initial sender of the <b>OnCommand</b> event
	 * is in <b>source</b> field. The command name and parameter
	 * are in <b>name</b> and <b>parameter</b> fields, respectively.
	 * @param TRepeaterCommandEventParameter event parameter
	 */
	protected function onItemCommand($param)
	{
		$this->raiseEvent('ItemCommand',$this,$param);
	}

	protected function onItemDataBound($param)
	{
		$this->raiseEvent('ItemDataBound',$this,$param);
	}
}

/**
 * TRepeaterItemEventParameter class
 *
 * TRepeaterItemEventParameter encapsulates the parameter data for <b>OnItemCreated</b>
 * event of TRepeater controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TRepeaterItemEventParameter extends TEventParameter
{
	/**
	 * The TRepeaterItem control responsible for the event.
	 * @var TRepeaterItem
	 */
	public $_item=null;

	public function __construct(TRepeaterItem $item)
	{
		$this->_item=$item;
	}

	public function getItem()
	{
		return $this->_item;
	}
}

/**
 * TRepeaterCommandEventParameter class
 *
 * TRepeaterCommandEventParameter encapsulates the parameter data for <b>OnItemCommand</b>
 * event of TRepeater controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TRepeaterCommandEventParameter extends TCommandEventParameter
{
	/**
	 * The TRepeaterItem control responsible for the event.
	 * @var TRepeaterItem
	 */
	public $_item=null;
	/**
	 * The control originally raises the <b>Command</b> event.
	 * @var TControl
	 */
	public $_source=null;

	public function __construct($item,$source,TCommandEventParameter $param)
	{
		$this->_item=$item;
		$this->_source=$source;
		parent::__construct($param->getCommandName(),$param->getCommandParameter());
	}

	public function getItem()
	{
		return $this->_item;
	}

	public function getCommandSource()
	{
		return $this->_source;
	}
}

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

	public function __construct($itemIndex,$itemType)
	{
		$this->_itemIndex=$itemIndex;
		$this->_itemType=TPropertyValue::ensureEnum($itemType,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
	}

	public function getItemType()
	{
		return $this->_itemType;
	}

	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	public function getDataItem()
	{
		return $this->_dataItem;
	}

	public function setDataItem($value)
	{
		$this->_dataItem=$value;
	}

	/**
	 * Handles <b>OnBubbleEvent</b>.
	 * This method overrides parent's implementation to bubble
	 * <b>OnItemCommand</b> event if an <b>OnCommand</b>
	 * event is bubbled from a child control.
	 * This method should only be used by control developers.
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

?>