<?php

/**
 * IDataSource, TDataSourceControl, TReadOnlyDataSource class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TDataSourceControl class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TReadOnlyDataSource extends TDataSourceControl
{
	private $_dataSource;
	private $_dataMember;

	public function __construct($dataSource, $dataMember)
	{
		if (!is_array($dataSource) && !($dataSource instanceof IDataSource) && !($dataSource instanceof \Traversable)) {
			throw new TInvalidDataTypeException('readonlydatasource_datasource_invalid');
		}
		$this->_dataSource = $dataSource;
		$this->_dataMember = $dataMember;
		parent::__construct();
	}

	public function getView($viewName)
	{
		if ($this->_dataSource instanceof IDataSource) {
			return $this->_dataSource->getView($viewName);
		} else {
			return new TReadOnlyDataSourceView($this, $this->_dataMember, $this->_dataSource);
		}
	}
}
