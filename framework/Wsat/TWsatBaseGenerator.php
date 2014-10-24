<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.3
 * @package Wsat
 */
Prado::using('System.Data.Common.TDbMetaData');

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

        function __construct()
        {
                if (!class_exists("TActiveRecordManager", false))
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

        public function renderAllTablesInformation()
        {
                foreach ($this->getAllTableNames() as $table_name)
                {
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
                array_splice($tableNames, $index, 1);
                return $tableNames;
        }

        public static function pr($data)
        {
                echo "<pre>";
                print_r($data);
                echo "</pre>";
        }

}

?>
