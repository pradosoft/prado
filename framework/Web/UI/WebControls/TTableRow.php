<?php
/**
 * TTableRow and TTableCellCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TTableRow class.
 *
 * TTableRow displays a table row. The table cells in the row can be accessed
 * via {@link getCells Cells}. The horizontal and vertical alignments of the row
 * are specified via {@link setHorizontalAlign HorizontalAlign} and
 * {@link setVerticalAlign VerticalAlign} properties, respectively.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TTableRow extends \Prado\Web\UI\WebControls\TWebControl
{
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
	 * @param mixed $object object parsed from template
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TTableCell) {
			$this->getCells()->add($object);
		}
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
	 * Creates a control collection object that is to be used to hold child controls
	 * @return TTableCellCollection control collection
	 * @see getControls
	 */
	protected function createControlCollection()
	{
		return new TTableCellCollection($this);
	}

	/**
	 * @return TTableCellCollection list of {@link TTableCell} controls
	 */
	public function getCells()
	{
		return $this->getControls();
	}

	/**
	 * @return string the horizontal alignment of the contents within the table item, defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		if ($this->getHasStyle()) {
			return $this->getStyle()->getHorizontalAlign();
		} else {
			return 'NotSet';
		}
	}

	/**
	 * Sets the horizontal alignment of the contents within the table item.
	 * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'
	 * @param string $value the horizontal alignment
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
		if ($this->getHasStyle()) {
			return $this->getStyle()->getVerticalAlign();
		} else {
			return 'NotSet';
		}
	}

	/**
	 * Sets the vertical alignment of the contents within the table item.
	 * Valid values include 'NotSet','Top','Bottom','Middle'
	 * @param string $value the horizontal alignment
	 */
	public function setVerticalAlign($value)
	{
		$this->getStyle()->setVerticalAlign($value);
	}

	/**
	 * @return TTableRowSection location of a row in a table. Defaults to TTableRowSection::Body.
	 */
	public function getTableSection()
	{
		return $this->getViewState('TableSection', TTableRowSection::Body);
	}

	/**
	 * @param TTableRowSection $value location of a row in a table.
	 */
	public function setTableSection($value)
	{
		$this->setViewState('TableSection', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TTableRowSection'), TTableRowSection::Body);
	}

	/**
	 * Renders body contents of the table row
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if ($this->getHasControls()) {
			$writer->writeLine();
			foreach ($this->getControls() as $cell) {
				$cell->renderControl($writer);
				$writer->writeLine();
			}
		}
	}
}
