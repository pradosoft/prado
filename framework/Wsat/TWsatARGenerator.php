<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.3
 * @package Wsat
 */

Prado::using('System.Data.Common.TDbMetaData');

class TWsatARGenerator
{

        /**
         * @return TDbMetaData for retrieving metadata information, such as
         * table and columns information, from a database connection.
         */
        private $_dbMetaData;

        /**
         * Output folder where AR classes will be saved.
         */
        private $_opFile;

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
        private $uqChars = array('[', ']', '"', '`', "'");

        function __construct()
        {
                if(!class_exists("TActiveRecordManager", false))
                        throw new Exception("You need to enable the ActiveRecord module in your application configuration file.");
                $ar_manager = TActiveRecordManager::getInstance();
                $_conn = $ar_manager->getDbConnection();
                $_conn->Active = true;
                $this->_dbMetaData = TDbMetaData::getInstance($_conn);
        }

        public function setOpFile($op_file_namespace)
        {
                $op_file = Prado::getPathOfNamespace($op_file_namespace);
                if (empty($op_file))
                        throw new Exception("You need to fix your output folder namespace.");
                if (!is_dir($op_file))
                        mkdir($op_file, 0777, true);
                $this->_opFile = $op_file;
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
                foreach ($this->_dbMetaData->findTableNames() as $tableName)
                {
                        if ($tableName == "pradocache")
                                continue;
                        $tableInfo = $this->_dbMetaData->getTableInfo($tableName);
                        if (!empty($this->_relations))
                        {
                                // Cancel generation of M-M relationships middle table
                                if (count($tableInfo->getPrimaryKeys()) === 2 && count($tableInfo->getColumns()) === 2)//M-M relationships
                                        continue;
                        }
                        $this->_commonGenerate($tableName, $tableInfo);
                }
        }

        public function buildRelations()
        {
                $this->_relations = array();
                foreach ($this->_dbMetaData->findTableNames() as $table_name)
                {
                        $tableInfo = $this->_dbMetaData->getTableInfo($table_name);
                        $pks = $tableInfo->getPrimaryKeys();
                        $fks = $tableInfo->getForeignKeys();

                        if (count($pks) === 2 && count($tableInfo->getColumns()) === 2)//M-M relationships
                        {
                                $table_name_mm = $fks[0]["table"];
                                $table_name_mm2 = $fks[1]["table"];

                                $this->_relations[$table_name_mm][] = array(
                                    "prop_name" => strtolower($table_name_mm2),
                                    "rel_type" => "self::MANY_TO_MANY",
                                    "ref_class_name" => $this->_getProperClassName($table_name_mm2),
                                    "prop_ref" => $table_name
                                );

                                $this->_relations[$table_name_mm2][] = array(
                                    "prop_name" => strtolower($table_name_mm),
                                    "rel_type" => "self::MANY_TO_MANY",
                                    "ref_class_name" => $this->_getProperClassName($table_name_mm),
                                    "prop_ref" => $table_name
                                );
                                continue;
                        }
                        foreach ($fks as $fk_data)//1-M relationships
                        {
                                $owner_table = $fk_data["table"];
                                $slave_table = $table_name;
                                $fk_prop = key($fk_data["keys"]);

                                $this->_relations[$owner_table][] = array(
                                    "prop_name" => strtolower($slave_table),
                                    "rel_type" => "self::HAS_MANY",
                                    "ref_class_name" => $this->_getProperClassName($slave_table),
                                    "prop_ref" => $fk_prop
                                );

                                $this->_relations[$slave_table][] = array(
                                    "prop_name" => strtolower($owner_table),
                                    "rel_type" => "self::BELONGS_TO",
                                    "ref_class_name" => $this->_getProperClassName($owner_table),
                                    "prop_ref" => $fk_prop
                                );
                        }
                }
        }

// </editor-fold>
//-----------------------------------------------------------------------------
        // <editor-fold defaultstate="collapsed" desc="Common Methods">

        private function _commonGenerate($tableName, $tableInfo)
        {
                if (count($tableInfo->getColumns()) === 0)
                        throw new Exception("Unable to find table or view $tableName in " . $this->_dbMetaData->getDbConnection()->getConnectionString() . ".");
                else
                {
                        $properties = array();
                        foreach ($tableInfo->getColumns() as $field => $column)
                                $properties[] = $this->generateProperty($field, $column);
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

        public function renderAllTablesInformation()
        {
                foreach ($this->_dbMetaData->findTableNames() as $table_name)
                {
                        echo $table_name . "<br>";

                        $tableInfo = $this->_dbMetaData->getTableInfo($table_name);
                        echo "Table info:" . "<br>";
                        echo "<pre>";
                        var_dump($tableInfo);
                        echo "</pre>";
                }
        }

//-----------------------------------------------------------------------------

        protected function generateProperty($field, $column)
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
                if (!isset($this->_relations[$tablename]))
                        return "";

                $code = "\tpublic static \$RELATIONS = array (";
                foreach ($this->_relations[$tablename] as $rel_data)
                        $code .= "\n\t\t'" . $rel_data["prop_name"] . "' => array(" . $rel_data["rel_type"] . ", '" . $rel_data["ref_class_name"] . "', '" . $rel_data["prop_ref"] . "'),";

                $code = substr($code, 0, -1);
                $code .= "\n\t);";
                return $code;
        }

        private function _buildSmartToString($tableInfo)
        {
                $code = "\tpublic function __toString() {";
                $property = "throw new THttpException(500, 'Not implemented yet.');";
                try
                {
                        foreach ($tableInfo->getColumns() as $column)
                        {
                                if (isset($column->IsPrimaryKey) && $column->IsPrimaryKey)
                                        $property = str_replace($this->uqChars, "", $column->ColumnName);
                                elseif ($column->PdoType == PDO::PARAM_STR && $column->DBType != "date")
                                {
                                        $property = str_replace($this->uqChars, "", $column->ColumnName);
                                        break;
                                }
                        }
                } catch (Exception $ex)
                {
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