<?php
/**
 * TWebServerAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell\Actions;

use Prado\Prado;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellApplication;
use Prado\TPropertyValue;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TWebServerAction class
 *
 * This class serves the application with the built-in PHP testing web server.
 *
 * When no network address is specified, the web server will listen on the default
 * 127.0.0.1 network interface and on port 8080.  The application is accessible on
 * the machine web browser at web address "http://127.0.0.1:8080/".
 *
 * The command option `--address=localhost` can specify the network address both
 * with and without a port.  A port can also be specified with the network address,
 * eg `--address=localhost:8777`.
 *
 * if the machine "hosts" file maps a domain name to 127.0.0.1/localhost, then that
 * domain name can be specified with `--address=testdomain.com` and is accessible
 * on the machine web browser at "http://testdomain.com/".  In this example, testdomain.com
 * maps to 127.0.0.1 in the system's "hosts" file.
 *
 * The network address can be changed to IPv6 with the command line option `--ipv6`.
 * To serve pages on all network addresses, including any internet IP address, include
 * the option `--all`.  These options only work when there is no address specified.
 *
 * The command line option `--port=8777` can be used to change the port; in this example
 * to port 8777.  Ports 1023 and below are typically reserved for application and
 * system use and cannot be specified without administration access.
 *
 * To have more than one worker (to handle multiple requests), specify the `--workers=8`
 * command option (with the number of page workers need for you).  In this example,
 * eight concurrent workers are created.
 *
 * The TWebServerAction is only available when the application is in "Debug" mode.
 * In other Application modes, this action can be enabled with the Prado Application
 * Parameter "Prado:PhpWebServer" set to "true". eg. Within the Application configuration:
 *
 * ```xml
 * <parameters>
 *     <parameter id="Prado:PhpWebServer" value="true" />
 * </parameters>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TWebServerAction extends TShellAction
{
	public const DEV_WEBSERVER_ENV = 'PRADO_DEV_WEBSERVER';
	public const WORKERS_ENV = 'PHP_CLI_SERVER_WORKERS';

	public const DEV_WEBSERVER_PARAM = 'Prado:PhpWebServer';

	protected $action = 'http';
	protected $methods = ['serve'];
	protected $parameters = [[]];
	protected $optional = [['router-filepath']];
	protected $description = [
		'Provides a PHP Web Server to serve the application.',
		'Runs a PHP Web Server after Initializing the Application.'];

	/** @var bool Listen on all network addresses assigned to the computer, when one is not provided. */
	private bool $_all = false;

	/** @var ?string The specific address to listen on. */
	private ?string $_address = null;

	/** @var int The port to listen on, default 8080 */
	private int $_port = 8080;

	/** @var bool Use a direct ip v6 address, when one is not provided. */
	private bool $_ipv6 = false;

	/** @var int the number of workers for the Web Server */
	private int $_workers = 1;



	/**
	 * This option is only used when no network interface is specified.
	 * @return bool Respond on all network addresses.
	 */
	public function getAll(): bool
	{
		return $this->_all;
	}

	/**
	 * This option is only used when no network interface is specified.
	 * @param mixed $value
	 * @return bool Respond on all network addresses.
	 * @return static The current object.
	 */
	public function setAll($value): static
	{
		if (!$value) {
			$this->_all = true;
		} else {
			$this->_all = TPropertyValue::ensureBoolean($value);
		}
		return $this;
	}

	/**
	 * Gets the network address to serve pages from.  When no network address is specified
	 * then this will return the proper network address based upon {@see self::getIpv6()}
	 * and {@see self::getAll()}.
	 * @return string The network address to serve pages.
	 */
	public function getAddress(): string
	{
		if(!$this->_address) {
			if ($this->getIpv6()) {
				if ($this->getAll()) {
					return '[::0]';
				} else {
					return 'localhost';
				}
			} else {
				if ($this->getAll()) {
					return '0.0.0.0';
				} else {
					return '127.0.0.1';
				}
			}
		}
		return $this->_address;
	}

	/**
	 * @param ?string $address The network address to serve pages from.
	 * @return static The current object.
	 */
	public function setAddress($address): static
	{
		if ($address) {
			$address = TPropertyValue::ensureString($address);
			$port = null;

			if (($address[0] ?? '') === '[') {
				if ($pos = strrpos($address, ']')) {
					if ($pos = strrpos($address, ':', $pos)) {
						$port = substr($address, $pos + 1);
					}
				}
			} else {
				if (($pos = strrpos($address, ':')) !== false) {
					$port = substr($address, $pos + 1);
				}
			}

			if (is_numeric($port)) {
				$this->_port = (int) $port;
				$address = substr($address, 0, $pos);
			}
			$this->_address = $address;
		} else {
			$this->_address = null;
		}

		return $this;
	}

	/**
	 * @return int The port to serve pages.
	 */
	public function getPort(): int
	{
		return $this->_port;
	}

	/**
	 * @param null|int|string $address The port to serve pages, default 8080.
	 * @return static The current object.
	 */
	public function setPort($address): static
	{
		$this->_port = TPropertyValue::ensureInteger($address);

		return $this;
	}

	/**
	 * @return bool Use an IPv6 network address.
	 */
	public function getIpv6(): bool
	{
		return $this->_ipv6;
	}

	/**
	 * @param null|bool|string $ipv6
	 * @return static The current object.
	 */
	public function setIpv6($ipv6): static
	{
		if ($ipv6 === null || $ipv6 === '') {
			$ipv6 = true;
		}
		$this->_ipv6 = TPropertyValue::ensureBoolean($ipv6);

		return $this;
	}

	/**
	 * @return int The number of web server requests workers.
	 */
	public function getWorkers(): int
	{
		return $this->_workers;
	}

	/**
	 * @param null|int|string $value The number of web server requests workers.
	 * @return static The current object.
	 */
	public function setWorkers($value): static
	{
		$this->_workers = max(1, TPropertyValue::ensureInteger($value));

		return $this;
	}

	/**
	 * Properties for the action set by parameter.
	 * @param string $methodID the action being executed
	 * @return array properties for the $actionID
	 */
	public function options($methodID): array
	{
		if ($methodID === 'serve') {
			return ['address', 'port', 'workers', 'ipv6', 'all'];
		}
		return [];
	}

	/**
	 * Aliases for the properties to be set by parameter
	 * @return array<string, string> alias => property for the $actionID
	 */
	public function optionAliases(): array
	{
		return ['a' => 'address', 'p' => 'port', 'w' => 'workers', '6' => 'ipv6', 'i' => 'all'];
	}


	/**
	 * This runs the PHP Development Web Server.
	 * @param array $args parameters
	 * @return bool
	 */
	public function actionServe($args)
	{
		array_shift($args);

		$env = getenv();
		$env[static::DEV_WEBSERVER_ENV] = '1';

		if (($workers = $this->getWorkers()) > 1) {
			$env[static::WORKERS_ENV] = $workers;
		}

		$address = $this->getAddress();
		$port = $this->getPort();
		$documentRoot = dirname($_SERVER['SCRIPT_FILENAME']);

		if ($router = array_shift($args)) {
			if ($r = realpath($router)) {
				$router = $r;
			}
		}

		$app = Prado::getApplication();
		$quiet = ($app instanceof TShellApplication) ? $app->getQuietMode() : 0;

		$writer = $this->getWriter();

		if (!is_dir($documentRoot)) {
			if ($quiet !== 3) {
				$writer->writeError("Document root \"$documentRoot\" does not exist.");
				$writer->flush();
			}
			return true;
		}

		if ($this->isAddressTaken($address, $port)) {
			if ($quiet !== 3) {
				$writer->writeError("http://$address is taken by another process.");
				$writer->flush();
			}
			return true;
		}

		if ($router !== null && !file_exists($router)) {
			if ($quiet !== 3) {
				$writer->writeError("Routing file \"$router\" does not exist.");
				$writer->flush();
			}
			return true;
		}

		$nullFile = null;
		if ($quiet >= 2) {
			$nullFile = TProcessHelper::isSystemWindows() ? 'NUL' : '/dev/null';
			$descriptors = [STDIN, ['file', $nullFile, 'w'], ['file', $nullFile, 'w']];
		} else {
			$writer->writeline();
			$writer->write("Document root is \"{$documentRoot}\"\n");
			if ($router) {
				$writer->write("Routing file is \"$router\"\n");
			}
			$writer->writeline();
			$writer->write("To quit press CTRL-C or COMMAND-C.\n");
			$writer->flush();

			$descriptors = [STDIN, STDOUT, STDERR];
		}

		$command = $this->generateCommand($address . ':' . $port, $documentRoot, $router);
		$cwd = null;

		$process = proc_open($command, $descriptors, $pipes, $cwd, $env);
		proc_close($process);

		if ($nullFile) {
			fclose($descriptors[1]);
			fclose($descriptors[2]);
		}

		return true;
	}

	/**
	 * @param string $address The web server address and port.
	 * @param string $documentRoot The path of the application.
	 * @param ?string $router The router file
	 * @return array
	 */
	public function generateCommand(string $address, string $documentRoot, $router): array
	{
		return TProcessHelper::filterCommand(array_merge(['@php', '-S', $address, '-t', $documentRoot], $router ? [$router] : []));
	}


	/**
	 * This checks if a specific hostname and port are currently being used in the system
	 * @param string $hostname server address
	 * @param int $port server port
	 * @return bool if address is already in use
	 */
	protected function isAddressTaken($hostname, $port)
	{
		$fp = @fsockopen($hostname, $port, $errno, $errstr, 3);
		if ($fp === false) {
			return false;
		}
		fclose($fp);
		return true;
	}
}
