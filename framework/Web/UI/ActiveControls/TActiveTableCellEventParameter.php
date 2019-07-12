<?php
/**
 * TActiveTableCell class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TActiveTableCellEventParameter class.
 *
 * The TActiveTableCellEventParameter provides the parameter passed during the callback
 * requestion in the {@link getCallbackParameter CallbackParameter} property. The
 * callback response content (e.g. new HTML content) must be rendered
 * using an THtmlWriter obtained from the {@link getNewWriter NewWriter}
 * property, which returns a <b>NEW</b> instance of TCallbackResponseWriter.
 *
 * The {@link getSelectedCellIndex SelectedCellIndex} is a zero-based index of the
 * TActiveTableCell , -1 if the cell is not part of the cell collection (this shouldn't
 * happen though since an exception is thrown before).
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.9
 */
class TActiveTableCellEventParameter extends TCallbackEventParameter
{

	/**
	 * @var int the zero-based index of the cell.
	 */
	private $_selectedCellIndex = -1;

	/**
	 * Creates a new TActiveTableRowEventParameter.
	 * @param mixed $response
	 * @param mixed $parameter
	 * @param mixed $index
	 */
	public function __construct($response, $parameter, $index = -1)
	{
		parent::__construct($response, $parameter);
		$this->_selectedCellIndex = $index;
	}

	/**
	 * Returns the zero-based index of the {@link TActiveTableCell} within the
	 * {@link TTableCellCollection} of the parent {@link TTableRow} control.
	 * @return int the zero-based index of the cell.
	 */
	public function getSelectedCellIndex()
	{
		return $this->_selectedCellIndex;
	}
}
