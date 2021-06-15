<?php
/**
 * TActiveRecordGenAllAction class file
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
 * Create active record skeleton for all tables in DB and its relations
 *
 * @author Matthias Endres <me[at]me23[dot]de>
 * @author Daniel Sampedro Bello <darthdaniel85[at]gmail[dot]com>
 * @package Prado\Shell\Actions
 * @since 3.2
 */
class TActiveRecordGenAllAction extends TShellAction
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
			$cmd = new TActiveRecordGenAction;
			$cmd->performAction($args);
		} else {
			$this->_outWriter->writeLine("\n    Usage: generate table_name Application.pages.RecordClassName");
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
		$this->_outWriter->writeError('Unable to find directory "' . $dir . "\".");
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
			$this->_outWriter->writeError('Unable to find TActiveRecordConfig module in ' . $xml . "");
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
		$this->_outWriter->writeError('Output file ' . $file . ' must be within directory ' . $app_dir . "");
		return false;
	}
}
