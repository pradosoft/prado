<?php

/**
 * TDbParameterAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell\Actions;

use Prado\Prado;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;

/**
 * TDbParameterAction class.
 *
 * The indexes, gets, and sets the TDbParameterModule.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @since 4.2.0
 */
class TDbParameterAction extends TShellAction
{
	protected $action = 'param';
	protected $methods = ['index', 'get', 'set'];
	protected $parameters = [null, 'param-key', ['param-key', 'param-value']];
	protected $optional = [null, null, null];
	protected $description = [
		'Provides indexing, getting and setting parameters in the Database.',
		'Displays all the variables in the database.',
		'Gets a specific parameter by <param-key>.',
		'Sets a specific parameter <param-key> to <param-value>.'];

	private $_allParams = false;

	private $_dbparam = false;

	/**
	 *
	 */
	public function getAll()
	{
		return $this->_allParams;
	}

	/**
	 * @param bool $value If this is called, set the property to true
	 */
	public function setAll($value)
	{
		$this->_allParams = TPropertyValue::ensureBoolean($value === '' ? true : $value);
	}

	/**
	 * Properties for the action set by parameter
	 * @param string $methodID the action being executed
	 * @return array properties for the $actionID
	 */
	public function options($methodID): array
	{
		if ($methodID === 'index') {
			return ['all'];
		}
		return [];
	}

	/**
	 * Aliases for the properties to be set by parameter
	 * @return array<string, string> alias => property for the $actionID
	 */
	public function optionAliases(): array
	{
		return ['a' => 'all'];
	}

	/**
	 * display the database parameter key values.
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionIndex($args)
	{
		$writer = $this->getWriter();
		if (!($module = $this->getDbParameterModule())) {
			$writer->writeError('No TDbParameterModule found to set parameters');
			return;
		}

		$params = Prado::getApplication()->getParameters();
		$len = 0;
		foreach ($params as $key => $value) {
			$_len = strlen($key);
			if ($len < $_len) {
				$len = $_len;
			}
		}
		$writer->writeLine();
		$writer->write($writer->pad($writer->format('Parameter Key', TShellWriter::UNDERLINE), $len + 1));
		$writer->writeLine('Parameter Key', TShellWriter::UNDERLINE);
		foreach ($params as $key => $value) {
			if (!$this->getAll() && !$module->exists($key)) {
				continue;
			}
			$writer->write($writer->pad($key, $len + 1));
			if (is_object($value)) {
				$value = '(object)';
			}
			if (is_array($value)) {
				$value = '(array)';
			}
			$writer->writeLine($writer->wrapText($value, $len + 1));
		}
		$writer->writeLine();
		return true;
	}

	/**
	 * gets a parameter value
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionGet($args)
	{
		$writer = $this->getWriter();
		$writer->writeLine();

		if (!($key = ($args[1] ?? null))) {
			$writer->writeError('Get Parameter needs a key');
			return true;
		}
		$writer->write('Parameter ');
		$writer->write($key, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$writer->write(': ');
		$value = Prado::getApplication()->getParameters()[$key];

		$writer->writeLine(Prado::varDump($value));
		$writer->writeLine();
		return true;
	}

	/**
	 * Sets a parameter value
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionSet($args)
	{
		$writer = $this->getWriter();
		$writer->writeLine();

		if (!($key = ($args[1] ?? null))) {
			$this->getWriter()->writeError('Get Parameter needs a key');
			return true;
		}
		if (!($value = ($args[2] ?? null))) {
			$this->getWriter()->writeError('Set Parameter needs a key and Value');
			return true;
		}
		$autoload = TPropertyValue::ensureBoolean($args[3] ?? true);

		if (!($module = $this->getDbParameterModule())) {
			$writer->writeError('No TDbParameterModule found to set parameters');
			return true;
		}

		$module->set($key, $value, $autoload, false);

		$writer->write('Set Parameter ');
		$writer->write($key, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$writer->write(' To: ');
		$writer->writeLine(Prado::varDump($value));
		$writer->writeLine();
		return true;
	}

	/**
	 * get the TDBParameterModule from the Application
	 * @return null|\Prado\Util\TDbParameterModule
	 */
	public function getDbParameterModule()
	{
		if ($this->_dbparam === false) {
			$this->_dbparam = null;
			$app = Prado::getApplication();
			foreach ($app->getModulesByType(\Prado\Util\TDbParameterModule::class) as $id => $module) {
				if ($this->_dbparam = $app->getModule($id)) {
					break;
				}
			}
		}
		return $this->_dbparam;
	}

	/**
	 * get the TPermissionsManager from the Application
	 * @param \Prado\Util\TDbParameterModule $dbparam
	 */
	public function setDbParameterModule($dbparam)
	{
		$this->_dbparam = $dbparam;
	}
}
