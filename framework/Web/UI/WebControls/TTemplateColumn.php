<?php
/**
 * TTemplateColumn class file
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
 * TTemplateColumn class
 *
 * TTemplateColumn customizes the layout of controls in the column with templates.
 * In particular, you can specify <b>ItemTemplate</b>, <b>EditItemTemplate</b>
 * <b>HeaderTemplate</b> and <b>FooterTemplate</b> to customize specific
 * type of cells in the column.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTemplateColumn extends TDataGridColumn
{
	/**
	 * Number of seconds that a cached template will expire after
	 */
	const CACHE_EXPIRY=18000;
	/**
	 * Various item templates
	 * @var string
	 */
	private $_itemTemplate='';
	private $_editItemTemplate='';
	private $_headerTemplate='';
	private $_footerTemplate='';
	private static $_templates=array();

	/**
	 * @return string the edit item template string
	 */
	public function getEditItemTemplate()
	{
		return $this->_editItemTemplate;
	}

	/**
	 * Sets the edit item template string
	 * @param string the edit item template
	 */
	public function setEditItemTemplate($value)
	{
		$this->_editItemTemplate=$value;
	}

	/**
	 * @return string the template string for the item
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * Sets the template string for the item
	 * @param string the item template
	 */
	public function setItemTemplate($value)
	{
		$this->_itemTemplate=$value;
	}

	/**
	 * @return string the header template string
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * Sets the header template.
	 * The template will be parsed immediately.
	 * @param string the header template
	 */
	public function setHeaderTemplate($value)
	{
		$this->_headerTemplate=$value;
	}

	/**
	 * @return string the footer template string
	 */
	public function getFooterTemplate()
	{
		return $this->_footerTemplate;
	}

	/**
	 * Sets the footer template.
	 * The template will be parsed immediately.
	 * @param string the footer template
	 */
	public function setFooterTemplate($value)
	{
		$this->_footerTemplate=$value;
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It initializes the cell based on different templates
	 * (ItemTemplate, EditItemTemplate, HeaderTemplate, FooterTemplate).
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		parent::initializeCell($cell,$columnIndex,$itemType);
		$tplContent='';
		switch($itemType)
		{
			case 'Header':
				$tplContent=$this->_headerTemplate;
				break;
			case 'Footer':
				$tplContent=$this->_footerTemplate;
				break;
			case 'Item':
			case 'AlternatingItem':
			case 'SelectedItem':
				$tplContent=$this->_itemTemplate;
				break;
			case 'EditItem':
				$tplContent=$this->_editItemTemplate===''?$this->_itemTemplate:$this->_editItemTemplate;
				break;
		}
		if($tplContent!=='')
		{
			$cell->setText('');
			$cell->getControls()->clear();
			$this->createTemplate($tplContent)->instantiateIn($cell);
		}
	}

	/**
	 * Parses item template.
	 * This method uses caching technique to accelerate template parsing.
	 * @param string template string
	 * @return ITemplate parsed template object
	 */
	protected function createTemplate($str)
	{
		$key=md5($str);
		if(isset(self::$_templates[$key]))
			return self::$_templates[$key];
		else
		{
			$contextPath=$this->getOwner()->getTemplateControl()->getTemplate()->getContextPath();
			if(($cache=$this->getApplication()->getCache())!==null)
			{
				if(($template=$cache->get($key))===null)
				{
					$template=new TTemplate($str,$contextPath);
					$cache->set($key,$template,self::CACHE_EXPIRY);
				}
			}
			else
				$template=new TTemplate($str,$contextPath);
			self::$_templates[$key]=$template;
			return $template;
		}
	}
}

?>