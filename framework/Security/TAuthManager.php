<?php

/**
 * TAuthManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;
use Prado\Web\Services\TPageService;
use Prado\Web\THttpCookie;

/**
 * TAuthManager class
 *
 * TAuthManager performs user authentication and authorization for a Prado application.
 * TAuthManager works together with a {@see \Prado\Security\IUserManager} module that can be
 * specified via the {@see setUserManager UserManager} property.
 * If an authorization fails, TAuthManager will try to redirect the client
 * browser to a login page that is specified via the {@see setLoginPage LoginPage}.
 * To login or logout a user, call {@see login} or {@see logout}, respectively.
 *
 * The {@see setAuthExpire AuthExpire} property can be used to define the time
 * in seconds after which the authentication should expire.
 * {@see setAllowAutoLogin AllowAutoLogin} specifies if the login information
 * should be stored in a cookie to perform automatic login. Enabling this
 * feature will cause that {@see setAuthExpire AuthExpire} has no effect
 * since the user will be logged in again on authentication expiration.
 *
 * Attached behaviors may intercept the following dynamic (`dy-`) events:
 * - `dyHandleUnauthorized(bool $handled): bool` — Called in {@see leave()} when
 *   the response status is 401. Return `true` to suppress the login-page
 *   redirect (e.g., a behavior that has already sent `WWW-Authenticate` headers).
 * - `dySkipSessionUpdate(bool $skip, IUser $user): bool` — Called in
 *   {@see updateSessionUser()} before writing the user to the session. Return
 *   `true` to skip the session write (e.g., a stateless HTTP-auth behavior).
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="users" class="Prado\Security\TUserManager" PasswordMode="MD5" />
 *   <module id="auth" class="Prado\Security\TAuthManager"
 *       UserManager="users" LoginPage="Site.Pages.Login"
 *       AllowAutoLogin="false" AuthExpire="0" />
 * </modules>
 * ```
 * where {@see setUserManager UserManager} refers to the ID of an
 * {@see \Prado\Security\IUserManager} module and {@see setLoginPage LoginPage}
 * is the dot-delimited page path to redirect on authorization failure.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'users' => [
 *             'class' => 'Prado\Security\TUserManager',
 *             'properties' => [
 *                 'PasswordMode' => 'MD5',
 *             ],
 *         ],
 *         'auth' => [
 *             'class' => 'Prado\Security\TAuthManager',
 *             'properties' => [
 *                 'UserManager' => 'users',
 *                 'LoginPage' => 'Site.Pages.Login',
 *                 'AllowAutoLogin' => 'false',
 *                 'AuthExpire' => '0',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * When a user logs in, onLogin event is raised with the TUser as the parameter.
 * If the user trying to login but fails the check, onLoginFailed is raised with the
 * user name as parameter.  When the user logs out, onLogout is raised with the TUser
 * as parameter.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method bool dyHandleUnauthorized(bool $handled) Behaviors return `true` to suppress the login-page redirect when the response is 401.
 * @method bool dySkipSessionUpdate(bool $skip, IUser $user) Behaviors return `true` to skip writing the user to the session.
 */
class TAuthManager extends \Prado\TModule
{
	use TInitializedTrait;

	/**
	 * GET variable name for return url
	 */
	public const RETURN_URL_VAR = 'ReturnUrl';
	/**
	 * Session key under which the authentication expiration timestamp is stored.
	 * @since 4.4.0
	 */
	public const AUTH_EXPIRE_TIME = 'AuthExpireTime';
	/**
	 * @var IUserManager user manager instance
	 */
	private $_userManager;
	/**
	 * @var string login page
	 */
	private $_loginPage;
	/**
	 * @var bool whether authorization should be skipped
	 */
	private $_skipAuthorization = false;
	/**
	 * @var string the session var name for storing return URL
	 */
	private $_returnUrlVarName;
	/**
	 * @var bool whether to allow auto login (using cookie)
	 */
	private $_allowAutoLogin = false;
	/**
	 * @var string variable name used to store user session or cookie
	 */
	private $_userKey;
	/**
	 * @var int authentication expiration time in seconds. Defaults to zero (no expiration)
	 */
	private $_authExpire = 0;

