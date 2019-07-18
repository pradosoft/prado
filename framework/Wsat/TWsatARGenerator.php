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
use PDO;
use Prado\Prado;

/**
 * TWsatARGenerator class
 *
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @since 3.3
 * @package Prado\Wsat
 */

class TWsatARGenerator extends TWsatBaseGenerator
{

		/**
		 * Class name prefix
		 */
	private $_clasPrefix;

	/**
	 * Class name sufix
	 */
	private $_classSufix;

	/**
	 * all table relations array
	 */
	private $_relations;

	/**
	 * unquote chars
	 * @var array
	 */
	private $uqChars = ['[', ']', '"', '`', "'"];

	public function __construct()
	{
		parent::__construct();
	}

	public function setClasPrefix($_clas_prefix)
	{
		$this->_clasPrefix = $_clas_prefix;
	}

	public function setClassSufix($_clas_sufix)
	{
		$this->_classSufix = $_clas_sufix;
	}

	//-----------------------------------------------------------------------------
	// <editor-fold defaultstate="collapsed" desc="Main APIs">
	public function generate($tableName)
	{
		$tableInfo = $this->_dbMetaData->getTableInfo($tableName);
		$this->_commonGenerate($tableName, $tableInfo);
	}

	public function generateAll()
	{
		foreach ($this->getAllTableNames() as $tableName) {
			$tableInfo = $this->_dbMetaData->getTableInfo($tableName);
			if (!empty($this->_relations)) {
				// Cancel generation of M-M relationships middle table
								if (count($tableInfo->getPrimaryKeys()) === 2 && count($tableInfo->getColumns()) === 2) {//M-M relationships
										continue;
								}
			}
			$this->_commonGenerate($tableName, $tableInfo);
		}
	}

	public function buildRelations()
	{
		$this->_relations = [];
		foreach ($this->getAllTableNames() as $table_name) {
			$tableInfo = $this->_dbMetaData->getTableInfo($table_name);
			$pks = $tableInfo->getPrimaryKeys();
			$fks = $tableInfo->getForeignKeys();

			if (count($pks) === 2 && count($tableInfo->getColumns()) === 2) {//M-M relationships
				$table_name_mm = $fks[0]["table"];
				$table_name_mm2 = $fks[1]["table"];

				$this->_relations[$table_name_mm][] = [
										"prop_name" => strtolower($table_name_mm2),
										"rel_type" => "self::MANY_TO_MANY",
										"ref_class_name" => $this->_getProperClassName($table_name_mm2),
										"prop_ref" => $table_name
								];

				$this->_relations[$table_name_mm2][] = [
										"prop_name" => strtolower($table_name_mm),
										"rel_type" => "self::MANY_TO_MANY",
										"ref_class_name" => $this->_getProperClassName($table_name_mm),
										"prop_ref" => $table_name
								];
				continue;
			}
			foreach ($fks as $fk_data) {//1-M relationships
				$owner_table = $fk_data["table"];
				$slave_table = $table_name;
				$fk_prop = key($fk_data["keys"]);

				$this->_relations[$owner_table][] = [
										"prop_name" => strtolower($slave_table),
										"rel_type" => "self::HAS_MANY",
										"ref_class_name" => $this->_getProperClassName($slave_table),
										"prop_ref" => $fk_prop
								];

				$this->_relations[$slave_table][] = [
										"prop_name" => strtolower($owner_table),
										"rel_type" => "self::BELONGS_TO",
										"ref_class_name" => $this->_getProperClassName($owner_table),
										"prop_ref" => $fk_prop
								];
			}
		}
	}

	// </editor-fold>
	//-----------------------------------------------------------------------------
	// <editor-fold defaultstate="collapsed" desc="Common Methods">

	private function _commonGenerate($tableName, $tableInfo)
	{
		if (count($tableInfo->getColumns()) === 0) {
			throw new Exception("Unable to find table or view $tableName in " . $this->_dbMetaData->getDbConnection()->getConnectionString() . ".");
		} else {
			$properties = [];
			foreach ($tableInfo->getColumns() as $field => $metadata) {
				$properties[] = $this->generateProperty($field, $metadata);
			}
			$toString = $this->_buildSmartToString($tableInfo);
		}

		$clasName = $this->_getProperClassName($tableName);
		$class = $this->generateClass($properties, $tableName, $clasName, $toString);
		$output = $this->_opFile . DIRECTORY_SEPARATOR . $clasName . ".php";
		file_put_contents($output, $class);
	}

	private function _getProperClassName($tableName)
	{
		$table_name_words = str_replace("_", " ", strtolower($tableName));
		$final_conversion = str_replace(" ", "", ucwords($table_name_words));
		return $this->_clasPrefix . $final_conversion . $this->_classSufix;
	}

	//-----------------------------------------------------------------------------

	protected function generateProperty($field, $metadata)
	{
		$prop = '';
		$name = '$' . $field;

		/* TODO use in version 2.0 */
		// $type = $column->getPHPType();

		$prop .= "\tpublic $name;";
		return $prop;
	}

	private function _renderRelations($tablename)
	{
		if (!isset($this->_relations[$tablename])) {
			return "";
		}

		$code = "\tpublic static \$RELATIONS = array (";
		foreach ($this->_relations[$tablename] as $rel_data) {
			$code .= "\n\t\t'" . $rel_data["prop_name"] . "' => array(" . $rel_data["rel_type"] . ", '" . $rel_data["ref_class_name"] . "', '" . $rel_data["prop_ref"] . "'),";
		}

		$code = substr($code, 0, -1);
		$code .= "\n\t);";
		return $code;
	}

	private function _buildSmartToString($tableInfo)
	{
		$code = "\tpublic function __toString() {";
		$property = "throw new THttpException(500, 'Not implemented yet.');";
		try {
			foreach ($tableInfo->getColumns() as $column) {
				if (isset($column->IsPrimaryKey) && $column->IsPrimaryKey) {
					$property = str_replace($this->uqChars, "", $column->ColumnName);
				} elseif ($column->PdoType == PDO::PARAM_STR && $column->DBType != "date") {
					$property = str_replace($this->uqChars, "", $column->ColumnName);
					break;
				}
			}
		} catch (Exception $ex) {
			Prado::trace($ex->getMessage());
		}
		$code .= "\n\t\treturn \$this->$property;";
		$code .= "\n\t}";
		return $code;
	}

	protected function generateClass($properties, $tablename, $classname, $toString)
	{
		$props = implode("\n", $properties);
		$relations = $this->_renderRelations($tablename);
		$date = date('Y-m-d h:i:s');
		$env_user = getenv("username");
		return <<<EOD
<?php
/**
 * Auto generated by PRADO - WSAT on $date.
 * @author $env_user
 */
class $classname extends TActiveRecord
{
	const TABLE='$tablename';

$props

	public static function finder(\$className=__CLASS__) {
                return parent::finder(\$className);
	}

$relations

$toString
}
EOD;
	}

	// </editor-fold>
}
