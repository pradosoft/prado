<?php

/**
 * TSocketServerAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TSocketException;
use Prado\Prado;
use Prado\Shell\TShellAction;
use Prado\TPropertyValue;

/**
 * TSocketServerAction class.
 *
 * Runs a {@see TSocketServerModule} from the command line: `php prado-cli.php socket/serve` binds
 * the configured endpoint and serves until interrupted (CTRL-C / SIGTERM).  The `--address` and
 * `--port` options override the module's configured endpoint for the run.
 *
 * The action is registered by the module ({@see TSocketServerModule::registerShellAction()}), which
 * injects itself as the {@see setSocketServerModule() SocketServerModule}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketServerAction extends TShellAction
{
	protected $action = 'socket';
	protected $methods = ['serve'];
	protected $parameters = [null];
	protected $optional = [null];
	protected $description = [
		'Runs a socket server module.',
		'Binds the configured endpoint and serves until interrupted.'];

	/** @var null|false|TSocketServerModule The socket server module: false until resolved, then the module or null when none is configured. */
	private $_module = false;

	/** @var ?string The address override for this run. */
	private ?string $_address = null;

	/** @var ?int The port override for this run. */
	private ?int $_port = null;

	/**
	 * Returns the module class the action drives.
	 * @return string The module class name.
	 */
	public function getModuleClass(): string
	{
		return TSocketServerModule::class;
	}

	/**
	 * Returns the socket server module of the application, resolved by type on first use.
	 * @return ?TSocketServerModule The module, or null when none is configured.
	 */
	public function getSocketServerModule(): ?TSocketServerModule
	{
		if ($this->_module === false) {
			$app = Prado::getApplication();
			$moduleClass = $this->getModuleClass();
			$this->_module = null;
			foreach ($app->getModulesByType($moduleClass, false) as $id => $m) {
				if ($this->_module = $app->getModule($id)) {
					break;
				}
			}
			if (!$this->_module) {
				$this->getWriter()->writeError("A {$moduleClass} is not found");
				return null;
			}
		}
		return $this->_module;
	}

	/**
	 * Sets the socket server module the action drives.
	 * @param ?TSocketServerModule $module The module.
	 */
	public function setSocketServerModule($module): void
	{
		$this->_module = $module;
	}

	/**
	 * Returns the address override for this run.
	 * @return ?string The address override, or null to use the module's address.
	 */
	public function getAddress(): ?string
	{
		return $this->_address;
	}

	/**
	 * Sets the address override for this run.
	 * @param ?string $value The address override.
	 * @return static The current action.
	 */
	public function setAddress($value): static
	{
		$this->_address = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * Returns the port override for this run.
	 * @return ?int The port override, or null to use the module's port.
	 */
	public function getPort(): ?int
	{
		return $this->_port;
	}

	/**
	 * Sets the port override for this run.
	 * @param null|int|string $value The port override.
	 * @return static The current action.
	 */
	public function setPort($value): static
	{
		$this->_port = ($value === null || $value === '') ? null : TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * Properties set by command line option for the action.
	 * @param string $methodID The action method being executed.
	 * @return array The properties settable by option.
	 */
	public function options($methodID): array
	{
		if ($methodID === 'serve') {
			return ['address', 'port'];
		}
		return [];
	}

	/**
	 * Aliases for the command line options.
	 * @return array<string, string> The alias => property map.
	 */
	public function optionAliases(): array
	{
		return ['a' => 'address', 'p' => 'port'];
	}

	/**
	 * Runs the socket server until interrupted.  A misconfigured endpoint or a failed bind is
	 * reported to the shell as an error rather than an uncaught exception.
	 * @param string[] $args The command line arguments.
	 * @return bool Whether the action handled the command.
	 */
	public function actionServe($args): bool
	{
		$module = $this->getSocketServerModule();
		if (!$module) {
			return true;
		}
		if ($this->_address !== null) {
			$module->setAddress($this->_address);
		}
		if ($this->_port !== null) {
			$module->setPort($this->_port);
		}

		$writer = $this->getWriter();
		try {
			$endpoint = $module->getEndpoint();   // resolve and validate before announcing or binding
			$writer->writeLine();
			$writer->writeLine("Socket server listening on {$endpoint}");
			$writer->writeLine("To quit press CTRL-C or COMMAND-C.");
			$writer->writeLine();
			$writer->flush();
			return $module->serve();
		} catch (TConfigurationException | TSocketException $e) {
			$writer->writeError($e->getMessage());
			return true;
		}
	}
}
