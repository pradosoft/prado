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
	 * Displays the general help, or the help of the command named by `$args[1]`.  The
	 * command matches an action name case-insensitively; a `/<method>` suffix is ignored.
	 * @param array $args parameters
	 * @return bool whether help was displayed; false without a shell application or
	 *   when no action has the command name.
	 */
	public function actionIndex($args)
	{
		if (($app = $this->getApplication()) === null) {
			return false;
		}
		if (!isset($args[1])) {
			$app->printHelp($this->getWriter());
			return true;
		}
		$command = explode('/', $args[1], 2)[0];
		foreach ($app->getShellActions() as $action) {
			if (strcasecmp($action->getAction(), $command) === 0) {
				$action->setWriter($this->getWriter());
				$action->renderHelpCommand($args[1]);
				return true;
			}
		}
		return false;
	}
}
