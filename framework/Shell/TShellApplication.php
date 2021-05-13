<?php
/**
 * TShellApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell
 */

namespace Prado\Shell;

/**
 * TShellApplication class.
 *
 * TShellApplication is the base class for developing command-line PRADO
 * tools that share the same configurations as their Web application counterparts.
 *
 * A typical usage of TShellApplication in a command-line PHP script is as follows:
 * <code>
 * require 'path/to/vendor/autoload.php';
 * $application=new TShellApplication('path/to/application.xml');
 * $application->run();
 * // perform command-line tasks here
 * </code>
 *
 * Since the application instance has access to all configurations, including
 * path aliases, modules and parameters, the command-line script has nearly the same
 * accessibility to resources as the PRADO Web applications.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Shell
 * @since 3.1.0
 */
class TShellApplication extends \Prado\TApplication
{
	/**
	 * @var cli shell Application commands. Modules can add their own command
	 */
	private $_actionClasses = [];
	
	/**
	 * Runs the application.
	 * This method overrides the parent implementation by initializing
	 * application with configurations specified when it is created.
	 */
	public function run()
	{
		$this->initApplication();
	}
	
	/**
	 * @param $v string a CLI Action class to add to the list of what the app is capable
	 */
	public function addShellActionClass($v)
	{
		$this->_actionClasses[] = $v;
	}
	
	/**
	 * @@return array the CLI Action classes that the application has registered
	 */
	public function getShellActionClasses()
	{
		return $this->_actionClasses;
	}
}
