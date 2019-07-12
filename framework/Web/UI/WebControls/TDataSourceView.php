<?php
/**
 * TDataSourceSelectParameters, TDataSourceView, TReadOnlyDataSourceView class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;

/**
 * TDataSourceView class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
abstract class TDataSourceView extends \Prado\TComponent
{
	private $_owner;
	private $_name;

	public function __construct(IDataSource $owner, $viewName)
	{
		$this->_owner = $owner;
		$this->_name = $viewName;
	}

	/**
	 * Performs DB selection based on specified parameters.
	 * @param ??? $parameters * @return Traversable
	 */
	abstract public function select($parameters);

	/**
	 * Inserts a DB record.
	 * @param array|TMap $values * @return integer affected rows
	 */
	public function insertAt($values)
	{
		throw new TNotSupportedException('datasourceview_insert_unsupported');
	}

	/**
	 * Updates DB record(s) with the specified keys and new values
	 * @param array|TMap $keys keys for specifying the records to be updated
	 * @param array|TMap $values new values
	 * @return int affected rows
	 */
	public function update($keys, $values)
	{
		throw new TNotSupportedException('datasourceview_update_unsupported');
	}

	/**
	 * Deletes DB row(s) with the specified keys.
	 * @param array|TMap $keys keys for specifying the rows to be deleted
	 * @return int affected rows
	 */
	public function delete($keys)
	{
		throw new TNotSupportedException('datasourceview_delete_unsupported');
	}

	public function getCanDelete()
	{
		return false;
	}

	public function getCanInsert()
	{
		return false;
	}

	public function getCanPage()
	{
		return false;
	}

	public function getCanGetRowCount()
	{
		return false;
	}

	public function getCanSort()
	{
		return false;
	}

	public function getCanUpdate()
	{
		return false;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function getDataSource()
	{
		return $this->_owner;
	}

	public function onDataSourceViewChanged($param)
	{
		$this->raiseEvent('OnDataSourceViewChanged', $this, $param);
	}
}
