<?php
/**
 * TApplication class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell
 */

namespace Prado\Shell;

use Prado\IO\TOutputWriter;
use Prado\Prado;

/**
 * TShellInterpreter Class
 *
 * Command line interface, configures the action classes and dispatches the command actions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Shell
 * @since 3.0.5
 */
class TShellInterpreter
{
	/**
	 * @var array command action classes
	 */
	protected $_actions = [];
	
	protected $_helpPrinted = false;
	
	protected $_outWriter;

	/**
	 * @param string $class action class name
	 */
	public function addActionClass($class)
	{
		$this->_actions[$class] = new $class;
	}

	/**
	 */
	public function clearActionClass()
	{
		$this->_actions = [];
	}

	/**
	 * @return TShellInterpreter static instance
	 */
	public static function getInstance()
	{
		static $instance;
		if ($instance === null) {
			$instance = new self;
		}
		return $instance;
	}

	public function printGreeting()
	{
		if (!$this->_helpPrinted) {
			$this->_outWriter->write("Command line tools for Prado " . Prado::getVersion() . ".", TShellWriter::DARK_GRAY);
			$this->_outWriter->writeLine();
			$this->_outWriter->flush();
			$this->_helpPrinted = true;
		}
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
		$outWriter = $this->_outWriter = new TShellWriter(new TOutputWriter());
		$valid = false;
		$_actions = $this->_actions;
		foreach ($_actions as $class => $action) {
			if ($action->isValidAction($args)) {
				$action->setWriter($outWriter);
				$valid |= $action->performAction($args);
				break;
			} else {
				$valid = false;
			}
		}
		if (!$valid) {
			$this->printHelp($outWriter);
		}
		$outWriter->flush();
	}

	/**
	 * Print command line help, default action.
	 * @param mixed $outWriter
	 */
	public function printHelp($outWriter)
	{
		TShellInterpreter::getInstance()->printGreeting($outWriter);
		
		$outWriter->write("usage: ");
		$outWriter->writeLine("php prado-cli.php action <parameter> [optional]", [TShellWriter::BLUE, TShellWriter::BOLD]);
		$outWriter->writeLine();
		$outWriter->writeLine("example: php prado-cli.php flushcaches /prado_app_directory");
		$outWriter->writeLine("example: php prado-cli.php app /prado_app_directory help");
		$outWriter->writeLine("example: php prado-cli.php app /prado_app_directory cron tasks");
		$outWriter->writeLine();
		$outWriter->writeLine("The following commands are available:");
		foreach ($this->_actions as $action) {
			$action->setWriter($outWriter);
			$outWriter->writeLine($action->renderHelp());
		}
	}
}
