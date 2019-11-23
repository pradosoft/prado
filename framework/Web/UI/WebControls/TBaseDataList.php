<?php
/**
 * TBaseDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Collections\TList;
use Prado\Util\TDataFieldAccessor;

/**
 * TBaseDataList class
 *
 * TBaseDataList is the base class for data listing controls, including
 * {@link TDataList} and {@link TDataGrid}.
 *
 * The key field in the data source is specified by {@link setKeyField KeyField},
 * while {@link getKeyValues KeyValues} stores the key values of each record in
 * a data listing control. You may use the list item index to obtain the corresponding
 * database key value.
 *
 * TBaseDataList also implements a few properties used for presentation based
 * on tabular layout. The {@link setCaption Caption}, whose alignment is
 * specified via {@link setCaptionAlign CaptionAlign}, is rendered as the table caption.
 * The table cellpadding and cellspacing are specified by
 * {@link setCellPadding CellPadding} and {@link setCellSpacing CellSpacing}
 * properties, respectively. The {@link setGridLines GridLines} specifies how
 * the table should display its borders, and the horizontal alignment of the table
 * content can be specified via {@link setHorizontalAlign HorizontalAlign}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
abstract class TBaseDataList extends TDataBoundControl
{
	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableStyle} to be used by the data list control.
	 * @return TTableStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableStyle;
	}

	/**
	 * @return int the cellspacing for the table layout. Defaults to -1, meaning not set.
	 * @deprecated use the border-spacing CSS property instead
	 */
	public function getCellSpacing()
	{
		if ($this->getHasStyle()) {
			return $this->getStyle()->getCellSpacing();
		} else {
			return -1;
		}
	}

	/**
	 * @param int $value the cellspacing for the table layout.
	 * @deprecated use the border-spacing CSS property instead
	 */
	public function setCellSpacing($value)
	{
		$this->getStyle()->setCellSpacing($value);
	}

	/**
	 * @return int the cellpadding for the table layout. Defaults to -1, meaning not set.
	 * @deprecated use border-collapse CSS property with its value set to collapse, and the padding property to the <td> element.
	 */
	public function getCellPadding()
	{
		if ($this->getHasStyle()) {
			return $this->getStyle()->getCellPadding();
		} else {
			return -1;
		}
	}

	/**
	 * @param int $value the cellpadding for the table layout
	 * @deprecated use border-collapse CSS property with its value set to collapse, and the padding property to the <td> element.
	 */
	public function setCellPadding($value)
	{
		$this->getStyle()->setCellPadding($value);
	}

	/**
	 * @return THorizontalAlign the horizontal alignment of the table content. Defaults to THorizontalAlign::NotSet.
	 */
	public function getHorizontalAlign()
	{
		if ($this->getHasStyle()) {
			return $this->getStyle()->getHorizontalAlign();
		} else {
			return THorizontalAlign::NotSet;
		}
	}

	/**
	 * @param THorizontalAlign $value the horizontal alignment of the table content.
	 */
	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	/**
	 * @return TTableGridLines the grid line setting of the table layout. Defaults to TTableGridLines::None.
	 */
	public function getGridLines()
	{
		if ($this->getHasStyle()) {
			return $this->getStyle()->getGridLines();
		} else {
			return TTableGridLines::None;
		}
	}

	/**
	 * Sets the grid line style of the table layout.
	 * @param TTableGridLines $value the grid line setting of the table
	 */
	public function setGridLines($value)
	{
		$this->getStyle()->setGridLines($value);
	}


	/**
	 * @return string the field of the data source that provides the keys of the list items.
	 */
	public function getDataKeyField()
	{
		return $this->getViewState('DataKeyField', '');
	}

	/**
	 * @param string $value the field of the data source that provides the keys of the list items.
	 */
	public function setDataKeyField($value)
	{
		$this->setViewState('DataKeyField', $value, '');
	}

	/**
	 * @return TList the keys used in the data listing control.
	 */
	public function getDataKeys()
	{
		if (($dataKeys = $this->getViewState('DataKeys', null)) === null) {
			$dataKeys = new TList;
			$this->setViewState('DataKeys', $dataKeys, null);
		}
		return $dataKeys;
	}

	/**
	 * Returns the value of the data at the specified field.
	 * If data is an array, TMap or TList, the value will be returned at the index
	 * of the specified field. If the data is a component with a property named
	 * as the field name, the property value will be returned.
	 * Otherwise, an exception will be raised.
	 * @param mixed $data data item
	 * @param mixed $field field name
	 * @throws TInvalidDataValueException if the data is invalid
	 * @return mixed data value at the specified field
	 */
	protected function getDataFieldValue($data, $field)
	{
		return TDataFieldAccessor::getDataFieldValue($data, $field);
	}

	/**
	 * Raises OnSelectedIndexChanged event.
	 * This method is invoked when a different item is selected
	 * in a data listing control between posts to the server.
	 * @param mixed $param event parameter
	 */
	public function onSelectedIndexChanged($param)
	{
		$this->raiseEvent('OnSelectedIndexChanged', $this, $param);
	}
}
