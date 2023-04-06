<?php
/**
 * TFlushCachesAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell\Actions;

use Prado\Prado;
use Prado\Caching\ICache;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;

/**
 * This command clears all application modules implementing ICache.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @since 4.2.0
 */
class TFlushCachesAction extends TShellAction
{
	protected $action = 'cache';
	protected $methods = ['index', 'flush', 'flush-all'];
	protected $parameters = [null, 'module', null];
	protected $optional = [null, 'module2...', null];
	protected $description = [
		'Allows you to flush the cache(s).',
		'Displays the cache modules that can be flushed.',
		'Flushes the specified ICache modules.',
		'Flushes all application ICache modules.',
	];

	/**
	 * This flushes an array of ICache modules
	 * @param array $args parameters
	 * @return bool Action was performed
	 */
	public function actionFlush($args)
	{
		$app = Prado::getApplication();
		$this->_outWriter->writeLine();
		$this->_outWriter->writeLine("Flushing Cache: ");

		$found = false;
		array_shift($args); //Shift off the 'action/method'
		foreach ($app->getModulesByType('Prado\\Caching\\ICache') as $id => $module) {
			if (in_array($id, $args)) {
				$module = (!$module) ? $app->getModule($id) : $module;
				$module->flush();
				$this->_outWriter->write('  ');
				$this->_outWriter->write($id, [TShellWriter::GREEN, TShellWriter::BOLD]);
				$this->_outWriter->writeLine(' (' . get_class($module) . ')');
				$found = true;
			}
		}
		if (!$found) {
			$this->_outWriter->writeLine('  (no cache was found)', [TShellWriter::RED, TShellWriter::BOLD]);
		}
		$this->_outWriter->writeLine();
		return true;
	}

	/**
	 * This flushes all the ICaches in the application
	 * @param array $args parameters
	 * @return bool Action was performed
	 */
	public function actionFlushAll($args)
	{
		$app = Prado::getApplication();
		$this->_outWriter->writeLine();
		$this->_outWriter->writeLine("Flushing All Caches: ");

		$module = null;
		foreach ($app->getModulesByType('Prado\\Caching\\ICache') as $id => $module) {
			$module = (!$module) ? $app->getModule($id) : $module;
			$module->flush();
			$this->_outWriter->write('  ');
			$this->_outWriter->write($id, [TShellWriter::GREEN, TShellWriter::BOLD]);
			$this->_outWriter->writeLine(' (' . get_class($module) . ')');
		}
		if (!$module) {
			$this->_outWriter->writeLine('  (no caches were found)', [TShellWriter::RED, TShellWriter::BOLD]);
		}
		$this->_outWriter->writeLine();
		return true;
	}

	/**
	 * Displays the ICache (by module ID) in the application that can be flushed
	 * @param array $args parameters
	 * @return bool Action was performed
	 */
	public function actionIndex($args)
	{
		$app = Prado::getApplication();
		$this->_outWriter->writeLine();
		$this->_outWriter->writeLine("Available Caches: ");

		$module = null;
		foreach ($app->getModulesByType('Prado\\Caching\\ICache') as $id => $module) {
			$module = (!$module) ? $app->getModule($id) : $module;
			$this->_outWriter->write('  ');
			$this->_outWriter->write($id, [TShellWriter::BLUE, TShellWriter::BOLD]);
			$this->_outWriter->writeLine(' (' . get_class($module) . ')');
		}
		if (!$module) {
			$this->_outWriter->writeLine('  (no caches were found)', [TShellWriter::RED, TShellWriter::BOLD]);
		}
		$this->_outWriter->writeLine();
		return true;
	}
}
