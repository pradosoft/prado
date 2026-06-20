<?php

/**
 * TShellApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell;

use Prado\Caching\ICache;
use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Shell\Actions\THelpAction;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\Actions\TPhpShellAction;
use Prado\Shell\Actions\TWebServerAction;
use Prado\IO\ITextWriter;
use Prado\IO\TStdOutWriter;
use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;

/**
 * TShellApplication class.
 *
 * TShellApplication is the base class for developing command-line PRADO
 * tools that share the same configurations as their Web application counterparts.
 *
 * A typical usage of TShellApplication in a command-line PHP script is as follows:
 * ```php
 * require 'path/to/vendor/autoload.php';
 * $application=new TShellApplication('path/to/application.xml');
 * $application->run($_SERVER);
 * // perform command-line tasks here
 * ```
 *
 * Since the application instance has access to all configurations, including
 * path aliases, modules and parameters, the command-line script has nearly the same
 * accessibility to resources as the PRADO Web applications.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> shell refactor
 * @since 3.1.0
 */
class TShellApplication extends \Prado\TApplication
{
	/**
	 * Hidden runtime directory name used when running without an application
	 * configuration file (no-config mode). Dot-prefixed so it is invisible to
	 * plain `ls` and consistent with other hidden project directories (.git,
	 * .cache, etc.).
	 * @since 4.3.3
	 */
	public const SHELL_RUNTIME_PATH = '.runtime';

	/** @var bool tells the application to be in quiet mode, levels [0..1], default 0, */
	private $_quietMode = 0;

	/**
	 * @var array<\Prado\Shell\TShellAction> cli shell Application commands. Modules can add their own command
	 */
	private $_actions = [];

	/**
	 * @var ?TShellWriter output writer.
	 */
	private $_outWriter;

	/**
	 * @var array<string, callable> application command options and property set callable
	 */
	private $_options = [];

	/**
	 * @var array<string, string> application command optionAliases of the short letter(s) and option name
	 */
	private $_optionAliases = [];

	/**
	 * @var array<array> The option help text and help values
	 */
	private $_optionsData = [];

	/**
	 * @var bool is the application help printed
	 */
	private $_helpPrinted = false;

	/**
	 * @var string[] arguments to the application
	 */
	private array $_arguments = [];

	/**
	 * Runs the application. Registers {@see processArguments} on
	 * {@see onConfiguration} at priority 20 before delegating to the
	 * parent {@see run()}.
	 * @param ?array<string> $args
	 */
	public function run($args = null)
	{
		array_shift($args);
		$this->setArguments($args);
		$this->detectShellLanguageCharset();

		$this->setWriter($this->createShellWriter());

		$this->registerOption('quiet', [$this, 'setQuietMode'], 'Quiets the output to <level> [1..3], default 1 (when specified)', '=<level>');
		$this->registerOptionAlias('q', 'quiet');

		$this->attachEventHandler('onConfiguration', [$this, 'processArguments'], 20);

		parent::run();
	}

	/**
	 * Maps the shell `LANG` environment variable to `$_SERVER['HTTP_ACCEPT_LANGUAGE']`
	 * and, when a charset suffix is present (e.g. `en_US.UTF-8`), to
	 * `$_SERVER['HTTP_ACCEPT_CHARSET']`, enabling PRADO's I18N stack in CLI contexts.
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
	 * Returns `true` when running as a CLI process with no interactive terminal —
	 * `TERM` is unset or `unknown` — which is characteristic of a system crontab invocation.
	 * @return bool whether the shell environment appears to be a crontab invocation.
	 * @since 4.2.2
	 */
	public static function detectCronTabShell()
	{
		return php_sapi_name() == 'cli' && (!($term = getenv('TERM')) || $term == 'unknown');
	}

