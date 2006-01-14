<?php
/**
 * TTable class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TTable class
 *
 * TTable displays an HTML table on a Web page.
 *
 * A table may have {@link setCaption Caption}, whose alignment is specified
 * via {@link setCaptionAlign CaptionAlign}. The table cellpadding and cellspacing
 * are specified via {@link setCellPadding CellPadding} and {@link setCellSpacing CellSpacing}
 * properties, respectively. The {@link setGridLines GridLines} specifies how
 * the table should display its borders. The horizontal alignment of the table
 * content can be specified via {@link setHorizontalAlign HorizontalAlign},
 * and {@link setBackImageUrl BackImageUrl} can assign a background image to the table.
 *
 * A TTable maintains a list of {@link TTableRow} controls in its
 * {@link getRows Rows} property. Each {@link TTableRow} represents
 * an HTML table row.
 *
 * To populate the table {@link getRows Rows}, you may either use control template
 * or dynamically create {@link TTableRow} in code.
 * In template, do as follows to create the table rows and cells,
 * <code>
 *   &lt;com:TTable&gt;
 *     &lt;com:TTableRow&gt;
 *       &lt;com:TTableCell Text="content" /&gt;
 *       &lt;com:TTableCell Text="content" /&gt;
 *     &lt;/com:TTableRow&gt;
 *     &lt;com:TTableRow&gt;
 *       &lt;com:TTableCell Text="content" /&gt;
 *       &lt;com:TTableCell Text="content" /&gt;
 *     &lt;/com:TTableRow&gt;
 *   &lt;com:TTable&gt;
 * </code>
 * The above can also be accomplished in code as follows,
 * <code>
 *   $table=new TTable;
 *   $row=new TTableRow;
 *   $cell=new TTableCell; $cell->Text="content"; $row->Cells->add($cell);
 *   $cell=new TTableCell; $cell->Text="content"; $row->Cells->add($cell);
 *   $table->Rows->add($row);
 *   $row=new TTableRow;
 *   $cell=new TTableCell; $cell->Text="content"; $row->Cells->add($cell);
 *   $cell=new TTableCell; $cell->Text="content"; $row->Cells->add($cell);
 *   $table->Rows->add($row);
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTable extends TWebControl
{
	/**
	 * @var TTableRowCollection row collection
	 */
	private $_rows=null;

	/**
	 * @return string tag name for the table
	 */
	protected function getTagName()
	{
		return 'table';
	}

	/**
	 * Adds object parsed from template to the control.
	 * This method adds only {@link TTableRow} objects into the {@link getRows Rows} collection.
	 * All other objects are ignored.
	 * @param mixed object parsed from template
	 */
	public function addParsedObject($object)
	{
		if($object instanceof TTableRow)
			$this->getRows()->add($object);
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableStyle} to be used by the table.
	 * @return TTableStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableStyle;
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$border=0;
		if($this->getHasStyle())
		{
			if($this->getGridLines()!=='None')
			{
				if(($border=$this->getBorderWidth())==='')
					$border=1;
				else
					$border=(int)$border;
			}
		}
		$writer->addAttribute('border',"$border");
	}

	/**
	 * @return TTableRowCollection list of {@link TTableRow} controls
	 */
	public function getRows()
	{
		if(!$this->_rows)
			$this->_rows=new TTableRowCollection();
		return $this->_rows;
	}

	/**
	 * @return string table caption
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption','');
	}

	/**
	 * @param string table caption
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption',$value,'');
	}

	/**
	 * @return string table caption alignment. Defaults to 'NotSet'.
	 */
	public function getCaptionAlign()
	{
		return $this->getViewState('CaptionAlign','NotSet');
	}

	/**
	 * @param string table caption alignment. Valid values include
	 * 'NotSet','Top','Bottom','Left','Right'.
	 */
	public function setCaptionAlign($value)
	{
		$this->setViewState('CaptionAlign',TPropertyValue::ensureEnum($value,'NotSet','Top','Bottom','Left','Right'),'NotSet');
	}

	/**
	 * @return integer the cellspacing for the table. Defaults to -1, meaning not set.
	 */
	public function getCellSpacing()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellSpacing();
		else
			return -1;
	}

	/**
	 * @param integer the cellspacing for the table. Defaults to -1, meaning not set.
	 */
	public function setCellSpacing($value)
	{
		$this->getStyle()->setCellSpacing($value);
	}

	/**
	 * @return integer the cellpadding for the table. Defaults to -1, meaning not set.
	 */
	public function getCellPadding()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellPadding();
		else
			return -1;
	}

	/**
	 * @param integer the cellpadding for the table. Defaults to -1, meaning not set.
	 */
	public function setCellPadding($value)
	{
		$this->getStyle()->setCellPadding($value);
	}

	/**
	 * @return string the horizontal alignment of the table content. Defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getHorizontalAlign();
		else
			return 'NotSet';
	}

	/**
	 * @param string the horizontal alignment of the table content.
	 * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'.
	 */
	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	/**
	 * @return string the grid line setting of the table. Defaults to 'None'.
	 */
	public function getGridLines()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getGridLines();
		else
			return 'None';
	}

	/**
	 * Sets the grid line style of the table.
     * Valid values include 'None', 'Horizontal', 'Vertical', 'Both'.
	 * @param string the grid line setting of the table
	 */
	public function setGridLines($value)
	{
		$this->getStyle()->setGridLines($value);
	}

	/**
	 * @return string the URL of the background image for the table
	 */
	public function getBackImageUrl()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getBackImageUrl();
		else
			return '';
	}

	/**
	 * Sets the URL of the background image for the table
	 * @param string the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->getStyle()->setBackImageUrl($value);
	}

	/**
	 * Renders the openning tag for the table control which will render table caption if present.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		parent::renderBeginTag($writer);
		if(($caption=$this->getCaption())!=='')
		{
			if(($align=$this->getCaptionAlign())!=='NotSet')
				$writer->addAttribute('align',$align);
			$writer->renderBeginTag('caption');
			$writer->write($caption);
			$writer->renderEndTag();
		}
	}

	/**
	 * Renders body contents of the table.
	 * @param THtmlWriter the writer used for the rendering purpose.
	 */
	protected function renderContents($writer)
	{
		if($this->_rows)
		{
			$writer->writeLine();
			foreach($this->_rows as $row)
			{
				$row->renderControl($writer);
				$writer->writeLine();
			}
		}
	}
}


