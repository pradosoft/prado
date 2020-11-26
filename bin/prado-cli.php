<?php

/**
 * Prado command line developer tools.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

if (!isset($_SERVER['argv']) || php_sapi_name() !== 'cli') {
	die('Must be run from the command line');
}

// Locate composer's autoloader
if (file_exists($autoloader = realpath(__DIR__ . '/../vendor/autoload.php'))) {
	// if we are running inside a prado repo checkout, get out of bin/
	include($autoloader);
} elseif (file_exists($autoloader = realpath(__DIR__ . '/../../../autoload.php'))) {
	// if we are running from inside an application's vendor/ directory, get out of pradosoft/prado/bin/
	include($autoloader);
}

use Prado\TApplication;
use Prado\TShellApplication;
use Prado\Prado;
use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Data\ActiveRecord\TActiveRecordManager;

//stub application class
class PradoShellApplication extends TShellApplication
{
}

restore_exception_handler();

//register action classes
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLinePhpShell');
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLineActiveRecordGen');
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLineActiveRecordGenAll');

//run it;
PradoCommandLineInterpreter::getInstance()->run($_SERVER['argv']);

/**************** END CONFIGURATION **********************/

/**
 * PradoCommandLineInterpreter Class
 *
 * Command line interface, configures the action classes and dispatches the command actions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0.5
 */
class PradoCommandLineInterpreter
{
	/**
	 * @var array command action classes
	 */
	protected $_actions = [];

	/**
	 * @param string $class action class name
	 */
	public function addActionClass($class)
	{
		$this->_actions[$class] = new $class;
	}

	/**
	 * @return PradoCommandLineInterpreter static instance
	 */
	public static function getInstance()
	{
		static $instance;
		if ($instance === null) {
			$instance = new self;
		}
		return $instance;
	}

	public static function printGreeting()
	{
		echo "Command line tools for Prado " . Prado::getVersion() . ".\n";
	}

	/**
	 * Dispatch the command line actions.
	 * @param array $args command line arguments
	 */
	public function run($args)
	{
		if (count($args) > 1) {
			array_shift($args);
		}
		$valid = false;
		foreach ($this->_actions as $class => $action) {
			if ($action->isValidAction($args)) {
				$valid |= $action->performAction($args);
				break;
			} else {
				$valid = false;
			}
		}
		if (!$valid) {
			$this->printHelp();
		}
	}

	/**
	 * Print command line help, default action.
	 */
	public function printHelp()
	{
		PradoCommandLineInterpreter::printGreeting();

		echo "usage: php prado-cli.php action <parameter> [optional]\n";
		echo "example: php prado-cli.php -c mysite\n\n";
		echo "actions:\n";
		foreach ($this->_actions as $action) {
			echo $action->renderHelp();
		}
	}
}

/**
 * Base class for command line actions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0.5
 */
abstract class PradoCommandLineAction
{
	/**
	 * Execute the action.
	 * @param array $args command line parameters
	 * @return bool true if action was handled
	 */
	abstract public function performAction($args);

	/**
	 * Creates a directory and sets its mode
	 * @param string $dir directory name
	 * @param int $mask directory mode mask suitable for chmod()
	 */
	protected function createDirectory($dir, $mask)
	{
		if (!is_dir($dir)) {
			mkdir($dir);
			echo "creating $dir\n";
		}
		if (is_dir($dir)) {
			chmod($dir, $mask);
		}
	}

	/**
	 * Creates a file and fills it with content
	 * @param string $filename file name
	 * @param int $content file contents
	 */
	protected function createFile($filename, $content)
	{
		if (!is_file($filename)) {
			file_put_contents($filename, $content);
			echo "creating $filename\n";
		}
	}

	/**
	 * Checks if specified parameters are suitable for the specified action
	 * @param array $args parameters
	 * @return bool
	 */
	public function isValidAction($args)
	{
		return 0 == strcasecmp($args[0], $this->action) &&
			count($args) - 1 >= count($this->parameters);
	}

	/**
	 * @return string
	 */
	public function renderHelp()
	{
		$params = [];
		foreach ($this->parameters as $v) {
			$params[] = '<' . $v . '>';
		}
		$parameters = implode(' ', $params);
		$options = [];
		foreach ($this->optional as $v) {
			$options[] = '[' . $v . ']';
		}
		$optional = (strlen($parameters) ? ' ' : '') . implode(' ', $options);
		$description = '';
		foreach (explode("\n", wordwrap($this->description, 65)) as $line) {
			$description .= '    ' . $line . "\n";
		}
		return <<<EOD
  {$this->action} {$parameters}{$optional}
{$description}

EOD;
	}