	// =========================================================================
	// Initialization
	// =========================================================================

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if user manager does not exist or is not IUserManager
	 */
	public function init($config)
	{
		if ($this->getUserManager() === null) {
			throw new TConfigurationException('authmanager_usermanager_required');
		}
		if ($this->getReturnUrlVarName() === null) {
			$this->setReturnUrlVarName($this->getApplication()->getID() . ':' . self::RETURN_URL_VAR);
		}
		$application = $this->getApplication();
		if (is_string($this->getUserManager())) {
			if (($users = $application->getModule($this->getUserManager())) === null) {
				throw new TConfigurationException('authmanager_usermanager_inexistent', $this->getUserManager());
			}
			if (!($users instanceof IUserManager)) {
				throw new TConfigurationException('authmanager_usermanager_invalid', $this->getUserManager());
			}
			$this->_userManager = $users;
		}
		$application->attachEventHandler('OnAuthentication', [$this, 'doAuthentication']);
		$application->attachEventHandler('OnEndRequest', [$this, 'leave']);
		$application->attachEventHandler('OnAuthorization', [$this, 'doAuthorization']);
		parent::init($config);
		$this->markInitialized();
	}

	// =========================================================================
	// Property Getters and Setters
	// =========================================================================

	/**
	 * @return IUserManager user manager instance
	 */
	public function getUserManager()
	{
		return $this->_userManager;
	}

	/**
	 * @param IUserManager|string $provider the user manager module ID or the user manager object
	 * @throws TInvalidOperationException if the module has been initialized or the user manager object is not IUserManager
	 */
	public function setUserManager($provider)
	{
		$this->assertUninitialized('UserManager');
		if (!is_string($provider) && !($provider instanceof IUserManager)) {
			throw new TConfigurationException('authmanager_usermanager_invalid', $provider);
		}
		$this->_userManager = $provider;
	}

	/**
	 * @return string path of login page should login is required
	 */
	public function getLoginPage()
	{
		return $this->_loginPage;
	}

	/**
	 * Sets the login page that the client browser will be redirected to if login is needed.
	 * Login page should be specified in the format of page path.
	 * @param string $pagePath path of login page should login is required
	 * @see TPageService
	 */
	public function setLoginPage($pagePath)
	{
		$this->_loginPage = $pagePath;
	}

	/**
	 * @return string the name of the session variable storing return URL. It defaults to 'AppID:ReturnUrl'
	 */
	public function getReturnUrlVarName()
	{
		return $this->_returnUrlVarName;
	}

	/**
	 * @param string $value the name of the session variable storing return URL.
	 */
	public function setReturnUrlVarName($value)
	{
		$this->_returnUrlVarName = $value;
	}

	/**
	 * @return string URL that the browser should be redirected to when login succeeds.
	 */
	public function getReturnUrl()
	{
		return $this->requireSession()->itemAt($this->getReturnUrlVarName());
	}

	/**
	 * Sets the URL that the browser should be redirected to when login succeeds.
	 * @param string $value the URL to be redirected to.
	 */
	public function setReturnUrl($value)
	{
		$this->requireSession()->add($this->getReturnUrlVarName(), $value);
	}

	/**
	 * @return bool whether to allow remembering login so that the user logs on automatically next time. Defaults to false.
	 * @since 3.1.1
	 */
	public function getAllowAutoLogin()
	{
		return $this->_allowAutoLogin;
	}

