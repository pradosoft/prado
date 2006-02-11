<?php
/**
 * TDataGridColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TDataGridColumn class
 *
 * TDataGridColumn serves as the base class for the different column types of
 * the {@link TDataGrid} control.
 * TDataGridColumn defines the properties and methods that are common among
 * all datagrid column types. In particular, it initializes header and footer
 * cells according to {@link setHeaderText HeaderText} and {@link getHeaderStyle HeaderStyle}
 * {@link setFooterText FooterText} and {@link getFooterStyle FooterStyle} properties.
 * If {@link setHeaderImageUrl HeaderImageUrl} is specified, the image
 * will be displayed instead in the header cell.
 * The {@link getItemStyle ItemStyle} is applied to cells that belong to
 * non-header and -footer datagrid items.
 *
 * When the datagrid enables sorting, if the {@link setSortExpression SortExpression}
 * is not empty, the header cell will display a button (linkbutton or imagebutton)
 * that will bubble the sort command event to the datagrid.
 *
 * The following datagrid column types are provided by the framework currently,
 * - {@link TBoundColumn}, associated with a specific field in datasource and displays the corresponding data.
 * - {@link TEditCommandColumn}, displaying edit/update/cancel command buttons
 * - {@link TButtonColumn}, displaying generic command buttons that may be bound to specific field in datasource.
 * - {@link THyperLinkColumn}, displaying a hyperlink that may be bound to specific field in datasource.
 * - {@link TCheckBoxColumn}, displaying a checkbox that may be bound to specific field in datasource.
 * - {@link TTemplateColumn}, displaying content based on templates.
 *
 * To create your own column class, simply override {@link initializeCell()} method,
 * which is the major logic for managing the data and presentation of cells in the column.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
abstract class TDataGridColumn extends TComponent
{
	private $_owner=null;
	private $_viewState=array();

	/**
	 * @return string the text to be displayed in the header of this column
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText','');
	}

	/**
	 * @param string text to be displayed in the header of this column
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText',$value,'');
	}

	/**
	 * @return string the url of the image to be displayed in header
	 */
	public function getHeaderImageUrl()
	{
		return $this->getViewState('HeaderImageUrl','');
	}

	/**
	 * @param string the url of the image to be displayed in header
	 */
	public function setHeaderImageUrl($value)
	{
		$this->setViewState('HeaderImageUrl',$value,'');
	}

	/**
	 * @param boolean whether to create a style if previously not existing
	 * @return TTableItemStyle the style for header
	 */
	public function getHeaderStyle($createStyle=true)
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null && $createStyle)
		{
			$style=new TTableItemStyle;
			$this->setViewState('HeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string the text to be displayed in the footer of this column
	 */
	public function getFooterText()
	{
		return $this->getViewState('FooterText','');
	}

	/**
	 * @param string text to be displayed in the footer of this column
	 */
	public function setFooterText($value)
	{
		$this->setViewState('FooterText',$value,'');
	}

	/**
	 * @param boolean whether to create a style if previously not existing
	 * @return TTableItemStyle the style for footer
	 */
	public function getFooterStyle($createStyle=true)
	{
		if(($style=$this->getViewState('FooterStyle',null))===null && $createStyle)
		{
			$style=new TTableItemStyle;
			$this->setViewState('FooterStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @param boolean whether to create a style if previously not existing
	 * @return TTableItemStyle the style for item
	 */
	public function getItemStyle($createStyle=true)
	{
		if(($style=$this->getViewState('ItemStyle',null))===null && $createStyle)
		{
			$style=new TTableItemStyle;
			$this->setViewState('ItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string the name of the field or expression for sorting
	 */
	public function getSortExpression()
	{
		return $this->getViewState('SortExpression','');
	}

	/**
	 * @param string the name of the field or expression for sorting
	 */
	public function setSortExpression($value)
	{
		$this->setViewState('SortExpression',$value,'');
	}

	/**
	 * @return boolean whether the column is visible. Defaults to true.
	 */
	public function getVisible($checkParents=true)
	{
		return $this->getViewState('Visible',true);
	}

	/**
	 * @param boolean whether the column is visible
	 */
	public function setVisible($value)
	{
		$this->setViewState('Visible',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Returns a viewstate value.
	 *
	 * @param string the name of the viewstate value to be returned
	 * @param mixed the default value. If $key is not found in viewstate, $defaultValue will be returned
	 * @return mixed the viewstate value corresponding to $key
	 */
	protected function getViewState($key,$defaultValue=null)
	{
		return isset($this->_viewState[$key])?$this->_viewState[$key]:$defaultValue;
	}

	/**
	 * Sets a viewstate value.
	 *
	 * Make sure that the viewstate value must be serializable and unserializable.
	 * @param string the name of the viewstate value
	 * @param mixed the viewstate value to be set
	 * @param mixed default value. If $value===$defaultValue, the item will be cleared from the viewstate.
	 */
	protected function setViewState($key,$value,$defaultValue=null)
	{
		if($value===$defaultValue)
			unset($this->_viewState[$key]);
		else
			$this->_viewState[$key]=$value;
	}

	/**
	 * Loads persistent state values.
	 * @param mixed state values
	 */
	public function loadState($state)
	{
		$this->_viewState=$state;
	}

	/**
	 * Saves persistent state values.
	 * @return mixed values to be saved
	 */
	public function saveState()
	{
		return $this->_viewState;
	}

	/**
	 * @return TDataGrid datagrid that owns this column
	 */
	public function getOwner()
	{
		return $this->_owner;
	}

	/**
	 * @param TDataGrid datagrid object that owns this column
	 */
	public function setOwner(TDataGrid $value)
	{
		$this->_owner=$value;
	}

	/**
	 * Initializes the column.
	 * This method is invoked by {@link TDataGrid} when the column
	 * is about to be used to initialize datagrid items.
	 * Derived classes may override this method to do additional initialization.
	 */
	public function initialize()
	{
	}

	/**
	 * Fetches the value of the data at the specified field.
	 * If the data is an array, the field is used as an array key.
	 * If the data is an of {@link TMap}, {@link TList} or their derived class,
	 * the field is used as a key value.
	 * If the data is a component, the field is used as the name of a property.
	 * @param mixed data containing the field of value
	 * @param string the data field
	 * @return mixed data value at the specified field
	 * @throws TInvalidDataValueException if the data or the field is invalid.
	 */
	protected function getDataFieldValue($data,$field)
	{
		if(is_array($data))
			return $data[$field];
		else if(($data instanceof TMap) || ($data instanceof TList))
			return $data->itemAt($field);
		else if(($data instanceof TComponent) && $data->canGetProperty($field))
		{
			$getter='get'.$field;
			return $data->$getter();
		}
		else
			throw new TInvalidDataValueException('datagridcolumn_data_invalid',get_class($this),$field);
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * The default implementation sets the content of header and footer cells.
	 * If sorting is enabled by the grid and sort expression is specified in the column,
	 * the header cell will show a link/image button. Otherwise, the header/footer cell
	 * will only show static text/image.
	 * This method can be overriden to provide customized intialization to column cells.
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		switch($itemType)
		{
			case 'Header':
				$sortExpression=$this->getSortExpression();
				$allowSorting=$sortExpression!=='' && (!$this->_owner || $this->_owner->getAllowSorting());
				if($allowSorting)
				{
					if(($url=$this->getHeaderImageUrl())!=='')
					{
						$button=Prado::createComponent('System.Web.UI.WebControls.TImageButton');
						$button->setImageUrl($url);
						$button->setCommandName('Sort');
						$button->setCommandParameter($sortExpression);
						$button->setCausesValidation(false);
						$cell->getControls()->add($button);
					}
					else if(($text=$this->getHeaderText())!=='')
					{
						$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
						$button->setText($text);
						$button->setCommandName('Sort');
						$button->setCommandParameter($sortExpression);
						$button->setCausesValidation(false);
						$cell->getControls()->add($button);
					}
					else
						$cell->setText('&nbsp;');
				}
				else
				{
					if(($url=$this->getHeaderImageUrl())!=='')
					{
						$image=Prado::createComponent('System.Web.UI.WebControls.TImage');
						$image->setImageUrl($url);
						$cell->getControls()->add($image);
					}
					else
					{
						if(($text=$this->getHeaderText())==='')
							$text='&nbsp;';
						$cell->setText($text);
					}
				}
				break;
			case 'Footer':
				if(($text=$this->getFooterText())==='')
					$text='&nbsp;';
				$cell->setText($text);
				break;
		}
	}

	/**
	 * Formats the text value according to format string.
	 * This method invokes the {@link sprintf} to do string formatting.
	 * If the format string is empty, the original value is converted into
	 * a string and returned.
	 * @param string format string
	 * @param mixed the data associated with the cell
	 * @return string the formatted result
	 */
	protected function formatDataValue($formatString,$value)
	{
		return $formatString===''?TPropertyValue::ensureString($value):sprintf($formatString,$value);
	}
}

?>