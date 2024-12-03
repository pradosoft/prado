<?php

/**
 * TPhpShellAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell\Actions;

use Prado\Prado;
use Prado\Shell\TShellAction;

/**
 * Creates and run a Prado application in a PHP Shell.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com> shell refactor
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0.5
 */
class TPhpShellAction extends TShellAction
{
	protected $action = 'shell';
	protected $methods = ['index'];
	protected $parameters = [null];
	protected $optional = [null];
	protected $description = [
		'Provides PHP Interactive Shell Interpreter.',
		'Runs a PHP interactive interpreter after Initializing the Prado application.'];

	/**
	 * This runs the interactive PHP Shell
	 * @param array $args parameters
	 * @return bool
	 */
	public function actionIndex($args)
	{
		$this->getWriter()->flush();
		$app = Prado::getApplication();

		$shell = new \Psy\Shell();
		$shell->setBoundObject($app);
		$shell->run();

		return true;
	}
}
