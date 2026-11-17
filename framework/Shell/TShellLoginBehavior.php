<?php

/**
 * TShellLoginBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell;

use Prado\Exceptions\TExitException;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TShellLoginBehavior class.
 *
 * TShellLoginBehavior implements user login with password into the application.
 * It forces shell command to have an application user before executing any
 * actions.  TShellLoginBehavior is attached to TAuthManager.
 *
 * For example in a TBehaviorsModule where the TAuthManager module id is 'auth':
 * ```php
 *	 <behavior name="shellLoginAuth" Class="Prado\Shell\TShellLoginBehavior" AttachTo="module:auth" />
 * ```
 * or, attach to all TAuthManager
 * ```php
 *	 <behavior name="shellLoginAuth" Class="Prado\Shell\TShellLoginBehavior" AttachToClass="Prado\Security\TAuthManager" />
 * ```
 *
 * The `--user` (`-u`) and `--password` (`-p`) options give the credentials; a missing one is
 * prompted for, the password without echo on a terminal.  A `--password` on the command
 * line is visible to other users in the process list and stays in the shell history, so
 * the prompt is the safer way to enter it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method \Prado\Security\TAuthManager getOwner()
 */
class TShellLoginBehavior extends \Prado\Util\TBehavior
{
	/** @var string user name for login */
	private $_username = '';

	private $_password = '';

	/**
	 * @return array<string, string> array of owner 'events' => behavior 'methods'
	 */
	public function events()
	{
		return ['OnAuthenticate' => 'shellApplicationLogin'];
	}

	/**
	 * @param \Prado\TComponent $owner the class this behavior is being attached tos
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->registerOption('user', [$this, 'setUsername'], 'Application login User name', '=<user>');
			$app->registerOptionAlias('u', 'user');
			$app->registerOption('password', [$this, 'setPassword'], 'Application login Password', '=<password>');
			$app->registerOptionAlias('p', 'password');
		}
	}

	/**
	 * Reads a line from standard input.
	 * @return string the line without its line ending, or an empty string at the end of input.
	 * @since 4.4.0
	 * @codeCoverageIgnore Reads the process standard input.
	 */
	protected function readLine(): string
	{
		return rtrim((string) fgets(STDIN), "\r\n");
	}

	/**
	 * Reads a password from standard input with {@see readLine()}.  With `$hide`, terminal echo
	 * is off while reading when {@see setTerminalEcho()} can turn it off, and a newline is
	 * printed after the input, which the terminal did not echo.
	 * @param bool $hide whether the text being read is hidden, default true
	 * @return string the user entered text
	 */
	protected function readPassword($hide = true)
	{
		$hidden = $hide && $this->setTerminalEcho(false);
		try {
			return $this->readLine();
		} finally {
			if ($hidden) {
				$this->setTerminalEcho(true);
				print "\n";
			}
		}
	}

	/**
	 * Turns terminal echo on or off with `stty` when standard input is a terminal outside Windows.
	 * @param bool $echo whether the terminal echoes input
	 * @return bool whether `stty` changed the echo.
	 * @since 4.4.0
	 */
	protected function setTerminalEcho(bool $echo): bool
	{
		if (TProcessHelper::isSystemWindows() || !$this->isStdInTerminal()) {
			return false;
		}
		exec('stty ' . ($echo ? 'echo' : '-echo') . ' 2>/dev/null', $output, $code);
		return $code === 0;
	}

	/**
	 * Returns whether the process standard input is a terminal.
	 * @return bool whether standard input is a terminal.
	 * @since 4.4.0
	 */
	protected function isStdInTerminal(): bool
	{
		return defined('STDIN') && @stream_isatty(STDIN);
	}

	/**
	 * This authenticates a Shell user for using shell commands
	 * @param object $sender
	 * @param mixed $param
	 */
	public function shellApplicationLogin($sender, $param)
	{
		$app = $this->getApplication();
		if (php_sapi_name() !== 'cli' || !($app instanceof \Prado\Shell\TShellApplication) || !$this->getEnabled()) {
			return;
		}
		$writer = $app->getWriter();

		if (!$this->_username || !$this->_password) {
			$writer->writeLine('  -- Login --', [TShellWriter::BOLD]);
			$writer->write("Login Username: ");
			if (!$this->_username) {
				$writer->flush();
				$this->_username = trim($this->readLine());
			} else {
				$writer->writeLine($this->_username);
			}
			$writer->write("Password: ");
			if (!$this->_password) {
				$writer->flush();
				$this->_password = $this->readPassword();
			} else {
				$writer->writeLine("**********");
			}
		}
		$password = $this->_password;
		$this->_password = '';	//Do not store the Password.
		if (!$this->getOwner()->login($this->_username, $password)) {
			$writer->writeError("Could not Authenticate the user");
			$writer->flush();
			throw new TExitException(0);
		}
	}

	/**
	 * @return string username of the login from the command line
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * @param string $name username of the login from the command line
	 */
	public function setUsername($name)
	{
		$this->_username = $name;
	}

	/**
	 * Sets the password, as the `--password` option does.  A password on the command line is
	 * visible in the process list and the shell history; omit the option to be prompted.
	 * @param string $password password of the login from the command line
	 */
	public function setPassword(#[\SensitiveParameter] $password)
	{
		$this->_password = $password;
	}
}