	/**
	 * Processes application-level CLI options from {@see getArguments()}. Registered on {@see onConfiguration}.
	 * Calls {@see installShellActions()} first, then strips any registered `--option` and `-alias`
	 * flags, invoking their setter callbacks. Remaining positional arguments are re-indexed for
	 * command dispatch in {@see runService()}.
	 * @param object $sender
	 * @param mixed $param
	 * @since 4.2.0
	 */
	public function processArguments($sender, $param)
	{
		$this->installShellActions();

		$options = $this->getOptions();
		$aliases = $this->getOptionAliases();
		$arguments = $this->getArguments();
		foreach ($arguments as $i => $arg) {
			$arg = explode('=', $arg, 2);
			$processed = false;
			foreach ($options as $option => $setMethod) {
				$option = '--' . $option;
				if ($arg[0] === $option) {
					call_user_func($setMethod, $arg[1] ?? '');
					unset($arguments[$i]);
					$processed = true;
					break;
				}
			}
			if (!$processed) {
				foreach ($aliases as $alias => $_option) {
					$alias = '-' . $alias;
					if (isset($options[$_option]) && $arg[0] === $alias) {
						call_user_func($options[$_option], $arg[1] ?? '');
						unset($arguments[$i]);
						break;
					}
				}
			}
		}
		$this->setArguments(array_values($arguments));
	}

	/**
	 * Returns whether the application has at least one {@see ICache} module configured.
	 * Used by {@see installShellActions()} to gate {@see TFlushCachesAction} registration.
	 * @return bool whether any ICache module is present.
	 * @since 4.3.3
	 */
	public function hasCacheModules(): bool
	{
		return !empty($this->getModulesByType(ICache::class));
	}

	/**
	 * Returns whether the application has at least one {@see TActiveRecordConfig} module configured.
	 * Used by {@see installShellActions()} to gate {@see TActiveRecordAction} registration.
	 * @return bool whether any TActiveRecordConfig module is present.
	 * @since 4.3.3
	 */
	public function hasActiveRecordConfig(): bool
	{
		return !empty($this->getModulesByType(TActiveRecordConfig::class));
	}

	/**
	 * Returns whether the built-in development web server action should be available.
	 * Used by {@see installShellActions()} to gate {@see TWebServerAction} registration.
	 *
	 * True when the application is in {@see TApplicationMode::Debug} mode, or when the
	 * {@see TWebServerAction::DEV_WEBSERVER_PARAM} application parameter is set to a
	 * truthy value.
	 *
	 * @return bool whether {@see TWebServerAction} should be registered.
	 * @since 4.3.3
	 */
	public function hasDevWebServer(): bool
	{
		return $this->getMode() === \Prado\TApplicationMode::Debug
			|| TPropertyValue::ensureBoolean($this->getParameters()[TWebServerAction::DEV_WEBSERVER_PARAM]);
	}

	/**
	 * Installs the built-in shell actions. Always registers {@see THelpAction} and
	 * {@see TPhpShellAction}. Conditionally registers:
	 * - {@see TFlushCachesAction} — only when {@see hasCacheModules()} is true.
	 * - {@see TActiveRecordAction} — only when {@see hasActiveRecordConfig()} is true.
	 * - {@see TWebServerAction} — only when {@see hasDevWebServer()} is true.
	 * @since 4.3.0
	 */
	public function installShellActions()
	{
		if ($this->hasCacheModules()) {
			$this->addShellActionClass(TFlushCachesAction::class);
		}
		$this->addShellActionClass(THelpAction::class);
		$this->addShellActionClass(TPhpShellAction::class);
		if ($this->hasActiveRecordConfig()) {
			$this->addShellActionClass(TActiveRecordAction::class);
		}
		if ($this->hasDevWebServer()) {
			$this->addShellActionClass(TWebServerAction::class);
		}
	}