	/**
	 * Initalize a Prado application inside the specified directory
	 * @param string $directory directory name
	 * @return false|TApplication
	 */
	protected function initializePradoApplication($directory)
	{
		$_SERVER['SCRIPT_FILENAME'] = $directory . '/index.php';
		$app_dir = realpath($directory . '/protected/');
		if ($app_dir !== false && is_dir($app_dir)) {
			if (Prado::getApplication() === null) {
				$app = new PradoShellApplication($app_dir);
				$app->run();
				$dir = substr(str_replace(realpath('./'), '', $app_dir), 1);
				PradoCommandLineInterpreter::printGreeting();
				echo '** Loaded PRADO appplication in directory "' . $dir . "\".\n";
			}

			return Prado::getApplication();
		} else {
			PradoCommandLineInterpreter::printGreeting();
			echo '+' . str_repeat('-', 77) . "+\n";
			echo '** Unable to load PRADO application in directory "' . $directory . "\".\n";
			echo '+' . str_repeat('-', 77) . "+\n";
		}
		return false;
	}
}

/**
 * Creates and run a Prado application in a PHP Shell.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0.5
 */
class PradoCommandLinePhpShell extends PradoCommandLineAction
{
	protected $action = 'shell';
	protected $parameters = [];
	protected $optional = ['directory'];
	protected $description = 'Runs a PHP interactive interpreter. Initializes the Prado application in the given [directory].';

	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		if (count($args) > 1) {
			$this->initializePradoApplication($args[1]);
		}

		\Psy\debug([], Prado::getApplication());
		return true;
	}
}