	/**
	 * @param bool $value whether to allow remembering login so that the user logs on automatically next time. Users have to enable cookie to make use of this feature.
	 * @since 3.1.1
	 */
	public function setAllowAutoLogin($value)
	{
		$this->_allowAutoLogin = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int authentication expiration time in seconds. Defaults to zero (no expiration).
	 * @since 3.1.3
	 */
	public function getAuthExpire()
	{
		return $this->_authExpire;
	}

	/**
	 * @param int $value authentication expiration time in seconds. Defaults to zero (no expiration).
	 * @since 3.1.3
	 */
	public function setAuthExpire($value)
	{
		$this->_authExpire = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string a unique variable name for storing user session/cookie data
	 * @since 3.1.1
	 */
	public function getUserKey()
	{
		if ($this->_userKey === null) {
			$this->_userKey = $this->generateUserKey();
		}
		return $this->_userKey;
	}

	/**
	 * @return string a key used to store user information in session
	 * @since 3.1.1
	 */
	protected function generateUserKey()
	{
		return md5($this->getApplication()->getUniqueID() . 'prado:user');
	}

	/**
	 * @return bool whether authorization is skipped for the current request, set
	 *   by {@see doAuthentication()} when the requested page is the login page.
	 * @since 4.4.0
	 */
	protected function getSkipAuthorization(): bool
	{
		return $this->_skipAuthorization;
	}

	/**
	 * @param bool $value whether to skip authorization for the current request.
	 * @since 4.4.0
	 */
	protected function setSkipAuthorization(bool $value)
	{
		$this->_skipAuthorization = $value;
	}

	// =========================================================================
	// Application Lifecycle Event Handlers
	// =========================================================================

	/**
	 * Performs authentication.
	 * This is the event handler attached to application's Authentication event.
	 * Do not call this method directly.
	 * @param mixed $sender sender of the Authentication event
	 * @param mixed $param event parameter
	 */
	public function doAuthentication($sender, $param)
	{
		$this->onAuthenticate($param);

		$service = $this->getService();
		if (($service instanceof TPageService) && $service->getRequestedPagePath() === $this->getLoginPage()) {
			$this->setSkipAuthorization(true);
		}
	}

	/**
	 * Performs authorization.
	 * This is the event handler attached to application's Authorization event.
	 * Do not call this method directly.
	 * @param mixed $sender sender of the Authorization event
	 * @param mixed $param event parameter
	 */
	public function doAuthorization($sender, $param)
	{
		if (!$this->getSkipAuthorization()) {
			$this->onAuthorize($param);
		}
	}

	/**
	 * Performs login redirect if authorization fails.
	 * This is the event handler attached to application's EndRequest event.
	 * Do not call this method directly.
	 *
	 * Attached behaviors may suppress the redirect by returning `true` from
	 * their `dyHandleUnauthorized` handler (e.g., when a behavior has already
	 * sent RFC 7235 `WWW-Authenticate` challenge headers and the 401 should
	 * stand as-is rather than becoming a login-page redirect).
	 * @param mixed $sender sender of the event
	 * @param mixed $param event parameter
	 */
	public function leave($sender, $param)
	{
		$application = $this->getApplication();
		if ($application->getResponse()->getStatusCode() === 401) {
			if ($this->dyHandleUnauthorized(false)) {
				return;
			}
			$service = $application->getService();
			if ($service instanceof TPageService) {
				$returnUrl = $application->getRequest()->getRequestUri();
				$this->setReturnUrl($returnUrl);
				$url = $service->constructUrl($this->getLoginPage());
				$application->getResponse()->redirect($url);
			}
		}
	}

	// =========================================================================
	// Authentication and Authorization
	// =========================================================================

	/**
	 * Performs the real authentication work.
	 * User information is restored from session data, falling back to the
	 * auto-login cookie when {@see getAllowAutoLogin AllowAutoLogin} is enabled
	 * and the session yields a guest or expired user. An OnAuthenticate event
	 * is raised afterward if any handler is attached to it.
	 * @param mixed $param parameter to be passed to OnAuthenticate event
	 * @throws TConfigurationException if session module does not exist.
	 */
	public function onAuthenticate($param)
	{
		$application = $this->getApplication();

		// restoring user info from session
		$this->openSession();
		$sessionInfo = $this->loadUserState();
		$user = $this->getUserManager()->getUser(null)->loadFromString($sessionInfo);

		// check for authentication expiration
		$isAuthExpired = $this->getAuthExpire() > 0 && !$user->getIsGuest() &&
		($expiretime = $this->getAuthExpireTime()) && $expiretime < time();

		// try authenticating through cookie if possible
		if ($this->getAllowAutoLogin() && ($user->getIsGuest() || $isAuthExpired)) {
			$cookie = $this->getRequest()->getCookies()->itemAt($this->getUserKey());
			if ($cookie instanceof THttpCookie) {
				if (($user2 = $this->getUserManager()->getUserFromCookie($cookie)) !== null) {
					$user = $user2;
					$this->updateSessionUser($user);
					// user is restored from cookie, auth may not expire
					$isAuthExpired = false;
				}
			}
		}

		$application->setUser($user);

		// handle authentication expiration or update expiration time
		if ($isAuthExpired) {
			$this->onAuthExpire($param);
		} else {
			$this->setAuthExpireTime(time() + $this->getAuthExpire());
		}

		// event handler gets a chance to do further auth work
		if ($this->hasEventHandler('OnAuthenticate')) {
			$this->raiseEvent('OnAuthenticate', $this, $param);
		}
	}

	/**
	 * Performs user logout on authentication expiration.
	 * An 'OnAuthExpire' event will be raised if there is any handler attached to it.
	 * @param mixed $param parameter to be passed to OnAuthExpire event.
	 */
	public function onAuthExpire($param)
	{
		$this->logout();
		if ($this->hasEventHandler('OnAuthExpire')) {
			$this->raiseEvent('OnAuthExpire', $this, $param);
		}
	}

	/**
	 * Performs the real authorization work.
	 * Authorization rules obtained from the application will be used to check
	 * if a user is allowed. If authorization fails, the response status code
	 * will be set as 401 and the application terminates.
	 * @param mixed $param parameter to be passed to OnAuthorize event
	 */
	public function onAuthorize($param)
	{
		$application = $this->getApplication();
		if ($this->hasEventHandler('OnAuthorize')) {
			$this->raiseEvent('OnAuthorize', $this, $application);
		}
		if (!$application->getAuthorizationRules()->isUserAllowed($application->getUser(), $application->getRequest()->getRequestType(), $application->getRequest()->getUserHostAddress())) {
			$application->getResponse()->setStatusCode(401);
			$application->completeRequest();
		}
	}

	/**
	 * Updates the user data stored in session.
	 *
	 * Attached behaviors may suppress the write by returning `true` from their
	 * `dySkipSessionUpdate` handler. Stateless HTTP-auth behaviors use this to
	 * avoid opening and writing the session on every request.
	 * @param IUser $user user object
	 * @throws TConfigurationException if session module is not loaded.
	 */
	public function updateSessionUser($user)
	{
		if ($this->getCanPersistSession() && !$user->getIsGuest()) {
			if ($this->dySkipSessionUpdate(false, $user)) {
				return;
			}
			$this->saveUserState($user);
		}
	}

	/**
	 * Switches to a new user.
	 * This method will logout the current user first and login with a new one (without password.)
	 * @param string $username the new username
	 * @return bool if the switch is successful
	 */
	public function switchUser($username)
	{
		if (($user = $this->getUserManager()->getUser($username)) === null) {
			return false;
		}
		$this->updateSessionUser($user);
		$this->getApplication()->setUser($user);
		return true;
	}

	/**
	 * Logs in a user with username and password.
	 * The username and password will be used to validate if login is successful.
	 * If yes, a user object will be created for the application.
	 * On successful Login, onLogin is raised with the TUser as parameter.
	 * When the login fails, onLoginFailed is raised with the username as parameter.
	 * @param string $username username
	 * @param string $password password
	 * @param int $expire number of seconds that automatic login will remain effective. If 0, it means user logs out when session ends. This parameter is added since 3.1.1.
	 * @return bool if login is successful
	 */
	public function login($username, #[\SensitiveParameter] $password, $expire = 0)
	{
		if ($this->getUserManager()->validateUser($username, $password)) {
			if (($user = $this->getUserManager()->getUser($username)) === null) {
				return false;
			}
			$this->updateSessionUser($user);
			$this->getApplication()->setUser($user);

			if ($expire > 0) {
				$cookie = new THttpCookie($this->getUserKey(), '');
				$cookie->setExpire(time() + $expire);
				$this->getUserManager()->saveUserToCookie($cookie);
				$this->getResponse()->getCookies()->add($cookie);
			}
			$this->onLogin($user);
			return true;
		} else {
			$this->onLoginFailed($username);
			return false;
		}
	}

	/**
	 * Logs out a user.  Raises onLogout with the TUser as parameter
	 * before logging out. User session will be destroyed after this
	 * method is called.
	 * @throws TConfigurationException if session module is not loaded.
	 */
	public function logout()
	{
		$this->onLogout($this->getApplication()->getUser());
		$this->requireSession();
		$this->getApplication()->getUser()->setIsGuest(true);
		$this->destroySession();
		if ($this->getAllowAutoLogin()) {
			$cookie = new THttpCookie($this->getUserKey(), '');
			$this->getResponse()->getCookies()->add($cookie);
		}
	}

	// =========================================================================
	// Session Encapsulation
	// =========================================================================

	/**
	 * Returns the session module, throwing when none is configured.
	 * Every session operation in this class routes through this accessor so the
	 * session interaction is encapsulated behind one overridable seam.
	 * @throws TConfigurationException if no session module is available.
	 * @return \Prado\Web\THttpSession the application session module.
	 * @since 4.4.0
	 */
	protected function requireSession()
	{
		if (($session = $this->getSession()) === null) {
			throw new TConfigurationException('authmanager_session_required');
		}
		return $session;
	}

	/**
	 * Opens the session for reading and writing.
	 * @throws TConfigurationException if no session module is available.
	 * @since 4.4.0
	 */
	protected function openSession()
	{
		$this->requireSession()->open();
	}

	/**
	 * Reads the serialized user state stored under {@see getUserKey()}.
	 * @return mixed the stored user-state string, or null when absent.
	 * @since 4.4.0
	 */
	protected function loadUserState()
	{
		return $this->requireSession()->itemAt($this->getUserKey());
	}

	/**
	 * Writes the serialized user state under {@see getUserKey()} and regenerates
	 * the session id to prevent session fixation.
	 * @param IUser $user the user whose state is stored.
	 * @since 4.4.0
	 */
	protected function saveUserState($user)
	{
		$session = $this->requireSession();
		$session->add($this->getUserKey(), $user->saveToString());
		$session->regenerate(true);
	}

	/**
	 * @return mixed the stored authentication expiration timestamp, or null.
	 * @since 4.4.0
	 */
	protected function getAuthExpireTime()
	{
		return $this->requireSession()->itemAt(static::AUTH_EXPIRE_TIME);
	}

	/**
	 * Stores the authentication expiration timestamp in the session.
	 * @param int $time the Unix timestamp at which authentication expires.
	 * @since 4.4.0
	 */
	protected function setAuthExpireTime($time)
	{
		$this->requireSession()->add(static::AUTH_EXPIRE_TIME, $time);
	}

	/**
	 * Destroys the session, ending the authenticated session.
	 * @throws TConfigurationException if no session module is available.
	 * @since 4.4.0
	 */
	protected function destroySession()
	{
		$this->requireSession()->destroy();
	}

	/**
	 * Whether the current SAPI persists sessions across requests. Returns false
	 * under CLI, where {@see updateSessionUser()} skips the session write because
	 * `session_start()` / `session_regenerate_id()` cannot run.
	 * @return bool whether session writes are meaningful in this SAPI.
	 * @since 4.4.0
	 */
	protected function getCanPersistSession()
	{
		return php_sapi_name() !== 'cli';
	}

	// =========================================================================
	// Events
	// =========================================================================

	/**
	 * onLogin event is raised when a user logs in
	 * @param TUser $user user being logged in
	 * @since 4.2.0
	 */
	public function onLogin($user)
	{
		$this->raiseEvent('onLogin', $this, $user);
	}

	/**
	 * onLoginFailed event is raised when a user login fails
	 * @param string $username username trying to log in
	 * @since 4.2.0
	 */
	public function onLoginFailed($username)
	{
		$this->raiseEvent('onLoginFailed', $this, $username);
	}

	/**
	 * onLogout event is raised when a user logs out.
	 * @param TUser $user user being logged out
	 * @since 4.2.0
	 */
	public function onLogout($user)
	{
		$this->raiseEvent('onLogout', $this, $user);
	}
}