	/**
	 * Resolves the runtime directory path for shell invocations.
	 *
	 * When a configuration file was found (`$configFile !== null`), delegates
	 * to {@see TApplication::resolveRuntimePath()} for standard resolution.
	 *
	 * When no configuration file is present (no-config mode, e.g. running
	 * `prado-cli` directly inside the framework repository), a two-level
	 * strategy using {@see SHELL_RUNTIME_PATH} is applied:
	 *
	 * 1. `<basePath>/{@see SHELL_RUNTIME_PATH}` — preferred; a hidden directory
	 *    inside the project root, created automatically if absent and writable.
	 * 2. `sys_get_temp_dir()/prado-<hash>/{@see SHELL_RUNTIME_PATH}` — fallback,
	 *    keyed to `$basePath` so concurrent invocations of the same project
	 *    share a stable temp runtime without colliding with other Prado
	 *    installations.
	 *
	 * @param string $basePath the real, validated application base path.
	 * @param null|string $configFile the resolved configuration file path, or
	 *   `null` when no configuration file was found.
	 * @throws TConfigurationException if no writable runtime directory can be
	 *   created at either level.
	 * @return string absolute path to the runtime directory.
	 * @since 4.3.3
	 */
	protected function resolveRuntimePath(string $basePath, ?string $configFile): string
	{
		if ($configFile !== null) {
			return parent::resolveRuntimePath($basePath, $configFile);
		}

		// No config file — no-config mode.
		// Level 1: hidden directory in project root.
		$runtimePath = $basePath . DIRECTORY_SEPARATOR . static::SHELL_RUNTIME_PATH;
		if (!is_dir($runtimePath)) {
			if (@mkdir($runtimePath, Prado::getDefaultDirPermissions(), true) !== false) {
				@chmod($runtimePath, Prado::getDefaultDirPermissions());
				return $runtimePath;
			}
		} elseif (is_writable($runtimePath)) {
			return $runtimePath;
		}

		// Level 2: stable temp directory keyed by the real base path.
		$runtimePath = sys_get_temp_dir()
			. DIRECTORY_SEPARATOR . 'prado-' . sha1($basePath)
			. DIRECTORY_SEPARATOR . static::SHELL_RUNTIME_PATH;
		if (!is_dir($runtimePath)) {
			if (@mkdir($runtimePath, Prado::getDefaultDirPermissions(), true) === false) {
				throw new TConfigurationException('application_runtimepath_failed', $runtimePath);
			}
			@chmod($runtimePath, Prado::getDefaultDirPermissions());
		}
		return $runtimePath;
	}

	/**
	 * Shell applications do not start a web service — command dispatch is handled
	 * entirely by {@see runService()}.  This override suppresses the default
	 * service resolution and startup that {@see TApplication::initService()} would
	 * otherwise perform.
	 *
	 * @since 4.3.3
	 */
	protected function initService(): void
	{
		// do nothing
	}

