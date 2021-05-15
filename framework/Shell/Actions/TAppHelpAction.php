<?php
/**
 * TAppHelpAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell\Actions
 */

namespace Prado\Shell\Actions;

use Prado\Shell\TShellAppAction;

/**
 * The Application specific action for help.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @package Prado\Shell\Actions
 * @since 4.2.0
 */
class TAppHelpAction extends TShellAppAction
{
	protected $action = 'help';
	protected $parameters = [];
	protected $optional = [];
	protected $description = 'outputs the CLI Application help';
	
	/**
	 * @param array $args parameters
	 * @return bool
	 */
	public function performAction($args)
	{
		// by not handling this, the parent caller will render the help automatically.
		return false;
	}
}
