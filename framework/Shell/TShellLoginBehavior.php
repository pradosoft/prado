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
use Prado\Prado;

/**
 * TShellLoginBehavior class.
 *
 * TShellLoginBehavior implements user login with password into the application.
 * It forces shell command to have an application user before executing any
 * actions.  TShellLoginBehavior is attached to TAuthManager.
 *
 * For example in a TBehaviorsModule where the TAuthManager module id is 'auth':
 * <code>
 *	 <behavior name="shellLoginAuth" Class="Prado\Shell\TShellLoginBehavior" AttachTo="module:auth" />
 * </code>
 * or, attach to all TAuthManager
 * <code>
 *	 <behavior name="shellLoginAuth" Class="Prado\Shell\TShellLoginBehavior" AttachToClass="Prado\Security\TAuthManager" />
 * </code>
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
		if (($app = Prado::getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->registerOption('user', [$this, 'setUsername'], 'Application login User name', '=<user>');
			$app->registerOptionAlias('u', 'user');
			$app->registerOption('password', [$this, 'setPassword'], 'Application login Password', '=<password>');
			$app->registerOptionAlias('p', 'password');
		}
	}

	/**
	 * utility function to read a password without displaying text while reading.
	 * @param bool $hide should the texing being read be hidden, default true
	 * @return string the user entered text
	 */
	protected function readPassword($hide = true)
	{
		$s = ($hide) ? '-s' : '';
		$f = popen("read $s; echo \$REPLY", "r");
		$input = fgets($f, 512);
		pclose($f);
		if ($hide) {
			print "\n";
		}
		return trim($input, "\n\r");
	}

	/**
	 * This authenticates a Shell user for using shell commands
	 * @param object $sender
	 * @param mixed $param
	 */
	public function shellApplicationLogin($sender, $param)
	{
		$app = Prado::getApplication();
		if (php_sapi_name() !== 'cli' || !($app instanceof \Prado\Shell\TShellApplication) || !$this->getEnabled()) {
			return;
		}
		$writer = $app->getWriter();

		if (!$this->_username || !$this->_password) {
			$writer->writeLine('  -- Login --', [TShellWriter::BOLD]);
			$writer->write("Login Username: ");
			if (!$this->_username) {
				$writer->flush();
				$this->_username = $line = trim(fgets(STDIN));
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
	 * @param string $password password of the login from the command line
	 */
	public function setPassword(#[\SensitiveParameter] $password)
	{
		$this->_password = $password;
	}
}
