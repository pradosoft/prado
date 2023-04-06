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
use Prado\Prado;
use Prado\Shell\TShellAction;

/**
 * TActiveRecordAction class.
 *
 * Create active record skeleton
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com> - Shell refactor
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
			$con->setActive(true);
			$command = null;

			switch ($con->getDriverName()) {
				case 'mysqli':
				case 'mysql':
					$command = $con->createCommand("SHOW TABLES");
					break;
				case 'sqlite': //sqlite 3
				case 'sqlite2': //sqlite 2
					$command = $con->createCommand("SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'");
					break;
				case 'pgsql':
				case 'mssql': // Mssql driver on windows hosts
				case 'sqlsrv': // sqlsrv driver on windows hosts
				case 'dblib': // dblib drivers on linux (and maybe others os) hosts
				case 'oci':
					//				case 'ibm':
				default:
					$this->_outWriter->writeError("Sorry, generateAll is not implemented for " . $con->getDriverName() . ".");
			}

			$dataReader = $command->query();
			$dataReader->bindColumn(1, $table);
			$tables = [];
			while ($dataReader->read() !== false) {
				$tables[] = $table;
			}
			$con->setActive(false);
			foreach ($tables as $key => $table) {
				$output = $args[1] . "." . $this->_prefix . ucfirst($table) . $this->_postfix;
				if ($config !== false && $output !== false) {
					$this->generate("generate " . $table . " " . $output . " " . $this->_soapall . " " . $this->_overwriteall);
				}
			}
		}
		return true;
	}

	/**
	 * @param string $l commandline
	 */
	public function generate($l)
	{
		$input = explode(" ", trim($l));
		if (count($input) > 2) {
			$app_dir = dirname(Prado::getApplication()->getBasePath());
			$args = [$input[0], $input[1], $input[2]];
			if (count($input) > 3) {
				$args[] = 'soap';
			}
			if (count($input) > 4) {
				$args[] = 'overwrite';
			}
			$this->actionGenerate($args);
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
		$this->_soapall = count($args) > 3 ? ($args[3] == "soap" || $args[2] == "true" ? true : false) : false;
		$this->_overwriteall = count($args) > 4 ? ($args[4] == "overwrite" || $args[3] == "true" ? true : false) : false;
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
	 * @return false|TActiveRecordConfig
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
		if (is_file($namespace) && strpos($namespace, $app_dir) === 0) {
			return $namespace;
		}
		$file = Prado::getPathOfNamespace($namespace, ".php");
		if ($file !== null && false !== ($path = realpath(dirname($file))) && is_dir($path)) {
			if (strpos($path, $app_dir) === 0) {
				return $file;
			}
		}
		$this->_outWriter->writeError('Output file ' . $file . ' must be within directory ' . $app_dir . "");
		return false;
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
			$tableInfo = $gateway->getTableInfo($manager->getDbConnection(), $tablename);
			if (count($tableInfo->getColumns()) === 0) {
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
			$this->_outWriter->writeError('Unable to connect to database with ConnectionID=\'' . $config->getConnectionID() . "'. Please check your settings in application.xml and ensure your database connection is set up first.");
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
		$date = date('Y-m-d h:i:s');
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
