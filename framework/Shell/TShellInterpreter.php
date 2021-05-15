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
			echo "Command line tools for Prado " . Prado::getVersion() . ".\n";
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
		$valid = false;
		$_actions = $this->_actions;
		foreach ($_actions as $class => $action) {
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
		TShellInterpreter::getInstance()->printGreeting();

		echo "usage: php prado-cli.php action <parameter> [optional]\n";
		echo "example: php prado-cli.php flushcaches /prado_app_directory\n\n";
		echo "example: php prado-cli.php app /prado_app_directory help\n\n";
		echo "example: php prado-cli.php app /prado_app_directory cron tasks\n\n";
		echo "actions:\n";
		foreach ($this->_actions as $action) {
			echo $action->renderHelp();
		}
	}
}
