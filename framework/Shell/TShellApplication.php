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

use Prado\Prado;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Shell\Actions\THelpAction;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\Actions\TPhpShellAction;

use Prado\IO\ITextWriter;
use Prado\IO\TOutputWriter;
use Prado\Shell\TShellWriter;

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
 * @author Brad Anderson <belisoful@icloud.com> shell refactor
 * @package Prado\Shell
 * @since 3.1.0
 */
class TShellApplication extends \Prado\TApplication
{
	/** @var bool  tells the application to be in quiet mode, levels [0..1], default 0, */
	private $_quietMode = 0;
	
	/**
	 * @var cli shell Application commands. Modules can add their own command
	 */
	private $_actions = [];
	
	/**
	 * @var TShellWriter output writer.
	 */
	protected $_outWriter;
	
	protected $_helpPrinted = false;
	
	/**
	 * Runs the application.
	 * This method overrides the parent implementation by initializing
	 * application with configurations specified when it is created.
	 * @param null|array<string> $args
	 */
	public function run($args = null)
	{
		$this->detectShellLanguageCharset();
		$this->processArguments($args);
		
		$this->addShellActionClass('Prado\\Shell\\Actions\\TFlushCachesAction');
		$this->addShellActionClass('Prado\\Shell\\Actions\\THelpAction');
		$this->addShellActionClass('Prado\\Shell\\Actions\\TPhpShellAction');
		$this->addShellActionClass('Prado\\Shell\\Actions\\TActiveRecordAction');
		
		$this->_outWriter = new TShellWriter(new TOutputWriter());
		
		$this->initApplication();
		
		$this->onLoadState();
		$this->onLoadStateComplete();
		
		$this->runCommand($args);
		
		$this->onSaveState();
		$this->onSaveStateComplete();
		$this->onPreFlushOutput();
		$this->flushOutput();
		$this->onEndRequest();
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
	 * This processes the arguments entered into the cli
	 * @param array $args
	 * @since 4.2.0
	 */
	public function processArguments($args)
	{
		$options = ['-q'];
		$skip = false;
		foreach ($args as $i => $arg) {
			foreach ($options as $option) {
				$len = strlen($option);
				if (strncasecmp($arg, $option, $len) === 0) {
					$value = substr($arg, $len);
					if (isset($value[0]) && $value[0] === '=') {
						$value = substr($value, 1);
					}
					if ($option === '-q') {
						$this->_quietMode = max(1, (int) $value);
					}
					break;
				}
			}
		}
	}
	
	/**
	 * This processes the command entered into the cli
	 * @param array $args
	 * @since 4.2.0
	 */
	public function runCommand($args)
	{
		if (count($args) > 1) {
			array_shift($args);
		}
		$outWriter = $this->_outWriter;
		$valid = false;
		
		$this->printGreeting($outWriter);
		foreach ($this->_actions as $class => $action) {
			if (($method = $action->isValidAction($args)) !== null) {
				$action->setWriter($outWriter);
				$m = 'action' . str_replace('-', '', $method);
				if (method_exists($action, $m)) {
					$valid |= call_user_func([$action, $m], $args);
				} else {
					$outWriter->writeError("$method is not an available command");
					$valid = true;
				}
				break;
			}
		}
		if (!$valid) {
			$this->printHelp($outWriter);
		}
	}

	/**
	 * @param string $class action class name
	 * @since 4.2.0
	 */
	public function addShellActionClass($class)
	{
		$this->_actions[$class] = new $class;
	}

	/**
	 * @@return Prado\Shell\TShellAction[] the shell actions for the application
	 * @since 4.2.0
	 */
	public function getShellActions()
	{
		return $this->_actions;
	}
	

	/**
	 * Flushes output to shell.
	 * @param bool $continueBuffering whether to continue buffering after flush if buffering was active
	 */
	public function flushOutput($continueBuffering = true)
	{
		$this->_outWriter->flush();
		if (!$continueBuffering) {
			$this->_outWriter = null;
		}
	}
	
	/**
	 * @return ITextWriter the writer for the class
	 */
	public function getWriter(): ITextWriter
	{
		return $this->_outWriter;
	}
	
	/**
	 * @@param ITextWriter $writer the writer for the class
	 */
	public function setWriter(ITextWriter $writer)
	{
		$this->_outWriter = $writer;
	}
	
	/**
	 * @return int the writer for the class
	 */
	public function getQuietMode(): int
	{
		return $this->_quietMode;
	}
	
	/**
	 * @@param int $quietMode the writer for the class
	 */
	public function setQuietMode(int $quietMode)
	{
		$this->_quietMode = $quietMode;
	}
	

	/**
	 * @param string $class action class name
	 * @param mixed $outWriter
	 * @since 4.2.0
	 */
	public function printGreeting($outWriter)
	{
		if (!$this->_helpPrinted && $this->_quietMode === 0) {
			$outWriter->write("  Command line tools for Prado " . Prado::getVersion() . ".", TShellWriter::DARK_GRAY);
			$outWriter->writeLine();
			$outWriter->flush();
			$this->_helpPrinted = true;
		}
	}


	/**
	 * Print command line help, default action.
	 * @param mixed $outWriter
	 * @since 4.2.0
	 */
	public function printHelp($outWriter)
	{
		$this->printGreeting($outWriter);
		
		$outWriter->write("usage: ");
		$outWriter->writeLine("php prado-cli.php command[/action] <parameter> [optional]", [TShellWriter::BLUE, TShellWriter::BOLD]);
		$outWriter->writeLine();
		$outWriter->writeLine("example: php prado-cli.php cache/flush-all");
		$outWriter->writeLine("example: prado-cli help");
		$outWriter->writeLine("example: prado-cli cron/tasks");
		$outWriter->writeLine();
		$outWriter->writeLine("The following options are available:");
		$outWriter->writeLine(str_pad("  -d=<folder>", 20) . "Loads the configuration.xml/php from <folder>");
		$outWriter->writeLine();
		$outWriter->writeLine("The following commands are available:");
		foreach ($this->_actions as $action) {
			$action->setWriter($outWriter);
			$outWriter->writeLine($action->renderHelp());
		}
		$outWriter->writeLine("To see the help of each command, enter:");
		$outWriter->writeLine();
		$outWriter->writeLine("  prado-cli help <command-name>");
		$outWriter->writeLine();
	}
}
