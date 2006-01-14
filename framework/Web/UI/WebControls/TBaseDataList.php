<?php
/**
 * TBaseDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TDataBoundControl class
 */
Prado::using('System.Web.UI.WebControls.TDataBoundControl');

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
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
abstract class TBaseDataList extends TDataBoundControl
{
	/**
	 * @var TList list of key values
	 */
	private $_dataKeys=null;

	/**
	 * No body content should be added to data list control.
	 * This method is invoked when body content is parsed and added to this control.
	 * @param mixed body content to be added
	 */
	public function addParsedObject($object)
	{
	}

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
	 * @return string caption of the table layout
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption','');
	}

	/**
	 * @param string caption of the table layout
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption','');
	}

	/**
	 * @return string alignment of the caption of the table layout. Defaults to 'NotSet'.
	 */
	public function getCaptionAlign()
	{
		return $this->getViewState('CaptionAlign','NotSet');
	}

	/**
	 * @return string alignment of the caption of the table layout.
	 * Valid values include 'NotSet','Top','Bottom','Left','Right'.
	 */
	public function setCaptionAlign($value)
	{
		$this->setViewState('CaptionAlign',TPropertyValue::ensureEnum($value,'NotSet','Top','Bottom','Left','Right'),'NotSet');
	}

	/**
	 * @return integer the cellspacing for the table layout. Defaults to -1, meaning not set.
	 */
	public function getCellSpacing()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellSpacing();
		else
			return -1;
	}

	/**
	 * @param integer the cellspacing for the table layout.
	 */
	public function setCellSpacing($value)
	{
		$this->getStyle()->setCellSpacing($value);
	}

	/**
	 * @return integer the cellpadding for the table layout. Defaults to -1, meaning not set.
	 */
	public function getCellPadding()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellPadding();
		else
			return -1;
	}

	/**
	 * @param integer the cellpadding for the table layout
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
	 * @return string the grid line setting of the table layout. Defaults to 'None'.
	 */
	public function getGridLines()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getGridLines();
		else
			return 'None';
	}

	/**
	 * Sets the grid line style of the table layout.
     * Valid values include 'None', 'Horizontal', 'Vertical', 'Both'.
	 * @param string the grid line setting of the table
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
		return $this->getViewState('DataKeyField','');
	}

	/**
	 * @param string the field of the data source that provides the keys of the list items.
	 */
	public function setDataKeyField($value)
	{
		$this->setViewState('DataKeyField',$value,'');
	}

	/**
	 * @return TList the keys used in the data listing control.
	 */
	public function getDataKeys()
	{
		if(!$this->_dataKeys)
			$this->_dataKeys=new TList;
		return $this->_dataKeys;
	}

	/**
	 * Raises SelectedIndexChanged event.
	 * This method is invoked when a different item is selected
	 * in a data listing control between posts to the server.
	 * @param mixed event parameter
	 */
	public function onSelectedIndexChanged($param)
	{
		$this->raiseEvent('SelectedIndexChanged',$this,$param);
	}
}

?>