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
 * TDataGridColumn serves as the base class for the different column types of the TDataGrid control.
 * TDataGridColumn defines the properties and methods that are common to all column types.
 * In particular, it initializes header and footer cells according to
 * <b>HeaderText</b>, <b>HeaderStyle</b>, <b>FooterText</b>, and <b>FooterStyle</b>.
 * If <b>HeaderImageUrl</b> is specified, the image will be displayed instead in the header cell.
 * The <b>ItemStyle</b> is applied to non-header and -footer items.
 *
 * When the datagrid enables sorting, if the <b>SortExpression</b> is not empty,
 * the header cell will display a button (linkbutton or imagebutton) that will
 * bubble sort command event to the datagrid.
 *
 * The framework provides the following TDataGridColumn descendant classes,
 * - TBoundColumn, associated with a specific field in datasource and displays the corresponding data.
 * - TEditCommandColumn, displaying edit/update/cancel command buttons
 * - TButtonColumn, displaying generic command buttons that may be bound to specific field in datasource.
 * - THyperLinkColumn, displaying a hyperlink that may be boudn to specific field in datasource.
 * - TTemplateColumn, displaying content based on templates.
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
		$this->onColumnChanged();
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
		$this->onColumnChanged();
	}

	/**
	 * @return TTableItemStyle the style for header
	 */
	public function getHeaderStyle()
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null)
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
		$this->onColumnChanged();
	}

	/**
	 * @return TTableItemStyle the style for footer
	 */
	public function getFooterStyle()
	{
		if(($style=$this->getViewState('FooterStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('FooterStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for item
	 */
	public function getItemStyle()
	{
		if(($style=$this->getViewState('ItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('ItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @param string the name of the field or expression for sorting
	 */
	public function setSortExpression($value)
	{
		$this->setViewState('SortExpression',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the name of the field or expression for sorting
	 */
	public function getSortExpression()
	{
		return $this->getViewState('SortExpression','');
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
		$this->onColumnChanged();
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

	protected function loadState($state)
	{
		$this->_viewState=$state;
	}

	protected function saveState()
	{
		return $this->_viewState;
	}

	protected function getOwner()
	{
		return $this->_owner;
	}

	protected function setOwner(TDataGrid $value)
	{
		$this->_owner=$value;
	}

	protected function onColumnChanged()
	{
		if($this->_owner)
			$this->_owner->onColumnsChanged();
	}

	public function initialize()
	{
	}

	protected function getDataFieldValue($data,$field)
	{
		if(is_array($data))
			return $data[$field];
		else if($data instanceof TMap)
			return $data->itemAt($field);
		else if(($data instanceof TComponent) && $data->canGetProperty($field))
		{
			$getter='get'.$field;
			return $data->$getter();
		}
		else
			throw new TInvalidDataValueException('datagridcolumn_data_invalid');
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
}

?>