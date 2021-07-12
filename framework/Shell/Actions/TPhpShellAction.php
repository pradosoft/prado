<?php
/**
 * TPhpShellAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell\Actions
 */

namespace Prado\Shell\Actions;

use Prado\Prado;
use Prado\Shell\TShellAction;

/**
 * Creates and run a Prado application in a PHP Shell.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Shell\Actions
 * @since 3.0.5
 */
class TPhpShellAction extends TShellAction
{
	protected $action = 'shell';
	protected $parameters = [];
	protected $optional = ['directory'];
	protected $description = 'Runs a PHP interactive interpreter. Initializes the Prado application in the given [directory].';

	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		if (count($args) > 1) {
			$loaded = $this->initializePradoApplication($args[1]);
			$this->getWriter()->flush();
			if ($loaded === false) {
				return true;
			}
		}

		$shell = new \Psy\Shell();
		$shell->setBoundObject($this);
		$shell->run();
		return true;
	}
}
