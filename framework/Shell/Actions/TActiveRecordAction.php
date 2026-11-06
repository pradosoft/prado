<?php

/**
 * TActiveRecordAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell\Actions;

use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TDbException;
use Prado\Prado;
use Prado\Shell\TShellAction;

/**
 * TActiveRecordAction class.
 *
 * Create active record skeleton
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com> - Shell refactor, expanded DB support
 * @author Matthias Endres <me[at]me23[dot]de> - Generate-All
 * @author Daniel Sampedro Bello <darthdaniel85[at]gmail[dot]com> - Generate-All
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com> - Generate
 * @since 3.1
 */
class TActiveRecordAction extends TShellAction
{
	protected $action = 'activerecord';
	protected $methods = ['generate', 'generate-all'];
	protected $parameters = [['table', 'output'], ['output']];
	protected $optional = [['soap', 'overwrite'], ['soap', 'overwrite', 'prefix', 'suffix']];
	protected $description = [
		'Provides Active Record skeleton generation.',
		'Generate Active Record skeleton for <table> to <output>. May also generate [soap] properties.',
		"Generate Active Record skeleton for all Tables to <output>. May also generate [soap] properties.\nGenerated Classes are named like the Table with optional [Prefix] and/or [Suffix]. [Overwrite] is used to overwrite existing Files.",
	];
	private $_soap = false;
	private $_overwrite = false;

	private $_soapall = false;
	private $_overwriteall = false;
	private $_prefix = '';
	private $_postfix = '';



	/**
	 * This is the Shell Command for Generating all Action Record table skeletons
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionGenerateAll($args)
	{
		$app_dir = Prado::getApplication()->getBasePath();
		$this->_soapall = count($args) > 2 ? ($args[2] == "soap" || $args[2] == "true" ? true : false) : false;
		$this->_overwriteall = count($args) > 3 ? ($args[3] == "overwrite" || $args[3] == "true" ? true : false) : false;
		$this->_prefix = count($args) > 4 ? $args[4] : '';
		$this->_postfix = count($args) > 5 ? $args[5] : '';

		if ($app_dir !== false) {
			$config = $this->getActiveRecordConfig();

			$manager = TActiveRecordManager::getInstance();
			$con = $manager->getDbConnection();
			if (!$con) {
				$this->writeConnectionError($config);
				return true;
			}
			$wasActive = $con->getActive();
			$con->setActive(true);
			$command = null;

			switch ($con->getDriverName()) {
				case TDbDriver::EXTENSION_MYSQLI:
				case TDbDriver::DRIVER_MYSQL:
					$command = $con->createCommand("SHOW TABLES");
					break;
				case TDbDriver::DRIVER_SQLITE: //sqlite 3
				case TDbDriver::DRIVER_SQLITE2: //sqlite 2
					$command = $con->createCommand("SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'");
					break;
				case TDbDriver::DRIVER_PGSQL:
					$command = $con->createCommand("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
					break;
				case TDbDriver::EXTENSION_MSSQL: // Mssql driver on windows hosts
				case TDbDriver::DRIVER_SQLSRV: // sqlsrv driver on windows hosts
				case TDbDriver::DRIVER_DBLIB: // dblib drivers on linux (and maybe others os) hosts
					$command = $con->createCommand("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
					break;
				case TDbDriver::DRIVER_OCI:
					$command = $con->createCommand("SELECT table_name FROM user_tables");
					break;
				case TDbDriver::DRIVER_IBM:
					$command = $con->createCommand("SELECT TABNAME FROM SYSCAT.TABLES WHERE TABSCHEMA = CURRENT SCHEMA AND TYPE = 'T' ORDER BY TABNAME");
					break;
				case TDbDriver::DRIVER_FIREBIRD:
				case TDbDriver::DRIVER_INTERBASE:
					$command = $con->createCommand("SELECT TRIM(RDB\$RELATION_NAME) AS tbl_name FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL ORDER BY RDB\$RELATION_NAME");
					break;
				default:
					$this->_outWriter->writeError("Sorry, generateAll is not implemented for " . $con->getDriverName() . ".");
					$con->setActive($wasActive);
					return true;
			}

			$dataReader = $command->query();
			$dataReader->bindColumn(1, $table);
			$tables = [];
			while ($dataReader->read() !== false) {
				$tables[] = $table;
			}
			foreach ($tables as $key => $table) {
				$classname = preg_replace('/[^A-Za-z0-9_\x80-\xff]/', '_', $this->_prefix . ucfirst($table) . $this->_postfix);
				$output = $args[1] . "." . $classname;
				if ($config !== false) {
					$this->actionGenerate([
						'generate',
						$table,
						$output,
						$this->_soapall ? 'soap' : 'false',
						$this->_overwriteall ? 'overwrite' : 'false',
					]);
				}
			}
			$con->setActive($wasActive);
		}
		return true;
	}

	/**
	 * Runs {@see actionGenerate()} from a command line string.
	 * @param string $l command line in the form `generate <table> <output> [soap] [overwrite]`
	 */
	public function generate($l)
	{
		$input = explode(" ", trim($l));
		if (count($input) > 2) {
			$this->actionGenerate(array_slice($input, 0, 5));
		} else {
			$this->_outWriter->writeLine("\n    Usage: generate table_name Application.pages.RecordClassName");
		}
	}


