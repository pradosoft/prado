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
 * $application->run($_SERVER);
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
	 * @var array cli shell Application commands. Modules can add their own command
	 */
	private $_actions = [];
	
	/**
	 * @var TShellWriter output writer.
	 */
	protected $_outWriter;
	
	/**
	 * @var array<string, callable> application command options and property set callable
	 */
	protected $_options = [];
	
	/**
	 * @var array<string, string> application command optionAliases of the short letter(s) and option name
	 */
	protected $_optionAliases = [];
	
	/**
	 * @var bool is the application help printed
	 */
	protected $_helpPrinted = false;
	
	/**
	 * @var string[] arguments to the application
	 */
	private $_arguments;
	
	/**
	 * Runs the application.
	 * This method overrides the parent implementation by initializing
	 * application with configurations specified when it is created.
	 * @param null|array<string> $args
	 */
	public function run($args = null)
	{
		array_shift($args);
		$this->_arguments = $args;
		$this->detectShellLanguageCharset();
		
		$this->addShellActionClass('Prado\\Shell\\Actions\\TFlushCachesAction');
		$this->addShellActionClass('Prado\\Shell\\Actions\\THelpAction');
		$this->addShellActionClass('Prado\\Shell\\Actions\\TPhpShellAction');
		$this->addShellActionClass('Prado\\Shell\\Actions\\TActiveRecordAction');
		
		$this->_outWriter = new TShellWriter(new TOutputWriter());
		
		$this->attachEventHandler('onInitComplete', [$this, 'processArguments']);
		
		parent::run();
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
	 * This processes the arguments entered into the cli.  This is processed after
	 * the application is initialized and modules can
	 * @param object $sender
	 * @param mixed $param
	 * @since 4.2.0
	 */
	public function processArguments($sender, $param)
	{
		$options = array_merge(['quiet' => [$this, 'setQuietMode']], $this->_options);
		$aliases = array_merge(['q' => 'quiet'], $this->_optionAliases);
		$skip = false;
		foreach ($this->_arguments as $i => $arg) {
			$arg = explode('=', $arg);
			$processed = false;
			foreach ($options as $option => $setMethod) {
				$option = '--' . $option;
				if ($arg[0] === $option) {
					call_user_func($setMethod, $arg[1] ?? '');
					unset($this->_arguments[$i]);
					break;
				}
			}
			if (!$processed) {
				foreach ($aliases as $alias => $_option) {
					$alias = '-' . $alias;
					if (isset($options[$_option]) && $arg[0] === $alias) {
						call_user_func($options[$_option], $arg[1] ?? '');
						unset($this->_arguments[$i]);
						break;
					}
				}
			}
		}
		$this->_arguments = array_values($this->_arguments);
	}

	/**
	 * Runs the requested service.
	 * @since 4.2.0
	 */
	public function runService()
	{
		$args = $this->_arguments;
		
		$outWriter = $this->_outWriter;
		$valid = false;
		
		$this->printGreeting($outWriter);
		foreach ($this->_actions as $class => $action) {
			if (($method = $action->isValidAction($args)) !== null) {
				$action->setWriter($outWriter);
				$this->processActionArguments($args, $action, $method);
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
		if (!$valid && $this->_quietMode === 0) {
			$this->printHelp($outWriter);
		}
	}
	
	/**
	 * This processes the arguments entered into the cli
	 * @param array $args
	 * @param string $action
	 * @param string $method
	 * @since 4.2.0
	 */
	public function processActionArguments(&$args, $action, $method)
	{
		$options = $action->options($method);
		$aliases = $action->optionAliases();
		$skip = false;
		if (!$options) {
			return;
		}
		$keys = array_flip($options);
		foreach ($args as $i => $arg) {
			$arg = explode('=', $arg);
			$processed = false;
			foreach ($options as $_option) {
				$option = '--' . $_option;
				if ($arg[0] === $option) {
					$action->$_option = $arg[1] ?? '';
					$processed = true;
					unset($args[$i]);
					break;
				}
			}
			if (!$processed) {
				foreach ($aliases as $alias => $_option) {
					$alias = '-' . $alias;
					if (isset($keys[$_option]) && $arg[0] === $alias) {
						$action->$_option = $arg[1] ?? '';
						unset($args[$i]);
						break;
					}
				}
			}
		}
		$args = array_values($args);
	}
	

	/**
	 * Flushes output to shell.
	 * @param bool $continueBuffering whether to continue buffering after flush if buffering was active
	 * @since 4.2.0
	 */
	public function flushOutput($continueBuffering = true)
	{
		$this->_outWriter->flush();
		if (!$continueBuffering) {
			$this->_outWriter = null;
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
	 * @return Prado\Shell\TShellAction[] the shell actions for the application
	 * @since 4.2.0
	 */
	public function getShellActions()
	{
		return $this->_actions;
	}
	

	/**
	 * This registers shell command line options and the setter callback
	 * @param string $name name of the option at the command line
	 * @param callable $setCallback the callback to set the property
	 * @since 4.2.0
	 */
	public function registerOption($name, $setCallback)
	{
		$this->_options[$name] = $setCallback;
	}
	

	/**
	 * This registers shell command line option aliases and linked variable
	 * @param string $alias the short command
	 * @param string $name the command name
	 * @since 4.2.0
	 */
	public function registerOptionAlias($alias, $name)
	{
		$this->_optionAliases[$alias] = $name;
	}
	
	/**
	 * @return \Prado\Shell\TShellWriter the writer for the class
	 * @since 4.2.0
	 */
	public function getWriter(): TShellWriter
	{
		return $this->_outWriter;
	}
	
	/**
	 * @@param \Prado\Shell\TShellWriter $writer the writer for the class
	 * @since 4.2.0
	 */
	public function setWriter(TShellWriter $writer)
	{
		$this->_outWriter = $writer;
	}
	
	/**
	 * @return int the writer for the class, default 0
	 * @since 4.2.0
	 */
	public function getQuietMode(): int
	{
		return $this->_quietMode;
	}
	
	/**
	 * @@param int $quietMode the writer for the class, [0..3]
	 * @since 4.2.0
	 */
	public function setQuietMode($quietMode)
	{
		$this->_quietMode = ($quietMode === '' ? 1 : min(max((int) $quietMode, 0), 3));
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
		$outWriter->writeLine(str_pad("  -q=<level>", 20) . "Quiets the output to <level> [1..3]");
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
