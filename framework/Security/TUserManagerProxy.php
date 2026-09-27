<?php

/**
 * TUserManagerProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\TModule;
use Prado\Util\Log\TLogger;

/**
 * TUserManagerProxy class.
 *
 * TUserManagerProxy is a transparent user manager module that delegates every
 * {@see IUserManager} operation to another user manager module registered with
 * the application. {@see TAuthManager::setUserManager UserManager} names one
 * module ID, so the proxy lets the configuration swap a {@see TUserManager} for a
 * {@see TDbUserManager}, or any other {@see IUserManager}, without changing the
 * authentication configuration.
 *
 * ## Configuration
 *
 * {@see getBackingUserManagerId BackingUserManagerId} names the backing user
 * manager module. TUserManagerProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * ## Transparency
 *
 * The {@see IUserManager} methods call the backing's methods. The proxy extends
 * {@see TModule} rather than a concrete user manager, so every other property,
 * method, and event of the backing, such as `PasswordMode` or `UserFile` of a
 * {@see TUserManager}, reaches it through {@see TComponentProxyTrait}, which
 * wires the backing's public `on` events on first resolution.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="auth" class="Prado\Security\TAuthManager" UserManager="users" />
 * <module id="users" class="Prado\Security\TUserManagerProxy" BackingUserManagerId="dbUsers" />
 * <module id="dbUsers" class="Prado\Security\TDbUserManager" UserClass="Application.Users.DbUser" ConnectionID="db" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUserManagerProxy extends TModule implements IUserManager, IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing user manager; empty until configured */
	private string $_backingUserManagerId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing user manager module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingUserManagerId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingUserManagerId BackingUserManagerId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingUserManagerId() === '') {
			throw new TConfigurationException('usermanagerproxy_backing_user_manager_id_required');
		}
		parent::init($config);
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing user manager through {@see getUserManager()}.
	 * @throws TConfigurationException when {@see getBackingUserManagerId BackingUserManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not an {@see IUserManager}
	 * @return ?TComponent the backing user manager
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getUserManager();
	}

	/**
	 * Returns whether a {@see getBackingUserManagerId BackingUserManagerId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingUserManagerId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing user manager
	 */
	protected function getBackingUserManagerIdDirect(): string
	{
		return $this->_backingUserManagerId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingUserManagerIdDirect(string $value): void
	{
		$this->_backingUserManagerId = $value;
	}

	/**
	 * @return string the module ID of the backing user manager
	 */
	public function getBackingUserManagerId(): string
	{
		return $this->getBackingUserManagerIdDirect();
	}

	/**
	 * Sets the module ID of the backing user manager. Replacing a non-empty ID
	 * detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops the
	 * resolved backing so the next operation resolves the new module.
	 * @param string $value the module ID of the user manager to proxy
	 */
	public function setBackingUserManagerId(string $value): void
	{
		$current = $this->getBackingUserManagerIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TUserManagerProxy.BackingUserManagerId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.security'
			);
		}
		$this->setBackingUserManagerIdDirect($value);
		$this->setUserManagerDirect(null);
	}

	/**
	 * Returns the stored backing user manager, narrowing the trait's
	 * `?TComponent` to a `TComponent` that is an {@see IUserManager}.
	 * @return null|(IUserManager&TComponent) the backing user manager, or null when not yet resolved
	 */
	protected function getUserManagerDirect(): ?TComponent
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof IUserManager ? $backing : null;
	}

	/**
	 * @param null|(IUserManager&TComponent) $value the backing user manager to store
	 */
	protected function setUserManagerDirect(?TComponent $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing {@see IUserManager}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingUserManagerId BackingUserManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not an {@see IUserManager}
	 * @return IUserManager&TComponent the backing user manager module
	 */
	public function getUserManager(): IUserManager
	{
		$manager = $this->getUserManagerDirect();
		if ($manager === null) {
			$id = $this->getBackingUserManagerId();
			if ($id === '') {
				throw new TConfigurationException('usermanagerproxy_backing_user_manager_id_required');
			}
			$manager = $this->getApplication()->getModule($id);
			if ($manager === null) {
				throw new TConfigurationException('usermanagerproxy_user_manager_not_found', $id);
			}
			if (!($manager instanceof IUserManager)) {
				throw new TConfigurationException('usermanagerproxy_invalid_user_manager_type', $id);
			}
			$this->setUserManagerDirect($manager);
			$this->attachProxy();
		}
		return $manager;
	}

	// ----------------------------------------------------------------- IUserManager

	/**
	 * @return string the guest name of the backing user manager
	 */
	public function getGuestName()
	{
		return $this->getUserManager()->getGuestName();
	}

	/**
	 * Returns a user from the backing user manager.
	 * @param ?string $username user name, null for a guest user
	 * @return ?IUser the user, or null when the user does not exist
	 */
	public function getUser($username = null)
	{
		return $this->getUserManager()->getUser($username);
	}

	/**
	 * Returns a user from the authentication data of a cookie through the backing user manager.
	 * @param \Prado\Web\THttpCookie $cookie the cookie storing the authentication data
	 * @return ?IUser the user, or null when the cookie holds no valid authentication data
	 */
	public function getUserFromCookie($cookie)
	{
		return $this->getUserManager()->getUserFromCookie($cookie);
	}

	/**
	 * Saves the current user's authentication data into a cookie through the backing user manager.
	 * @param \Prado\Web\THttpCookie $cookie the cookie to receive the authentication data
	 */
	public function saveUserToCookie($cookie)
	{
		$this->getUserManager()->saveUserToCookie($cookie);
	}

	/**
	 * Validates credentials through the backing user manager.
	 * @param string $username user name
	 * @param string $password password
	 * @return bool whether the credentials are valid
	 */
	public function validateUser($username, #[\SensitiveParameter] $password)
	{
		return $this->getUserManager()->validateUser($username, $password);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing (re-resolved
	 * from the module registry after unserialization), and an empty backing ID
	 * from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingUserManagerIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingUserManagerId";
		}
	}
}
