<?php
/**
 * TAppAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell\Actions
 */

namespace Prado\Shell\Actions;

use Prado\Shell\TShellInterpreter;
use Prado\Shell\TShellAction;

/**
 * Creates and run Prado application specific commands.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @package Prado\Shell\Actions
 * @since 4.2.0
 */
class TAppAction extends TShellAction
{
	protected $action = 'app';
	protected $parameters = [];
	protected $optional = ['directory', 'app-action'];
	protected $description = 'Initializes the Prado application in the given [directory] and performs the app action';

	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		$app = null;
		if (count($args) == 1) {
			$args[1] = '.';
		}
		if (false === ($xml = $this->getAppConfigFile($args[1]))) {
			echo "** Application '$args[1]' was not found. \n";
			return false;
		}
		$app = $this->initializePradoApplication($args[1]);
		TShellInterpreter::getInstance()->clearActionClass();
		TShellInterpreter::getInstance()->addActionClass('Prado\\Shell\\Actions\\TAppHelpAction');
		foreach ($app->getShellActionClasses() as $actions) {
			TShellInterpreter::getInstance()->addActionClass($actions);
		}
		$app->onLoadState();
		$app->onLoadStateComplete();
		TShellInterpreter::getInstance()->run($_SERVER['argv']);
		$app->onSaveState();
		$app->onSaveStateComplete();
		return true;
	}
}