	/**
	 * This is the Shell Command for Generating a specific Action Record table skeleton
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionGenerate($args)
	{
		$this->_soap = count($args) > 3 && ($args[3] == 'soap' || $args[3] == 'true');
		$this->_overwrite = count($args) > 4 && ($args[4] == 'overwrite' || $args[4] == 'true');
		$config = $this->getActiveRecordConfig();
		$output = $this->getOutputFile($args[2]);
		if (is_file($output) && !$this->_overwrite) {
			$this->_outWriter->writeError("File $output already exists, skipping. ");
		} elseif ($config !== false && $output !== false) {
			$this->generateActiveRecord($config, $args[1], $output);
		}
		return true;
	}

	/**
	 * gets the TActiveRecordConfig for the application
	 * @return null|\Prado\Data\ActiveRecord\TActiveRecordConfig
	 */
	protected function getActiveRecordConfig()
	{
		foreach (Prado::getApplication()->getModules() as $module) {
			if ($module instanceof TActiveRecordConfig) {
				return $module;
			}
		}
		return null;
	}

	/**
	 * @param string $namespace output file in namespace format
	 * @return false|string
	 */
	protected function getOutputFile($namespace)
	{
		$app_dir = Prado::getApplication()->getBasePath();
		$real_dir = realpath($app_dir);
		if (is_file($namespace) && $this->isWithinDirectory(realpath($namespace), $real_dir)) {
			return $namespace;
		}
		$file = Prado::getPathOfNamespace($namespace, ".php");
		if ($file !== null && false !== ($path = realpath(dirname($file))) && is_dir($path)) {
			if ($this->isWithinDirectory($path, $real_dir)) {
				return $file;
			}
		}
		$this->_outWriter->writeError('Output file ' . ($file ?? $namespace) . ' must be within directory ' . $app_dir . "");
		return false;
	}

	/**
	 * @param string $path canonical path
	 * @param false|string $dir canonical directory, false when it does not exist
	 * @return bool whether $path is $dir or lies below it
	 * @since 4.3.3
	 */
	private function isWithinDirectory($path, $dir): bool
	{
		if ($dir === false) {
			return false;
		}
		$dir = rtrim($dir, '/\\');
		return $path === $dir || str_starts_with($path, $dir . DIRECTORY_SEPARATOR);
	}

	/**
	 * Writes the error for a missing database connection.
	 * @param ?TActiveRecordConfig $config active record module, null when the application has none
	 * @since 4.3.3
	 */
	private function writeConnectionError($config): void
	{
		$this->_outWriter->writeError('Unable to connect to database with ConnectionID=\'' . ($config ? $config->getConnectionID() : '') . "'. Please check your settings in application.xml and ensure your database connection is set up first.");
	}

	/**
	 * @param TActiveRecordConfig $config database configuration
	 * @param string $tablename table name
	 * @param string $output output file name
	 * @return bool
	 */
	protected function generateActiveRecord($config, $tablename, $output)
	{
		$manager = TActiveRecordManager::getInstance();
		if ($manager->getDbConnection()) {
			$gateway = $manager->getRecordGateway();
			try {
				$tableInfo = $gateway->getTableInfo($manager->getDbConnection(), $tablename);
			} catch (TDbException $e) {
				$tableInfo = null;
			}
			if ($tableInfo === null || count($tableInfo->getColumns()) === 0) {
				$this->_outWriter->writeError('Unable to find table or view "' . $tablename . '" in "' . $manager->getDbConnection()->getConnectionString() . "\".");
				return false;
			} else {
				$properties = [];
				foreach ($tableInfo->getColumns() as $field => $column) {
					$properties[] = $this->generateProperty($field, $column);
				}
			}

			$classname = basename($output, '.php');
			$class = $this->generateClass($properties, $tablename, $classname);
			$this->_outWriter->writeLine("  Writing class $classname to file $output");
			file_put_contents($output, $class);
		} else {
			$this->writeConnectionError($config);
		}
		return true;
	}

	/**
	 * @param string $field php variable name
	 * @param \Prado\Data\Common\TDbTableColumn $column database column name
	 * @return string
	 */
	protected function generateProperty($field, $column)
	{
		$prop = '';
		$name = '$' . $field;
		$type = $column->getPHPType();
		if ($this->_soap) {
			$prop .= <<<EOD

					/**
					 * @var $type $name
					 * @soapproperty
					 */

				EOD;
		}
		$prop .= "\tpublic $name;";
		return $prop;
	}

	/**
	 * @param array $properties class varibles
	 * @param string $tablename database table name
	 * @param string $class php class name
	 * @return string
	 */
	protected function generateClass($properties, $tablename, $class)
	{
		$props = implode("\n", $properties);
		$date = date('Y-m-d H:i:s');
		return <<<EOD
			<?php
			/**
			 * Auto generated by prado-cli.php on $date.
			 */
			class $class extends TActiveRecord
			{
				const TABLE='$tablename';

			$props

				public static function finder(\$className=__CLASS__)
				{
					return parent::finder(\$className);
				}
			}

			EOD;
	}
}
