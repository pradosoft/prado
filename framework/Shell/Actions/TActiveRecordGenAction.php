<?php
/**
 * TActiveRecordGenAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell\Actions
 */

namespace Prado\Shell\Actions;

use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Prado;
use Prado\Shell\TShellAction;

/**
 * Create active record skeleton
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Shell\Actions
 * @since 3.1
 */
class TActiveRecordGenAction extends TShellAction
{
	protected $action = 'generate';
	protected $parameters = ['table', 'output'];
	protected $optional = ['directory', 'soap'];
	protected $description = 'Generate Active Record skeleton for <table> to <output> file using application.xml/php in [directory]. May also generate [soap] properties.';
	private $_soap = false;
	private $_overwrite = false;

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
				$this->_outWriter->writeLine("** File $output already exists, skipping. ");
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
		$this->_outWriter->writeLine('** Unable to find directory "' . $dir . "\".");
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @return false|string
	 */
	protected function getActiveRecordConfig($app_dir)
	{
		if (false === ($xml = $this->getAppConfigFile($app_dir))) {
			return false;
		}
		if (false !== ($app = $this->initializePradoApplication($app_dir))) {
			foreach ($app->getModules() as $module) {
				if ($module instanceof TActiveRecordConfig) {
					return $module;
				}
			}
			$this->_outWriter->writeLine('** Unable to find TActiveRecordConfig module in ' . $xml . "");
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
		$this->_outWriter->writeLine('** Output file ' . $file . ' must be within directory ' . $app_dir . "");
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
				$this->_outWriter->writeLine('** Unable to find table or view "' . $tablename . '" in "' . $manager->getDbConnection()->getConnectionString() . "\".");
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
			$this->_outWriter->writeLine('** Unable to connect to database with ConnectionID=\'' . $config->getConnectionID() . "'. Please check your settings in application.xml and ensure your database connection is set up first.");
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
