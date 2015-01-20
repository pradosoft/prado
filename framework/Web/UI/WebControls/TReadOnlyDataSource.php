<?php
/**
 * IDataSource, TDataSourceControl, TReadOnlyDataSource class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TDataSourceControl class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TReadOnlyDataSource extends TDataSourceControl
{
	private $_dataSource;
	private $_dataMember;

	public function __construct($dataSource,$dataMember)
	{
		if(!is_array($dataSource) && !($dataSource instanceof IDataSource) && !($dataSource instanceof Traversable))
			throw new TInvalidDataTypeException('readonlydatasource_datasource_invalid');
		$this->_dataSource=$dataSource;
		$this->_dataMember=$dataMember;
	}

	public function getView($viewName)
	{
		if($this->_dataSource instanceof IDataSource)
			return $this->_dataSource->getView($viewName);
		else
			return new TReadOnlyDataSourceView($this,$this->_dataMember,$this->_dataSource);
	}
}