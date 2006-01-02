<?php

class TTable extends TWebControl
{
	private $_rows=null;

	protected function getTagName()
	{
		return 'table';
	}

	public function addParsedObject($object)
	{
		if($object instanceof TTableRow)
			$this->getRows()->add($object);
	}

	protected function createStyle()
	{
		return new TTableStyle;
	}

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
	 * @return array list of TTableRow components
	 */
	public function getRows()
	{
		if(!$this->_rows)
			$this->_rows=new TTableRowCollection($this);
		return $this->_rows;
	}

	public function getCaption()
	{
		return $this->getViewState('Caption','');
	}

	public function setCaption($value)
	{
		$this->setViewState('Caption',$value,'');
	}

	public function getCaptionAlign()
	{
		return $this->getViewState('CaptionAlign','');
	}

	public function setCaptionAlign($value)
	{
		$this->setViewState('CaptionAlign',TPropertyValue::ensureEnum($value,'NotSet','Top','Bottom','Left','Right'),'NotSet');
	}

	/**
	 * @return integer the cellspacing for the table keeping the checkbox list. Defaults to -1, meaning not set.
	 */
	public function getCellSpacing()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellSpacing();
		else
			return -1;
	}

	/**
	 * Sets the cellspacing for the table keeping the checkbox list.
	 * @param integer the cellspacing for the table keeping the checkbox list.
	 */
	public function setCellSpacing($value)
	{
		$this->getStyle()->setCellSpacing($value);
	}

	/**
	 * @return integer the cellpadding for the table keeping the checkbox list. Defaults to -1, meaning not set.
	 */
	public function getCellPadding()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellPadding();
		else
			return -1;
	}

	/**
	 * Sets the cellpadding for the table keeping the checkbox list.
	 * @param integer the cellpadding for the table keeping the checkbox list.
	 */
	public function setCellPadding($value)
	{
		$this->getStyle()->setCellPadding($value);
	}

	public function getHorizontalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getHorizontalAlign();
		else
			return 'NotSet';
	}

	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	public function getGridLines()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getGridLines();
		else
			return 'None';
	}

	public function setGridLines($value)
	{
		$this->getStyle()->setGridLines($value);
	}

	public function getBackImageUrl()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getBackImageUrl();
		else
			return 'None';
	}

	public function setBackImageUrl($value)
	{
		$this->getStyle()->setBackImageUrl($value);
	}

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

	protected function renderContents($writer)
	{
		if($this->_rows)
		{
			foreach($this->_rows as $row)
			{
				$row->renderControl($writer);
				$writer->writeLine();
			}
		}
	}
}


class TTableRow extends TWebControl
{
	private $_cells=null;

	protected function getTagName()
	{
		return 'tr';
	}

	public function addParsedObject($object)
	{
		if($object instanceof TTableCell)
			$this->getCells()->add($object);
	}

	protected function createStyle()
	{
		return new TTableItemStyle;
	}

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

	public function getCells()
	{
		if(!$this->_cells)
			$this->_cells=new TTableCellCollection($this);
		return $this->_cells;
	}

	public function getHorizontalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getHorizontalAlign();
		else
			return 'NotSet';
	}

	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	public function getVerticalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getVerticalAlign();
		else
			return 'NotSet';
	}

	public function setVerticalAlign($value)
	{
		$this->getStyle()->setVerticalAlign($value);
	}

	protected function renderContents($writer)
	{
		if($this->_cells)
		{
			foreach($this->_cells as $cell)
			{
				$cell->renderControl($writer);
				$writer->writeLine();
			}
		}
	}
}


class TTableCell extends TWebControl
{
	protected function getTagName()
	{
		return 'td';
	}

	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	public function getHorizontalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getHorizontalAlign();
		else
			return 'NotSet';
	}

	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	public function getVerticalAlign()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getVerticalAlign();
		else
			return 'NotSet';
	}

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
	 * @return boolean whether the text content wraps within a table cell.
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
	 * @param string the text content
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if(($colspan=$this->getColumnSpan())>0)
			$writer->addAttribute('colspan',"$colspan");
		if(($rowspan=$this->getColumnSpan())>0)
			$writer->addAttribute('rowspan',"$rowspan");
	}

	protected function renderContents($writer)
	{
		if(($text=$this->getText())==='')
			parent::renderContents($writer);
		else
			$writer->write($text);
	}

	/**
	 * Renders the body content of this cell.
	 * @return string the rendering result
	 */
	protected function renderBody()
	{
		$text=$this->getText();
		if($text!=='')
			return $text;
		else
			return parent::renderBody();
	}
}


class TTableHeaderCell extends TTableCell
{
	protected function getTagName()
	{
		return 'th';
	}

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

	public function getScope()
	{
		return $this->getViewState('Scope','NotSet');
	}

	public function setScope($value)
	{
		$this->setViewState('Scope',TPropertyValue::ensureEnum($value,'NotSet','Row','Column'),'NotSet');
	}

	public function getAbbreviatedText()
	{
		return $this->getViewState('AbbreviatedText','');
	}

	public function setAbbreviatedText($value)
	{
		$this->setViewState('AbbreviatedText',$value,'');
	}

	public function getCategoryText()
	{
		return $this->getViewState('CategoryText','');
	}

	public function setCategoryText($value)
	{
		$this->setViewState('CategoryText',$value,'');
	}
}
?>