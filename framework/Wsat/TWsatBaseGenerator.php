<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.3
 * @package Prado\Wsat
 */

namespace Prado\Wsat;

use Exception;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\Common\TDbMetaData;
use Prado\Prado;

/**
 * TWsatBaseGenerator class
 *
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @since 3.3
 * @package Prado\Wsat
 */

class TWsatBaseGenerator
{

	/**
	 * @return TDbMetaData for retrieving metadata information, such as
	 * table and columns information, from a database connection.
	 */
	protected $_dbMetaData;

	/**
	 * Output folder where AR classes will be saved.
	 */
	protected $_opFile;

	public function __construct()
	{
		if (!class_exists("TActiveRecordManager", false)) {
			throw new Exception("You need to enable the ActiveRecord module in your application configuration file.");
		}
		$ar_manager = TActiveRecordManager::getInstance();
		$_conn = $ar_manager->getDbConnection();
		$_conn->Active = true;
		$this->_dbMetaData = TDbMetaData::getInstance($_conn);
	}

	public function setOpFile($op_file_namespace)
	{
		$op_file = Prado::getPathOfNamespace($op_file_namespace);
		if (empty($op_file)) {
			throw new Exception("You need to fix your output folder namespace.");
		}
		if (!is_dir($op_file)) {
			mkdir($op_file, 0777, true);
		}
		$this->_opFile = $op_file;
	}

	public function renderAllTablesInformation()
	{
		foreach ($this->getAllTableNames() as $table_name) {
			echo $table_name . "<br>";
			$tableInfo = $this->_dbMetaData->getTableInfo($table_name);
			echo "Table info:" . "<br>";
			echo "<pre>";
			print_r($tableInfo);
			echo "</pre>";
		}
	}

	public function getAllTableNames()
	{
		$tableNames = $this->_dbMetaData->findTableNames();
		$index = array_search('pradocache', $tableNames);
		if ($index) {
			array_splice($tableNames, $index, 1);
		}
		return $tableNames;
	}

	public static function pr($data)
	{
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}

	protected function eq($data)
	{
		return '"' . $data . '"';
	}
}
