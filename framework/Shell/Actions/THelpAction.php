<?php

/**
 * THelpAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell\Actions;

use Prado\Prado;
use Prado\Shell\TShellAction;

/**
 * THelpAction class.
 *
 * The help for a specific command.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @since 4.2.0
 */
class THelpAction extends TShellAction
{
	protected $action = 'help';
	protected $methods = ['index'];
	protected $parameters = [null];
	protected $optional = ['command'];
	protected $description = [
		'Provides help information about shell commands.',
		'Displays available commands or detailed command information.'];

	/**
	 * displays help for a specific command or the general help
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionIndex($args)
	{
		if (isset($args[1])) {
			foreach ($this->getApplication()->getShellActions() as $action) {
				$cmdname = $action->getAction();
				if (0 === strncasecmp($cmdname, $args[1], strlen($cmdname))) {
					$action->setWriter($this->getWriter());
					$action->renderHelpCommand($args[1]);
					return true;
				}
			}
		} else {
			$app = $this->getApplication();
			$app->printHelp($this->getWriter());
			return true;
		}
		return false;
	}
}
