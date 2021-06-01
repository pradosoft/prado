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
 * @author Brad Anderson <belisoful@icloud.com>
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
		$this->detectShellLanguageCharset();
		$this->initApplication();
	}
	
	/**
	 * This takes the shell LANG and sets the HTTP_ACCEPT_LANGUAGE/HTTP_ACCEPT_CHARSET
	 * for the application to do I18N.
	 * @since 4.2.0
	 */
	private function detectShellLanguageCharset()
	{
		if (isset($_SERVER['LANG'])) {
			$lang = $_SERVER['LANG'];
			$pos = strpos($lang, '.');
			if ($pos !== false) {
				$_SERVER['HTTP_ACCEPT_CHARSET'] = substr($lang, $pos + 1);
				$lang = substr($lang, 0, $pos);
			}
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $lang;
		}
	}
	
	/**
	 * @param string $v a CLI Action class to add to the list of what the application is capable
	 * @since 4.2.0
	 */
	public function addShellActionClass($v)
	{
		$this->_actionClasses[] = $v;
	}
	
	/**
	 * @@return string[] the CLI Action classes that the application has registered
	 * @since 4.2.0
	 */
	public function getShellActionClasses()
	{
		return $this->_actionClasses;
	}
}