/**
 * TTableRow class.
 *
 * TTableRow displays a table row. The table cells in the row can be accessed
 * via {@link getCells Cells}. The horizontal and vertical alignments of the row
 * are specified via {@link setHorizontalAlign HorizontalAlign} and
 * {@link setVerticalAlign VerticalAlign} properties, respectively.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableRow extends TWebControl
{
	/**
	 * @var TTableCellCollection cell collection
	 */
	private $_cells=null;

	/**
	 * @return string tag name for the table
	 */
	protected function getTagName()
	{
		return 'tr';
	}

	/**
	 * Adds object parsed from template to the control.
	 * This method adds only {@link TTableCell} objects into the {@link getCells Cells} collection.
	 * All other objects are ignored.
	 * @param mixed object parsed from template
	 */
	public function addParsedObject($object)
	{
		if($object instanceof TTableCell)
			$this->getCells()->add($object);
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableItemStyle} to be used by the table row.
	 * @return TStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return TTableCellCollection list of {@link TTableCell} controls
	 */
	public function getCells()
	{
		if(!$this->_cells)
			$this->_cells=new TTableCellCollection();
		return $this->_cells;
	}

	/**
	 * @return string the horizontal alignment of the contents within the table item, defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getHorizontalAlign();
		else
			return 'NotSet';
	}

	/**
	 * Sets the horizontal alignment of the contents within the table item.
     * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'
	 * @param string the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	/**
	 * @return string the vertical alignment of the contents within the table item, defaults to 'NotSet'.
	 */
	public function getVerticalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getVerticalAlign();
		else
			return 'NotSet';
	}

	/**
	 * Sets the vertical alignment of the contents within the table item.
     * Valid values include 'NotSet','Top','Bottom','Middel'
	 * @param string the horizontal alignment
	 */
	public function setVerticalAlign($value)
	{
		$this->getStyle()->setVerticalAlign($value);
	}

	/**
	 * Renders body contents of the table row
	 * @param THtmlWriter writer for the rendering purpose
	 */
	protected function renderContents($writer)
	{
		if($this->_cells)
		{
			$writer->writeLine();
			foreach($this->_cells as $cell)
			{
				$cell->renderControl($writer);
				$writer->writeLine();
			}
		}
	}
}


