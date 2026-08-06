<?php

/**
 * TTableColumnGroup class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTableColumnGroup class.
 *
 * TTableColumnGroup represents the HTML `<colgroup>` element. Column groups
 * belong to the {@see TTable::getColumnGroups ColumnGroups} of a {@see TTable}
 * and render between the table caption and the table rows. A group either spans
 * columns itself via {@see setSpan Span} or contains {@see TTableColumn}
 * (`<col>`) children in its {@see getColumns Columns}; the HTML specification
 * forbids the `span` attribute on a `<colgroup>` with `<col>` children, so
 * {@see getSpan Span} is not rendered when {@see getColumns Columns} is
 * non-empty.
 *
 * The styling properties `CssClass`, `Width`, and `Style` are inherited from
 * {@see TTableColumn}.
 *
 * Template usage:
 * ```html
 * <com:TTable>
 *     <com:TTableColumnGroup CssClass="rowLabels" />
 *     <com:TTableColumnGroup>
 *         <com:TTableColumn Width="8em" />
 *         <com:TTableColumn Span="2" CssClass="numeric" />
 *     </com:TTableColumnGroup>
 *     <com:TTableRow> ... </com:TTableRow>
 * </com:TTable>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTableColumnGroup extends TTableColumn
{
	private ?TTableColumnCollection $_columns = null;

	/**
	 * @return string the tag name of the element
	 */
	protected function getTagName()
	{
		return 'colgroup';
	}

	/**
	 * @return TTableColumnCollection the list of {@see TTableColumn} (`<col>`) children
	 */
	public function getColumns()
	{
		if ($this->_columns === null) {
			$this->_columns = new TTableColumnCollection();
		}
		return $this->_columns;
	}

	/**
	 * Adds object parsed from template to the column group.
	 * This method adds only {@see TTableColumn} objects into the
	 * {@see getColumns Columns} collection. All other objects are ignored.
	 * @param mixed $object object parsed from template
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TTableColumn && !($object instanceof self)) {
			$this->getColumns()->add($object);
		}
	}

	/**
	 * Renders the column group and its `<col>` children.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag($this->getTagName());
		foreach ($this->getColumns() as $column) {
			$column->render($writer);
		}
		$writer->renderEndTag();
	}

	/**
	 * Adds attribute name-value pairs to renderer. The `span` attribute is
	 * omitted when the group contains `<col>` children, per the HTML specification.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getColumns()->getCount() === 0) {
			parent::addAttributesToRender($writer);
		} else {
			$this->addStyleAttributesToRender($writer);
		}
	}
}
