<?php
/**
 * TTableRow and TTableCellCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TTableCell and TTableHeaderCell classes
 */
Prado::using('System.Web.UI.WebControls.TTableCell');
Prado::using('System.Web.UI.WebControls.TTableHeaderCell');

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
			$this->_cells=new TTableCellCollection($this);
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
     * Valid values include 'NotSet','Top','Bottom','Middle'
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
	public function renderContents($writer)
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
	 * @var mixed cell collection owner
	 */
	private $_owner=null;

	/**
	 * Constructor.
	 * @param mixed cell collection owner
	 */
	public function __construct($owner=null)
	{
		$this->_owner=$owner;
	}


	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added table cell.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TTableCell object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TTableCell)
		{
			parent::insertAt($index,$item);
			if($this->_owner)
				$this->_owner->getControls()->insertAt($index,$item);
		}
		else
			throw new TInvalidDataTypeException('tablecellcollection_tablecell_required');
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a table cell.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item=parent::removeAt($index);
		if($item instanceof TTableCell)
			$this->_owner->getControls()->remove($item);
		return $item;
	}
}
?>