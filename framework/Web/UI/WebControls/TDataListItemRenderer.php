<?php
/**
 * TDataListItemRenderer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.WebControls
 */

Prado::using('System.Web.UI.WebControls.TDataList');

/**
 * TDataListItemRenderer class
 *
 * TDataListItemRenderer can be used as a convenient base class to
 * define an item renderer class for {@link TDataList}.
 *
 * Because TDataListItemRenderer extends from {@link TTemplateControl}, derived child classes
 * can have templates to define their presentational layout.
 *
 * TDataListItemRenderer implements {@link IItemDataRenderer} interface,
 * which enables the following properties that are related with data-bound controls:
 * - {@link getItemIndex ItemIndex}: zero-based index of this control in the datalist item collection.
 * - {@link getItemType ItemType}: item type of this control, such as TListItemType::AlternatingItem
 * - {@link getData Data}: data associated with this control

 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.1.0
 */
class TDataListItemRenderer extends TTemplateControl implements IItemDataRenderer, IStyleable
{
	/**
	 * index of the data item in the Items collection of TDataList
	 * @var integer
	 */
	private $_itemIndex;
	/**
	 * type of the TDataListItem
	 * @var TListItemType
	 */
	private $_itemType;
	/**
	 * value of the data associated with this item
	 * @var mixed
	 */
	private $_data;

	/**
	 * Creates a style object to be used by the control.
	 * This method may be overriden by controls to provide customized style.
	 * @return TStyle
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return boolean whether the control has defined any style information
	 */
	public function getHasStyle()
	{
		return $this->getViewState('Style',null)!==null;
	}

	/**
	 * @return TStyle the object representing the css style of the control
	 */
	public function getStyle()
	{
		if($style=$this->getViewState('Style',null))
			return $style;
		else
		{
			$style=$this->createStyle();
			$this->setViewState('Style',$style,null);
			return $style;
		}
	}

	/**
	 * Removes all style data.
	 */
	public function clearStyle()
	{
		$this->clearViewState('Style');
	}

	/**
	 * @return TListItemType item type
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param TListItemType item type.
	 */
	public function setItemType($value)
	{
		$this->_itemType=TPropertyValue::ensureEnum($value,'TListItemType');
	}

	/**
	 * @return integer zero-based index of the item in the item collection of datalist
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param integer zero-based index of the item.
	 */
	public function setItemIndex($value)
	{
		$this->_itemIndex=TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return mixed data associated with the item
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setData($value)
	{
		$this->_data=$value;
	}

	/**
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
			$this->raiseBubbleEvent($this,new TDataListCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
	}

	/**
	 * Returns the tag name used for this control.
	 * By default, the tag name is 'span'.
	 * You can override this method to provide customized tag names.
	 * If the tag name is empty, the opening and closing tag will NOT be rendered.
	 * @return string tag name of the control to be rendered
	 */
	protected function getTagName()
	{
		return 'span';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * By default, this method renders the style string.
	 * The method can be overriden to provide customized attribute rendering.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if($style=$this->getViewState('Style',null))
			$style->addAttributesToRender($writer);
	}

	/**
	 * Renders the control.
	 * This method overrides the parent implementation by replacing it with
	 * the following sequence:
	 * - {@link renderBeginTag}
	 * - {@link renderContents}
	 * - {@link renderEndTag}
	 * If the {@link getTagName TagName} is empty, only {@link renderContents} is invoked.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if($this->getTagName()!=='')
		{
			$this->renderBeginTag($writer);
			$this->renderContents($writer);
			$this->renderEndTag($writer);
		}
		else
			$this->renderContents();
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * This method is invoked when {@link getTagName TagName} is not empty.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag($this->getTagName());
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * By default, child controls and text strings will be rendered.
	 * You can override this method to provide customized content rendering.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		parent::renderChildren($writer);
	}

	/**
	 * Renders the closing tag for the control
	 * This method is invoked when {@link getTagName TagName} is not empty.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		$writer->renderEndTag();
	}
}

?>