	/**
	 * Dispatches the CLI command by iterating registered actions to find one that matches
	 * {@see getArguments()}, stripping action-specific options via {@see processActionArguments()},
	 * and calling the resolved method. Falls back to printing help when no action matched
	 * and quiet mode is off.
	 * @since 4.2.0
	 */
	public function runService()
	{
		$args = $this->getArguments();

		$outWriter = $this->getWriter();
		$valid = false;

		$this->printGreeting($outWriter);
		foreach ($this->getShellActions() as $class => $action) {
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
		if (!$valid && $this->getQuietMode() === 0) {
			$this->printHelp($outWriter);
		}
	}

	/**
	 * Strips option flags accepted by `$action` for `$method` from `$args`. Recognized
	 * `--option` flags and `-alias` shortcuts are set as properties on `$action` and removed
	 * from `$args`; unrecognized entries are left in place. `$args` is re-indexed on return.
	 * @param array $args
	 * @param TShellAction $action
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
	 * Flushes buffered output to the shell writer. When `$continueBuffering` is `false`,
	 * the writer is released after flushing.
	 * @param bool $continueBuffering whether to continue buffering after flush if buffering was active
	 * @since 4.2.0
	 */
	public function flushOutput($continueBuffering = true)
	{
		$this->getWriter()->flush();
		if (!$continueBuffering) {
			$this->setWriterDirect(null);
		}
	}

	/**
	 * Instantiates the action class via `Prado::createComponent()` and registers it in the
	 * action map keyed by class name. Accepts either a class-name string or an array with a
	 * `'class'` key.
	 * @param array|string $class action class name or array with a `'class'` key.
	 * @since 4.2.0
	 */
	public function addShellActionClass($class)
	{
		$this->_actions[is_array($class) ? $class['class'] : $class] = Prado::createComponent($class);
	}

	/**
	 * Returns whether an action of the given class name is registered.
	 * @param string $class fully-qualified action class name.
	 * @return bool whether the action is registered.
	 * @since 4.3.3
	 */
	public function hasShellActionClass(string $class): bool
	{
		return isset($this->_actions[$class]);
	}

	/**
	 * Returns the registered shell action map, keyed by action class name.
	 * @return TShellAction[] the shell actions for the application
	 * @since 4.2.0
	 */
	public function getShellActions()
	{
		return $this->_actions;
	}

	/**
	 * Returns the current CLI argument list.
	 * @return string[] the remaining positional arguments.
	 * @since 4.3.3
	 */
	protected function getArguments(): array
	{
		return $this->_arguments;
	}

	/**
	 * Sets the CLI argument list, replacing the current one entirely.
	 * @param string[] $args the argument list to set.
	 * @since 4.3.3
	 */
	protected function setArguments(array $args): void
	{
		$this->_arguments = $args;
	}


	/**
	 * Registers a CLI option (e.g. `--quiet`) with its setter callback and optional help text.
	 * When matched in {@see getArguments()}, `$setCallback` is called with the option's value.
	 * @param string $name name of the option at the command line
	 * @param callable $setCallback the callback to set the property
	 * @param string $description short description of the option
	 * @param string $values value placeholder shown in help, eg "=<level>"
	 * @since 4.2.0
	 */
	public function registerOption($name, $setCallback, $description = '', $values = '')
	{
		$this->_options[$name] = $setCallback;
		$this->_optionsData[$name] = [TPropertyValue::ensureString($description), TPropertyValue::ensureString($values)];
	}


	/**
	 * Maps a short alias (e.g. `-q`) to a previously registered option name (e.g. `quiet`).
	 * @param string $alias the short alias character(s)
	 * @param string $name the full option name this alias maps to
	 * @since 4.2.0
	 */
	public function registerOptionAlias($alias, $name)
	{
		$this->_optionAliases[$alias] = $name;
	}

	/**
	 * Returns the registered options map (name → setter callback).
	 * @return array<string, callable> the registered options.
	 * @since 4.3.3
	 */
	protected function getOptions(): array
	{
		return $this->_options;
	}

	/**
	 * Returns the registered option aliases map (alias → option name).
	 * @return array<string, string> the registered option aliases.
	 * @since 4.3.3
	 */
	protected function getOptionAliases(): array
	{
		return $this->_optionAliases;
	}

	/**
	 * Returns the option help data map (name → [description, value placeholder]).
	 * @return array<string, array> the registered options help data.
	 * @since 4.3.3
	 */
	protected function getOptionsData(): array
	{
		return $this->_optionsData;
	}

	/**
	 * Returns the raw output writer field without side effects. May be `null` before
	 * {@see run()} initialises it or after {@see flushOutput()} releases it.
	 * @return ?TShellWriter the raw output writer, or `null`.
	 * @since 4.3.3
	 */
	protected function getWriterDirect(): ?TShellWriter
	{
		return $this->_outWriter;
	}

	/**
	 * Sets the output writer field directly, allowing `null` to release it.
	 * Use {@see setWriter()} for normal assignment; this variant is used internally
	 * by {@see flushOutput()} to release the writer when `$continueBuffering` is `false`.
	 * @param ?TShellWriter $writer the writer to set, or `null` to release it.
	 * @since 4.3.3
	 */
	protected function setWriterDirect(?TShellWriter $writer): void
	{
		$this->_outWriter = $writer;
	}

	/**
	 * Creates the underlying raw output writer used by {@see createShellWriter()}.
	 * Override in a subclass to substitute a different {@see ITextWriter} implementation
	 * (e.g. a file writer or a test double).
	 * @return ITextWriter the inner writer.
	 * @since 4.3.3
	 */
	protected function createStdOutWriter(): ITextWriter
	{
		return new TStdOutWriter();
	}

	/**
	 * Creates the {@see TShellWriter} that wraps {@see createStdOutWriter()}.
	 * Override in a subclass to substitute a different {@see TShellWriter} subclass or
	 * to wrap the inner writer differently.
	 * @return TShellWriter the shell writer.
	 * @since 4.3.3
	 */
	protected function createShellWriter(): TShellWriter
	{
		return new TShellWriter($this->createStdOutWriter());
	}

	/**
	 * Returns the shell output writer, creating a default {@see TStdOutWriter}-backed
	 * instance on the first call if none has been set yet.
	 * @return TShellWriter the shell output writer.
	 * @since 4.2.0
	 */
	public function getWriter(): TShellWriter
	{
		if ($this->_outWriter === null) {
			$this->_outWriter = $this->createShellWriter();
		}
		return $this->_outWriter;
	}

	/**
	 * Sets the shell output writer.
	 * @param TShellWriter $writer the shell output writer.
	 * @since 4.2.0
	 */
	public function setWriter(TShellWriter $writer)
	{
		$this->setWriterDirect($writer);
	}

	/**
	 * Returns the current quiet mode level (`0`–`3`); `0` means full output (default).
	 * @return int the quiet mode level.
	 * @since 4.2.0
	 */
	public function getQuietMode(): int
	{
		return $this->_quietMode;
	}

	/**
	 * Sets the quiet mode level. An empty string maps to `1`; otherwise the value is clamped
	 * to `[0..3]`.
	 * @param int|string $quietMode desired quiet level, or empty string to default to `1`.
	 * @since 4.2.0
	 */
	public function setQuietMode($quietMode)
	{
		$this->_quietMode = ($quietMode === '' ? 1 : min(max((int) $quietMode, 0), 3));
	}


	/**
	 * Returns whether the greeting banner has already been printed.
	 * @return bool whether the greeting has been printed.
	 * @since 4.3.3
	 */
	protected function isHelpPrinted(): bool
	{
		return $this->_helpPrinted;
	}

	/**
	 * Sets whether the greeting banner has been printed.
	 * @param bool $value whether the greeting has been printed.
	 * @since 4.3.3
	 */
	protected function setHelpPrinted(bool $value): void
	{
		$this->_helpPrinted = $value;
	}

	/**
	 * Prints the Prado version banner to `$outWriter`. Guards against double-printing via
	 * {@see isHelpPrinted()}, and skips output entirely when quiet mode is active.
	 * @param TShellWriter $outWriter
	 * @since 4.2.0
	 */
	public function printGreeting($outWriter)
	{
		if (!$this->isHelpPrinted() && $this->getQuietMode() === 0) {
			$outWriter->write("  Command line tools for Prado " . Prado::getVersion() . ".", TShellWriter::DARK_GRAY);
			$outWriter->writeLine();
			$outWriter->flush();
			$this->setHelpPrinted(true);
		}
	}


	/**
	 * Prints the full CLI usage summary: calls {@see printGreeting()}, then lists usage syntax,
	 * conditional examples (cache/flush-all only when an {@see ICache} module is configured),
	 * registered options and aliases, and the available commands. Only commands registered via
	 * {@see installShellActions()} appear in the list.
	 * @param TShellWriter $outWriter
	 * @since 4.2.0
	 */
	public function printHelp($outWriter)
	{
		$this->printGreeting($outWriter);

		$outWriter->write("usage: ");
		$outWriter->writeLine("php prado-cli.php command[/action] <parameter> [optional]", [TShellWriter::BLUE, TShellWriter::BOLD]);
		$outWriter->writeLine();
		$outWriter->writeLine("example: prado-cli http");
		if ($this->hasShellActionClass(TFlushCachesAction::class)) {
			$outWriter->writeLine("example: php prado-cli.php cache/flush-all");
		}
		$outWriter->writeLine("example: prado-cli help");
		$outWriter->writeLine("example: prado-cli cron/tasks");
		$outWriter->writeLine();
		$outWriter->writeLine("The following options are available:");
		$outWriter->writeLine(str_pad("  -d=<folder>", 20) . " Loads the configuration.xml/php from <folder>");
		$optionsData = $this->getOptionsData();
		foreach ($this->getOptions() as $option => $callable) {
			$data = $optionsData[$option];
			$outWriter->writeLine(str_pad(" --{$option}{$data[1]}", 20) . ' ' . $data[0]);
		}
		foreach ($this->getOptionAliases() as $alias => $option) {
			$data = $optionsData[$option] ?? ['', ''];
			$outWriter->writeLine(str_pad("  -{$alias}{$data[1]}", 20) . " is an alias for --" . $option);
		}
		$outWriter->writeLine();
		$outWriter->writeLine("The following commands are available:");
		foreach ($this->getShellActions() as $action) {
			$action->setWriter($outWriter);
			$outWriter->writeLine($action->renderHelp());
		}
		$outWriter->writeLine("To see the help of each command, enter:");
		$outWriter->writeLine();
		$outWriter->writeLine("  prado-cli help <command-name>");
		$outWriter->writeLine();
	}
}
