<?php
/**
 * THyperLinkColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TDataGridColumn class file
 */
Prado::using('System.Web.UI.WebControls.TDataGridColumn');

/**
 * THyperLinkColumn class
 *
 * THyperLinkColumn contains a hyperlink for each item in the column.
 * You can set the text and the url of the hyperlink by <b>Text</b> and <b>NavigateUrl</b>
 * properties, respectively. You can also bind the text and url to specific
 * data field in datasource by setting <b>DataTextField</b> and <b>DataNavigateUrlField</b>.
 * Both can be formatted before rendering according to the <b>DataTextFormatString</b>
 * and <b>DataNavigateUrlFormatString</b> properties, respectively.
 * If both <b>Text</b> and <b>DataTextField</b> are present, the latter takes precedence.
 * The same rule applies to <b>NavigateUrl</b> and <b>DataNavigateUrlField</b> properties.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THyperLinkColumn extends TDataGridColumn
{
	/**
	 * @return string the text caption of the hyperlink
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text caption of the hyperlink.
	 * @param string the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the field name from the data source to bind to the hyperlink caption
	 */
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField','');
	}

	/**
	 * @param string the field name from the data source to bind to the hyperlink caption
	 */
	public function setDataTextField($value)
	{
		$this->setViewState('DataTextField',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the formatting string used to control how the hyperlink caption will be displayed.
	 */
	public function getDataTextFormatString()
	{
		return $this->getViewState('DataTextFormatString','');
	}

	/**
	 * @param string the formatting string used to control how the hyperlink caption will be displayed.
	 */
	public function setDataTextFormatString($value)
	{
		$this->setViewState('DataTextFormatString',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the URL to link to when the hyperlink is clicked.
	 */
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl','');
	}

	/**
	 * Sets the URL to link to when the hyperlink is clicked.
	 * @param string the URL
	 */
	public function setNavigateUrl($value)
	{
		$this->setViewState('NavigateUrl',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the field name from the data source to bind to the navigate url of hyperlink
	 */
	public function getDataNavigateUrlField()
	{
		return $this->getViewState('DataNavigateUrlField','');
	}

	/**
	 * @param string the field name from the data source to bind to the navigate url of hyperlink
	 */
	public function setDataNavigateUrlField($value)
	{
		$this->setViewState('DataNavigateUrlField',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the formatting string used to control how the navigate url of hyperlink will be displayed.
	 */
	public function getDataNavigateUrlFormatString()
	{
		return $this->getViewState('DataNavigateUrlFormatString','');
	}

	/**
	 * @param string the formatting string used to control how the navigate url of hyperlink will be displayed.
	 */
	public function setDataNavigateUrlFormatString($value)
	{
		$this->setViewState('DataNavigateUrlFormatString',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the target window or frame to display the Web page content linked to when the hyperlink is clicked.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target','');
	}

	/**
	 * Sets the target window or frame to display the Web page content linked to when the hyperlink is clicked.
	 * @param string the target window, valid values include '_blank', '_parent', '_self', '_top' and empty string.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates a hyperlink within the cell.
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		parent::initializeCell($cell,$columnIndex,$itemType);
		if($itemType==='Item' || $itemType==='AlternatingItem' || $itemType==='SelectedItem' || $itemType==='EditItem')
		{
			$link=Prado::createComponent('System.Web.UI.WebControls.THyperLink');
			$link->setText($this->getText());
			$link->setNavigateUrl($this->getNavigateUrl());
			$link->setTarget($this->getTarget());
			if($this->getDataTextField()!=='' || $this->getDataNavigateUrlField()!=='')
				$link->attachEventHandler('DataBinding',array($this,'dataBindColumn'));
			$cell->getControls()->add($link);
		}
	}

	public function dataBindColumn($sender,$param)
	{
		$item=$sender->getNamingContainer();
		$data=$item->getDataItem();
		if(($field=$this->getDataTextField())!=='')
		{
			$value=$this->getDataFieldValue($data,$field);
			$text=$this->formatDataValue($this->getDataTextFormatString(),$value);
			$sender->setText($text);
		}
		if(($field=$this->getDataNavigateUrlField())!=='')
		{
			$value=$this->getDataFieldValue($data,$field);
			$url=$this->formatDataValue($this->getDataNavigateUrlFormatString(),$value);
			$sender->setNavigateUrl($url);
		}
	}
}

?>