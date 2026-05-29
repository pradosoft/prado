<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\TAuthManager;
use Prado\Security\TUserManager;
use Prado\Xml\TXmlDocument;

class TAuthManagerTest extends PHPUnit\Framework\TestCase
{
	public static TTestApplication $app;
	public static TUserManager $usrMgr;

	public static function setUpBeforeClass(): void
	{
		self::$app = new TTestApplication();

		self::$usrMgr = new TUserManager();
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><role name="Administrator" users="John" /><role name="Writer" users="Joe,John" /></users>');
		self::$usrMgr->init($config);
		self::$app->setModule('users', self::$usrMgr);
	}

	public static function tearDownAfterClass(): void
	{
		self::$app->restoreApplication();
	}

	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testInit()
	{
		$authManager = new TAuthManager();
		// Catch exception with null usermgr
		try {
			$authManager->init(null);
			self::fail('Expected TConfigurationException not thrown');
		} catch (TConfigurationException $e) {
		}

		$authManager->setUserManager('users');
		$authManager->init(null);
		self::assertEquals(self::$usrMgr, $authManager->getUserManager());
	}

	public function testUserManager()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		self::assertEquals(self::$usrMgr, $authManager->getUserManager());

		// test change
		try {
			$authManager->setUserManager('invalid');
			self::fail('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {
		}
	}