/**
 * Create active record skeleton
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class PradoCommandLineActiveRecordGen extends PradoCommandLineAction
{
	protected $action = 'generate';
	protected $parameters = ['table', 'output'];
	protected $optional = ['directory', 'soap'];
	protected $description = 'Generate Active Record skeleton for <table> to <output> file using application.xml in [directory]. May also generate [soap] properties.';
	private $_soap = false;

	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		$app_dir = count($args) > 3 ? $this->getAppDir($args[3]) : $this->getAppDir();
		$this->_soap = count($args) > 4;
		$this->_overwrite = count($args) > 5;
		if ($app_dir !== false) {
			$config = $this->getActiveRecordConfig($app_dir);
			$output = $this->getOutputFile($app_dir, $args[2]);
			if (is_file($output) && !$this->_overwrite) {
				echo "** File $output already exists, skipping. \n";
			} elseif ($config !== false && $output !== false) {
				$this->generateActiveRecord($config, $args[1], $output);
			}
		}
		return true;
	}

	/**
	 * @param string $dir application directory
	 * @return false|string
	 */
	protected function getAppDir($dir = ".")
	{
		if (is_dir($dir)) {
			return realpath($dir);
		}
		if (false !== ($app_dir = realpath($dir . '/protected/')) && is_dir($app_dir)) {
			return $app_dir;
		}
		echo '** Unable to find directory "' . $dir . "\".\n";
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @return false|string
	 */
	protected function getXmlFile($app_dir)
	{
		if (false !== ($xml = realpath($app_dir . '/application.xml')) && is_file($xml)) {
			return $xml;
		}
		if (false !== ($xml = realpath($app_dir . '/protected/application.xml')) && is_file($xml)) {
			return $xml;
		}
		echo '** Unable to find application.xml in ' . $app_dir . "\n";
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @return false|string
	 */
	protected function getActiveRecordConfig($app_dir)
	{
		if (false === ($xml = $this->getXmlFile($app_dir))) {
			return false;
		}
		if (false !== ($app = $this->initializePradoApplication($app_dir))) {
			foreach ($app->getModules() as $module) {
				if ($module instanceof TActiveRecordConfig) {
					return $module;
				}
			}
			echo '** Unable to find TActiveRecordConfig module in ' . $xml . "\n";
		}
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @param string $namespace output file in namespace format
	 * @return false|string
	 */
	protected function getOutputFile($app_dir, $namespace)
	{
		if (is_file($namespace) && strpos($namespace, $app_dir) === 0) {
			return $namespace;
		}
		$file = Prado::getPathOfNamespace($namespace, ".php");
		if ($file !== null && false !== ($path = realpath(dirname($file))) && is_dir($path)) {
			if (strpos($path, $app_dir) === 0) {
				return $file;
			}
		}
		echo '** Output file ' . $file . ' must be within directory ' . $app_dir . "\n";
		return false;
	}

	/**
	 * @param string $config database configuration
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
				echo '** Unable to find table or view "' . $tablename . '" in "' . $manager->getDbConnection()->getConnectionString() . "\".\n";
				return false;
			} else {
				$properties = [];
				foreach ($tableInfo->getColumns() as $field => $column) {
					$properties[] = $this->generateProperty($field, $column);
				}
			}

			$classname = basename($output, '.php');
			$class = $this->generateClass($properties, $tablename, $classname);
			echo "  Writing class $classname to file $output\n";
			file_put_contents($output, $class);
		} else {
			echo '** Unable to connect to database with ConnectionID=\'' . $config->getConnectionID() . "'. Please check your settings in application.xml and ensure your database connection is set up first.\n";
		}
	}

	/**
	 * @param string $field php variable name
	 * @param string $column database column name
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

/**
 * Create active record skeleton for all tables in DB and its relations
 *
 * @author Matthias Endres <me[at]me23[dot]de>
 * @author Daniel Sampedro Bello <darthdaniel85[at]gmail[dot]com>
 * @since 3.2
 */
class PradoCommandLineActiveRecordGenAll extends PradoCommandLineAction
{
	protected $action = 'generateAll';
	protected $parameters = ['output'];
	protected $optional = ['directory', 'soap', 'overwrite', 'prefix', 'postfix'];
	protected $description = "Generate Active Record skeleton for all Tables to <output> file using application.xml in [directory]. May also generate [soap] properties.\nGenerated Classes are named like the Table with optional [Prefix] and/or [Postfix]. [Overwrite] is used to overwrite existing Files.";
	private $_soap = false;
	private $_prefix = '';
	private $_postfix = '';
	private $_overwrite = false;

	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		$app_dir = count($args) > 2 ? $this->getAppDir($args[2]) : $this->getAppDir();
		$this->_soap = count($args) > 3 ? ($args[3] == "soap" || $args[3] == "true" ? true : false) : false;
		$this->_overwrite = count($args) > 4 ? ($args[4] == "overwrite" || $args[4] == "true" ? true : false) : false;
		$this->_prefix = count($args) > 5 ? $args[5] : '';
		$this->_postfix = count($args) > 6 ? $args[6] : '';

		if ($app_dir !== false) {
			$config = $this->getActiveRecordConfig($app_dir);

			$manager = TActiveRecordManager::getInstance();
			$con = $manager->getDbConnection();
			$con->Active = true;

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
					echo "\n    Sorry, generateAll is not implemented for " . $con->getDriverName() . "\n";

			   }

			$dataReader = $command->query();
			$dataReader->bindColumn(1, $table);
			$tables = [];
			while ($dataReader->read() !== false) {
				$tables[] = $table;
			}
			$con->Active = false;
			foreach ($tables as $key => $table) {
				$output = $args[1] . "." . $this->_prefix . ucfirst($table) . $this->_postfix;
				if ($config !== false && $output !== false) {
					$this->generate("generate " . $table . " " . $output . " " . $this->_soap . " " . $this->_overwrite);
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
			$app_dir = '.';
			if (Prado::getApplication() !== null) {
				$app_dir = dirname(Prado::getApplication()->getBasePath());
			}
			$args = [$input[0], $input[1], $input[2], $app_dir];
			if (count($input) > 3) {
				$args[] = 'soap';
			}
			if (count($input) > 4) {
				$args[] = 'overwrite';
			}
			$cmd = new PradoCommandLineActiveRecordGen;
			$cmd->performAction($args);
		} else {
			echo "\n    Usage: generate table_name Application.pages.RecordClassName\n";
		}
	}

	/**
	 * @param string $dir application directory
	 * @return false|string
	 */
	protected function getAppDir($dir = ".")
	{
		if (is_dir($dir)) {
			return realpath($dir);
		}
		if (false !== ($app_dir = realpath($dir . '/protected/')) && is_dir($app_dir)) {
			return $app_dir;
		}
		echo '** Unable to find directory "' . $dir . "\".\n";
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @return false|string
	 */
	protected function getXmlFile($app_dir)
	{
		if (false !== ($xml = realpath($app_dir . '/application.xml')) && is_file($xml)) {
			return $xml;
		}
		if (false !== ($xml = realpath($app_dir . '/protected/application.xml')) && is_file($xml)) {
			return $xml;
		}
		echo '** Unable to find application.xml in ' . $app_dir . "\n";
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @return false|string
	 */
	protected function getActiveRecordConfig($app_dir)
	{
		if (false === ($xml = $this->getXmlFile($app_dir))) {
			return false;
		}
		if (false !== ($app = $this->initializePradoApplication($app_dir))) {
			foreach ($app->getModules() as $module) {
				if ($module instanceof TActiveRecordConfig) {
					return $module;
				}
			}
			echo '** Unable to find TActiveRecordConfig module in ' . $xml . "\n";
		}
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @param string $namespace output file in namespace format
	 * @return false|string
	 */
	protected function getOutputFile($app_dir, $namespace)
	{
		if (is_file($namespace) && strpos($namespace, $app_dir) === 0) {
			return $namespace;
		}
		$file = Prado::getPathOfNamespace($namespace, "");
		if ($file !== null && false !== ($path = realpath(dirname($file))) && is_dir($path)) {
			if (strpos($path, $app_dir) === 0) {
				return $file;
			}
		}
		echo '** Output file ' . $file . ' must be within directory ' . $app_dir . "\n";
		return false;
	}
}
