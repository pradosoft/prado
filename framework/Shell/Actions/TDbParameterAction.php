<?php
/**
 * TDbParameterAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell\Actions
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
 * @package Prado\Shell\Actions
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
	
	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function actionIndex($args)
	{
		$params = Prado::getApplication()->getParameters();
		$len = 0;
		foreach ($params as $key => $value) {
			$_len = strlen($key);
			if($len < $_len) {
				$len = $_len;
			}
		}
		$writer = $this->getWriter();
		$writer->writeLine();
		$writer->write($writer->pad($writer->format('Parameter Key', TShellWriter::UNDERLINE), $len + 1));
		$writer->writeLine('Parameter Key', TShellWriter::UNDERLINE);
		foreach ($params as $key => $value) {
			$writer->write($writer->pad($key, $len + 1));
			if(is_object($value)) {
				$value = '(object)';
			}
			if(is_array($value)) {
				$value = '(array)';
			}
			$writer->writeLine($writer->wrapText($value, $len + 1));
		}
		
		return true;
	}
	
	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function actionGet($args)
	{
		$writer = $this->getWriter();
		$writer->writeLine();
		
		if(!($key = ($args[1] ?? null)))
		{
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
	 * @param array $args parameters
	 * @return bool
	 */
	public function actionSet($args)
	{
		$writer = $this->getWriter();
		$writer->writeLine();
		
		if(!($key = ($args[1] ?? null)))
		{
			$this->getWriter()->writeError('Get Parameter needs a key');
			return true;
		}
		if(!($value = ($args[2] ?? null)))
		{
			$this->getWriter()->writeError('Set Parameter needs a key and Value');
			return true;
		}
		$autoload = TPropertyValue::ensureBoolean($args[3] ?? true);
		$module = null;
		foreach (Prado::getApplication()->getModulesByType('Prado\\Util\\TDbParameterModule') as $module) {
			if ($module) {
				break;
			}
		}
		if(!$module) {
			$writer->writeError('No TDbParameterModule found to set parameters');
		}
		$module->set($key, $value, $autoload, false);
		
		$writer->write('Set Parameter ');
		$writer->write($key, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$writer->write(' To: ');
		$writer->writeLine(Prado::varDump($value));
		$writer->writeLine();
		return true;
	}
}