/**
 * TTableCell class.
 *
 * TTableCell displays a table cell on a Web page. Content of the table cell
 * is specified by the {@link setText Text} property. If {@link setText Text}
 * is empty, the body contents enclosed by the table cell component tag are rendered.
 * Note, {@link setText Text} is not HTML-encoded when displayed. So make sure
 * it does not contain dangerous characters.
 *
 * The horizontal and vertical alignments of the contents in the cell
 * are specified via {@link setHorizontalAlign HorizontalAlign} and
 * {@link setVerticalAlign VerticalAlign} properties, respectively.
 *
 * The colspan and rowspan of the cell are specified via {@link setColumnSpan ColumnSpan}
 * and {@link setRowSpan RowSpan} properties. And the {@link setWrap Wrap} property
 * indicates whether the contents in the cell should be wrapped.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableCell extends TWebControl
{
	/**
	 * @return string tag name for the table cell
	 */
	protected function getTagName()
	{
		return 'td';
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableItemStyle} to be used by the table cell.
	 * @return TStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return string the horizontal alignment of the contents within the table item, defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getHorizontalAlign();
		else
			return 'NotSet';
	}

	/**
	 * Sets the horizontal alignment of the contents within the table item.
     * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'
	 * @param string the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	/**
	 * @return string the vertical alignment of the contents within the table item, defaults to 'NotSet'.
	 */
	public function getVerticalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getVerticalAlign();
		else
			return 'NotSet';
	}

	/**
	 * Sets the vertical alignment of the contents within the table item.
     * Valid values include 'NotSet','Top','Bottom','Middel'
	 * @param string the horizontal alignment
	 */
	public function setVerticalAlign($value)
	{
		$this->getStyle()->setVerticalAlign($value);
	}

	/**
	 * @return integer the columnspan for the table cell, 0 if not set.
	 */
	public function getColumnSpan()
	{
		return $this->getViewState('ColumnSpan', 0);
	}

	/**
	 * Sets the columnspan for the table cell.
	 * @param integer the columnspan for the table cell, 0 if not set.
	 */
	public function setColumnSpan($value)
	{
		$this->setViewState('ColumnSpan', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return integer the rowspan for the table cell, 0 if not set.
	 */
	public function getRowSpan()
	{
		return $this->getViewState('RowSpan', 0);
	}

	/**
	 * Sets the rowspan for the table cell.
	 * @param integer the rowspan for the table cell, 0 if not set.
	 */
	public function setRowSpan($value)
	{
		$this->setViewState('RowSpan', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return boolean whether the text content wraps within a table cell. Defaults to true.
	 */
	public function getWrap()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getWrap();
		else
			return true;
	}

	/**
	 * Sets the value indicating whether the text content wraps within a table cell.
	 * @param boolean whether the text content wraps within a table cell.
	 */
	public function setWrap($value)
	{
		$this->getStyle()->setWrap($value);
	}

	/**
	 * @return string the text content of the table cell.
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text content of the table cell.
	 * If the text content is empty, body content (child controls) of the cell will be rendered.
	 * @param string the text content
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if(($colspan=$this->getColumnSpan())>0)
			$writer->addAttribute('colspan',"$colspan");
		if(($rowspan=$this->getRowSpan())>0)
			$writer->addAttribute('rowspan',"$rowspan");
	}

	/**
	 * Renders body contents of the table cell.
	 * @param THtmlWriter the writer used for the rendering purpose.
	 */
	protected function renderContents($writer)
	{
		if(($text=$this->getText())==='')
			parent::renderContents($writer);
		else
			$writer->write($text);
	}
}

/**
 * TTableHeaderCell class.
 *
 * TTableHeaderCell displays a table header cell on a Web page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableHeaderCell extends TTableCell
{
	/**
	 * @return string tag name for the table header cell
	 */
	protected function getTagName()
	{
		return 'th';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if(($scope=$this->getScope())!=='NotSet')
			$writer->addAttribute('scope',$scope==='Row'?'row':'col');
		if(($text=$this->getAbbreviatedText())!=='')
			$writer->addAttribute('abbr',$text);
		if(($text=$this->getCategoryText())!=='')
			$writer->addAttribute('axis',$text);
	}

	/**
	 * @return string the scope of the cells that the header cell applies to. Defaults to 'NotSet'.
	 */
	public function getScope()
	{
		return $this->getViewState('Scope','NotSet');
	}

	/**
	 * @param string the scope of the cells that the header cell applies to.
	 * Valid values include 'NotSet','Row','Column'.
	 */
	public function setScope($value)
	{
		$this->setViewState('Scope',TPropertyValue::ensureEnum($value,'NotSet','Row','Column'),'NotSet');
	}

	/**
	 * @return string  the abbr attribute of the HTML th element
	 */
	public function getAbbreviatedText()
	{
		return $this->getViewState('AbbreviatedText','');
	}

	/**
	 * @param string  the abbr attribute of the HTML th element
	 */
	public function setAbbreviatedText($value)
	{
		$this->setViewState('AbbreviatedText',$value,'');
	}

	/**
	 * @return string the axis attribute of the HTML th element
	 */
	public function getCategoryText()
	{
		return $this->getViewState('CategoryText','');
	}

	/**
	 * @param string the axis attribute of the HTML th element
	 */
	public function setCategoryText($value)
	{
		$this->setViewState('CategoryText',$value,'');
	}
}


/**
 * TTableRowCollection class.
 *
 * TTableRowCollection is used to maintain a list of rows belong to a table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableRowCollection extends TList
{
	/**
	 * Only string or instance of TControl can be added into collection.
	 * @param mixed the item to be added
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof TTableRow);
	}
}


/**
 * TTableCellCollection class.
 *
 * TTableCellCollection is used to maintain a list of cells belong to a table row.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableCellCollection extends TList
{
	/**
	 * Only string or instance of TTableCell can be added into collection.
	 * @param mixed the item to be added
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof TTableCell);
	}
}
?>