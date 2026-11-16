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
	 * Runs the PsySH interactive PHP shell bound to the application.  Without the
	 * `psy/psysh` package, an error names the package to install.
	 * @param array $args parameters
	 * @return bool
	 */
	public function actionIndex($args)
	{
		if (!$this->isShellAvailable()) {
			$this->getWriter()->writeError('The shell command needs psy/psysh: run "composer require psy/psysh".');
			return true;
		}
		$this->getWriter()->flush();

		$shell = $this->createShell();
		$shell->setBoundObject(Prado::getApplication());
		$shell->run();

		return true;
	}

	/**
	 * Returns whether the `psy/psysh` package is installed.
	 * @return bool whether `\Psy\Shell` exists.
	 * @since 4.4.0
	 */
	protected function isShellAvailable(): bool
	{
		return class_exists(\Psy\Shell::class);
	}

	/**
	 * Creates the PsySH shell {@see actionIndex()} runs.
	 * @return \Psy\Shell the interactive shell.
	 * @since 4.4.0
	 */
	protected function createShell(): \Psy\Shell
	{
		return new \Psy\Shell();
	}
}