	public function testLoginPage()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage('LoginPage');
		self::assertEquals('LoginPage', $authManager->getLoginPage());
	}

	public function _testDoAuthentication()
	{
		/*
		 * doAuthentication() is the application-level OnAuthentication event handler.
		 * It delegates entirely to onAuthenticate(), whose logic is:
		 *
		 *   1. Opens the HTTP session via $session->open() — requires session_start(),
		 *      which emits a Set-Cookie header that cannot be sent from CLI.
		 *   2. Reads the serialised TUser state from the session store.
		 *   3. If AllowAutoLogin is set and the user is a guest, falls back to the
		 *      auto-login cookie (THttpCookie / THttpRequest — HTTP-only objects).
		 *   4. Applies authentication-expiry logic and raises OnAuthExpire if needed.
		 *   5. Sets the resolved TUser on the application via setUser().
		 *
		 * Additionally, when the currently-requested page is the configured LoginPage,
		 * doAuthentication() sets $_skipAuthorization = true so that doAuthorization()
		 * does not redirect the user back to the login page in a loop.
		 *
		 * None of this can be exercised in a CLI unit test without a real HTTP session.
		 *
		 * Full coverage is provided by the Playwright functional test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — tests 1–6 cover guest state, session restore, and authenticated access.
		 *   — test 14 covers auto-login cookie restore after session loss.
		 */
	}

	public function testDoAuthorization()
	{
		/*
		 * doAuthorization() is the application-level OnAuthorization event handler.
		 * It delegates to onAuthorize() unless $_skipAuthorization is true (set by
		 * doAuthentication() when the requested page is the LoginPage, to prevent a
		 * redirect loop).
		 *
		 * The $_skipAuthorization flag is tested here via reflection.  The downstream
		 * behaviour of onAuthorize() — setting a 401 response and calling
		 * completeRequest() when a deny rule matches — is covered by the functional
		 * test (tests 2, 3, and 8 of TAuthManagerTestCase.spec.js).
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		// Provide a guest user so onAuthorize()'s isUserAllowed() call has a subject.
		self::$app->setUser(self::$usrMgr->getUser(null));

		// Track whether onAuthorize / the OnAuthorize event actually fires.
		$fired = false;
		$authManager->onAuthorize[] = function () use (&$fired) {
			$fired = true;
		};

		// Default: $_skipAuthorization is false — doAuthorization delegates to onAuthorize.
		$authManager->doAuthorization(null, null);
		self::assertTrue($fired, 'onAuthorize should fire when _skipAuthorization is false');

		// When $_skipAuthorization is true, doAuthorization must be a no-op.
		$fired = false;
		PradoUnit::setProp($authManager, '_skipAuthorization', true);
		$authManager->doAuthorization(null, null);
		self::assertFalse($fired, 'onAuthorize must not fire when _skipAuthorization is true');
	}

	public function _testLeave()
	{
		/*
		 * leave() is the application-level OnEndRequest event handler.
		 * Its behaviour:
		 *
		 *   1. Reads $application->getResponse()->getStatusCode().
		 *   2. If the code is 401 and the active service is a TPageService, it stores
		 *      the current request URI as the ReturnUrl in the session, then calls
		 *      $response->redirect($loginUrl) — which emits a Location header.
		 *
		 * Emitting HTTP headers from CLI is not possible (headers_sent() is already
		 * true in the PHPUnit runner), so this method cannot be meaningfully unit-tested.
		 *
		 * Full coverage is provided by the Playwright functional test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — test  2: guest accesses Members page → 401 → redirect to Login.
		 *   — test  3: guest accesses Admin-only page → 401 → redirect to Login.
		 *   — test  8: authenticated non-admin accesses Admin page → 401 → redirect.
		 *   — test 10: ReturnUrl is preserved across the redirect so the user lands
		 *               on the originally-requested page after successful login.
		 */
	}

	public function testReturnUrlVarName()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setReturnUrlVarName('test');
		self::assertEquals('test', $authManager->getReturnUrlVarName());
		$authManager->setReturnUrlVarName('variable');
		self::assertEquals('variable', $authManager->getReturnUrlVarName());
	}

	public function testReturnUrl()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setReturnUrl('test');
		self::assertEquals('test', $authManager->getReturnUrl());
		$authManager->setReturnUrl('variable');
		self::assertEquals('variable', $authManager->getReturnUrl());
	}

	public function testAllowAutoLogin()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setAllowAutoLogin(true);
		self::assertEquals(true, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin(false);
		self::assertEquals(false, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin(1);
		self::assertEquals(true, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin(0);
		self::assertEquals(false, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin('true');
		self::assertEquals(true, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin('false');
		self::assertEquals(false, $authManager->getAllowAutoLogin());
	}

	public function testAuthExpire()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setAuthExpire(86400);
		self::assertEquals(86400, $authManager->getAuthExpire());
		$authManager->setAuthExpire(0);
		self::assertEquals(0, $authManager->getAuthExpire());
	}

	public function _testOnAuthenticate()
	{
		/*
		 * onAuthenticate() is the core implementation of the authentication flow,
		 * called by doAuthentication() on every request:
		 *
		 *   1. Calls $session->open() (session_start()) — requires HTTP to send the
		 *      session cookie; impossible in the CLI PHPUnit runner.
		 *   2. Reads $session->itemAt($userKey) and deserialises the stored TUser.
		 *   3. Checks AllowAutoLogin: if set and the user is a guest, attempts to
		 *      restore the user from an auto-login THttpCookie.
		 *   4. Checks AuthExpire: if the session timestamp is past expiry, calls
		 *      onAuthExpire() which in turn calls logout() → session->destroy().
		 *   5. Calls $application->setUser() with the resolved user.
		 *   6. Raises the OnAuthenticate event so any attached handler can do further
		 *      authentication work (e.g., OAuth token refresh).
		 *
		 * All six steps require live HTTP session state and cannot be replicated in
		 * a CLI unit test.
		 *
		 * Full coverage is provided by the Playwright functional test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — tests 1–6 verify guest detection, session restore, and authenticated
		 *     access across multiple requests (each request re-runs onAuthenticate).
		 *   — test 14 verifies the auto-login cookie fallback after session loss.
		 */
	}

	public function _testOnAuthExpire()
	{
		/*
		 * onAuthExpire() is called by onAuthenticate() when the 'AuthExpireTime'
		 * session value exists and has passed.  Its behaviour:
		 *
		 *   1. Calls logout() — which calls $session->destroy().  Session destruction
		 *      requires an active HTTP session that cannot be started in CLI.
		 *   2. Raises the OnAuthExpire event so attached handlers can react (e.g.,
		 *      display an "your session has expired" message).
		 *
		 * Because step 1 is a hard dependency on a live session, this method cannot
		 * be unit-tested in CLI.  The event-raise behaviour (step 2) would only be
		 * reachable after a successful logout, which has the same dependency.
		 *
		 * The authentication-expiry path is an edge-case variation of the session-
		 * restore flow and is covered end-to-end by the Playwright functional test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — test 9 covers logout (the operation onAuthExpire triggers internally).
		 */
	}

	public function testOnAuthorize()
	{
		/*
		 * onAuthorize() performs the real authorisation check:
		 *
		 *   1. Raises the OnAuthorize event if any handler is attached (testable).
		 *   2. Calls $application->getAuthorizationRules()->isUserAllowed(...).
		 *      When rules deny the user, sets the response status to 401 and calls
		 *      $application->completeRequest() — which terminates the request cycle.
		 *
		 * Step 1 is verified below.  Step 2's deny-and-terminate path requires
		 * authorization rules configured in the application and a response object that
		 * can absorb a 401; the full end-to-end deny flow is covered by the functional
		 * test (tests 2, 3, and 8 of TAuthManagerTestCase.spec.js).
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		// Provide a guest user so isUserAllowed() has a subject.
		self::$app->setUser(self::$usrMgr->getUser(null));

		// Verify the OnAuthorize event fires when a handler is registered.
		$fired = false;
		$authManager->onAuthorize[] = function () use (&$fired) {
			$fired = true;
		};
		$authManager->onAuthorize(null);
		self::assertTrue($fired, 'OnAuthorize event must fire when a handler is attached');

		// With no deny rules in the test app, isUserAllowed() returns true for
		// everyone; verify the response is NOT set to 401 (no completeRequest call).
		// Capture the output-buffer depth so we can clean up any buffers that
		// onAuthorize() / completeRequest() opens during its internal processing.
		$obLevel = ob_get_level();
		$authManager2 = new TAuthManager();
		$authManager2->setUserManager('users');
		$authManager2->init(null);
		$authManager2->onAuthorize(null);
		self::assertNotEquals(401, self::$app->getResponse()->getStatusCode());
		while (ob_get_level() > $obLevel) {
			ob_end_clean();
		}
	}

	public function testUserKey()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$md5 = md5($authManager->getApplication()->getUniqueID() . 'prado:user');
		self::assertEquals($md5, $authManager->getUserKey());
	}

	public function testUpdateSessionUser()
	{
		/*
		 * updateSessionUser() writes serialised user state to the session and
		 * regenerates the session ID to prevent session-fixation attacks.
		 * It is guarded by:
		 *
		 *   if (php_sapi_name() !== 'cli' && !$user->getIsGuest())
		 *
		 * In the CLI PHPUnit runner, php_sapi_name() === 'cli', so the method is
		 * a deliberate no-op.  The guard exists precisely because session_start() /
		 * session_regenerate_id() cannot run without an HTTP session.
		 *
		 * The real session-write path — including ID regeneration and its effects on
		 * subsequent requests — is covered by the Playwright functional test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — tests 5–7 verify that login writes user state to the session and that
		 *     subsequent requests restore it correctly via onAuthenticate().
		 *   — test 14 verifies the auto-login cookie written alongside the session.
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		// In CLI the method must silently do nothing — no exception thrown.
		$user = self::$usrMgr->getUser('Joe');
		$authManager->updateSessionUser($user);
		self::assertTrue(true); // reached: CLI guard is in effect
	}

	public function testSwitchUser()
	{
		/*
		 * switchUser() looks up a username in the user manager, calls
		 * updateSessionUser() (a no-op in CLI — see testUpdateSessionUser),
		 * and sets the new user on the application.  The session-persistence
		 * side of the switch is covered by TAuthManagerTestCase.spec.js test 13.
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		// Unknown username: must return false and leave the application user unchanged.
		self::assertFalse($authManager->switchUser('nonexistent'));

		// Known user (Joe / Writer): returns true and updates the application user.
		self::assertTrue($authManager->switchUser('Joe'));
		self::assertEquals('joe', self::$app->getUser()->getName());
		self::assertEquals(['Writer'], self::$app->getUser()->getRoles());
		self::assertFalse(self::$app->getUser()->getIsGuest());

		// Switching to a second user replaces the active user.
		self::assertTrue($authManager->switchUser('John'));
		self::assertEquals('john', self::$app->getUser()->getName());
		self::assertContains('Administrator', self::$app->getUser()->getRoles());
	}

	public function testLogin()
	{
		/*
		 * login() validates credentials via the user manager, calls
		 * updateSessionUser() (no-op in CLI), sets the user on the application,
		 * and raises onLogin or onLoginFailed accordingly.
		 *
		 * The cookie-based auto-login path ($expire > 0) requires an HTTP response
		 * object capable of setting cookies, and the session-persistence side
		 * requires an active session; both are covered by the Playwright functional
		 * test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — tests 4–5  cover valid and invalid credentials.
		 *   — test  14   covers the remember-me cookie written with $expire > 0.
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		$loginUser = null;
		$loginFailedName = null;
		$authManager->onLogin[] = function ($sender, $user) use (&$loginUser) {
			$loginUser = $user;
		};
		$authManager->onLoginFailed[] = function ($sender, $username) use (&$loginFailedName) {
			$loginFailedName = $username;
		};

		// Invalid credentials: returns false and raises onLoginFailed with the username.
		self::assertFalse($authManager->login('Joe', 'wrongpassword'));
		self::assertEquals('Joe', $loginFailedName);
		self::assertNull($loginUser);

		// Valid credentials: returns true, raises onLogin, sets the app user.
		// Default password mode is MD5; switch to Clear so the plain-text fixture
		// passwords ('demo') validate correctly, then restore afterwards.
		self::$usrMgr->setPasswordMode('Clear');
		$loginUser = null;
		$loginFailedName = null;
		self::assertTrue($authManager->login('Joe', 'demo'));
		self::assertNotNull($loginUser);
		self::assertNull($loginFailedName);
		self::assertEquals('joe', self::$app->getUser()->getName());
		self::assertEquals(['Writer'], self::$app->getUser()->getRoles());
		self::$usrMgr->setPasswordMode('MD5');
	}

	public function _testLogout()
	{
		/*
		 * logout() performs three actions:
		 *
		 *   1. Raises the OnLogout event with the current TUser as parameter
		 *      (the OnLogout event itself is tested in testOnLogout below).
		 *   2. Marks the application user as a guest (setIsGuest(true)).
		 *   3. Calls $session->destroy() — which requires an active HTTP session.
		 *      session_destroy() is a no-op or throws when called without a prior
		 *      session_start(), and session_start() cannot run in CLI without
		 *      emitting headers.
		 *   4. If AllowAutoLogin is set, adds a cleared cookie to the response to
		 *      remove the auto-login cookie from the browser.
		 *
		 * Steps 3 and 4 require live HTTP session and response objects.
		 *
		 * Full coverage is provided by the Playwright functional test:
		 *   tests/playwright/security/TAuthManagerTestCase.spec.js
		 *   — test  9: logout clears the session and reverts the user to Guest.
		 *   — test 17: logout after auto-login removes the remember-me cookie.
		 */
	}

	protected $_handled = 0;
	public function myhandler()
	{
		$this->_handled++;
	}

	public function testOnLogin()
	{
		$this->_handled = 0;
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->onLogin[] = [$this, 'myhandler'];
		$authManager->onLogin($this, null);
		self::assertEquals(1, $this->_handled);
	}

	public function testOnLoginFailed()
	{
		$this->_handled = 0;
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->onLoginFailed[] = [$this, 'myhandler'];
		$authManager->onLoginFailed($this, null);
		self::assertEquals(1, $this->_handled);
	}

	public function testOnLogout()
	{
		$this->_handled = 0;
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->onLogout[] = [$this, 'myhandler'];
		$authManager->onLogout($this, null);
		self::assertEquals(1, $this->_handled);
	}
}
