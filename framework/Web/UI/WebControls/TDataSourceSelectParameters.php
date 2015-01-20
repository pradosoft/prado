<?php
/**
 * TDataSourceSelectParameters, TDataSourceView, TReadOnlyDataSourceView class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TDataSourceSelectParameters class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataSourceSelectParameters extends TComponent
{
	private $_retrieveTotalRowCount=false;
	private $_startRowIndex=0;
	private $_totalRowCount=0;
	private $_maximumRows=0;

	public function getStartRowIndex()
	{
		return $this->_startRowIndex;
	}

	public function setStartRowIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=0;
		$this->_startRowIndex=$value;
	}

	public function getMaximumRows()
	{
		return $this->_maximumRows;
	}

	public function setMaximumRows($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=0;
		$this->_maximumRows=$value;
	}

	public function getRetrieveTotalRowCount()
	{
		return $this->_retrieveTotalRowCount;
	}

	public function setRetrieveTotalRowCount($value)
	{
		$this->_retrieveTotalRowCount=TPropertyValue::ensureBoolean($value);
	}

	public function getTotalRowCount()
	{
		return $this->_totalRowCount;
	}

	public function setTotalRowCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=0;
		$this->_totalRowCount=$value;
	}
}