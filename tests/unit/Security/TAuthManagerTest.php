<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\TAuthManager;
use Prado\Security\TAuthorizationRule;
use Prado\Security\TUserManager;
use Prado\Util\TCallChain;
use Prado\Web\THttpCookie;
use Prado\Web\THttpResponse;
use Prado\Xml\TXmlDocument;

/**
 * Response double that records cookie operations in memory instead of emitting
 * `setcookie()` headers, which cannot run in the CLI test runner.
 */
class TTestAuthResponse extends THttpResponse
{
	/** @var list<string> URLs captured from httpRedirect() instead of emitting + exiting */
	public array $redirects = [];

	protected function responseSetCookie(string $name, ...$args): bool
	{
		return true;
	}

	public function httpRedirect($url)
	{
		$this->redirects[] = $url;
	}
}

/**
 * TAuthManager subclass that routes every session seam through an in-memory
 * session ({@see TTestMemorySession}) and reports the SAPI as session-persistent,
 * so the session-bound methods are exercisable in the CLI test runner.
 */
class TTestSessionAuthManager extends TAuthManager
{
	public TTestMemorySession $fakeSession;
	public bool $persist = true;

	public function __construct()
	{
		parent::__construct();
		$this->fakeSession = new TTestMemorySession();
	}

	protected function requireSession()
	{
		return $this->fakeSession;
	}

	protected function getCanPersistSession()
	{
		return $this->persist;
	}
}

/**
 * Page-service double reporting a fixed requested page path, so the
 * login-page branch of {@see TAuthManager::doAuthentication()} runs in CLI
 * without a real page request.
 */
class TTestLoginPageService extends \Prado\Web\Services\TPageService
{
	public string $requestedPath = '';
	public string $loginUrl = 'login-url';

	public function getRequestedPagePath()
	{
		return $this->requestedPath;
	}

	public function constructUrl($pagePath, $getParams = null, $encodeAmpersand = true, $encodeGetItems = true)
	{
		return $this->loginUrl;
	}
}

/**
 * TAuthManager whose session module resolves to null, exercising the
 * `requireSession()` guard.
 */
class TTestNoSessionAuthManager extends TAuthManager
{
	public function getSession()
	{
		return null;
	}
}

/**
 * Records invocations of the `dyHandleUnauthorized` seam raised by
 * {@see TAuthManager::leave()}.
 */
class TTestUnauthorizedSeamBehavior extends \Prado\Util\TBehavior
{
	public int $called = 0;
	public bool $handled = true;

	public function dyHandleUnauthorized($handled, ?TCallChain $chain = null)
	{
		$this->called++;
		$handled = $this->handled || $handled;
		return $chain ? $chain->dyHandleUnauthorized($handled) : $handled;
	}
}

/**
 * Records invocations of the `dySkipSessionUpdate` seam raised by
 * {@see TAuthManager::updateSessionUser()}.
 */
class TTestSkipSessionBehavior extends \Prado\Util\TBehavior
{
	public int $called = 0;
	public bool $skip = true;

	public function dySkipSessionUpdate($skip, $user, ?TCallChain $chain = null)
	{
		$this->called++;
		$skip = $this->skip || $skip;
		return $chain ? $chain->dySkipSessionUpdate($skip, $user) : $skip;
	}
}

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

	public function testInitRejectsInexistentUserManager()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('no-such-module');
		$caught = false;
		try {
			$authManager->init(null);
		} catch (TConfigurationException $e) {
			$caught = true;
		}
		self::assertTrue($caught, 'Expected TConfigurationException for an unresolved UserManager id');
	}

	public function testInitRejectsNonUserManagerModule()
	{
		// 'security' resolves to a TSecurityManager, which is not an IUserManager.
		$security = new \Prado\Security\TSecurityManager();
		$security->init(null);
		self::$app->setModule('not-a-usermgr', $security);

		$authManager = new TAuthManager();
		$authManager->setUserManager('not-a-usermgr');
		$caught = false;
		try {
			$authManager->init(null);
		} catch (TConfigurationException $e) {
			$caught = true;
		}
		self::assertTrue($caught, 'Expected TConfigurationException for a module that is not an IUserManager');
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

	public function testSetUserManagerRejectsInvalidType()
	{
		// Before init: a value that is neither a string id nor an IUserManager
		// is rejected by the type guard (not the assertUninitialized guard).
		$authManager = new TAuthManager();
		$caught = false;
		try {
			$authManager->setUserManager(123);
		} catch (TConfigurationException $e) {
			$caught = true;
		}
		self::assertTrue($caught, 'setUserManager must reject a non-string, non-IUserManager value');
	}

	public function testSetUserManagerAcceptsInstance()
	{
		// A concrete IUserManager instance is stored as-is and survives init()
		// without module-id resolution.
		$authManager = new TAuthManager();
		$authManager->setUserManager(self::$usrMgr);
		$authManager->init(null);
		self::assertSame(self::$usrMgr, $authManager->getUserManager());
	}

	public function testInitDefaultsReturnUrlVarName()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$expected = self::$app->getID() . ':' . TAuthManager::RETURN_URL_VAR;
		self::assertEquals($expected, $authManager->getReturnUrlVarName());
	}

	public function testRequireSessionThrowsWithoutSessionModule()
	{
		// getReturnUrl() routes through requireSession(); with no session module
		// the guard raises the configuration exception.
		$authManager = new TTestNoSessionAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$caught = false;
		try {
			$authManager->getReturnUrl();
		} catch (TConfigurationException $e) {
			$caught = true;
		}
		self::assertTrue($caught, 'requireSession() must throw when no session module is available');
	}

	public function testLoginPage()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage('LoginPage');
		self::assertEquals('LoginPage', $authManager->getLoginPage());
	}

	public function testDoAuthenticationSetsSkipAuthorizationOnLoginPage()
	{
		/*
		 * doAuthentication() delegates to onAuthenticate() and then, when the
		 * requested page is the configured LoginPage, sets $_skipAuthorization so
		 * doAuthorization() does not redirect the login page back to itself.
		 * The onAuthenticate() side runs through the in-memory session seam; a
		 * page-service double supplies the requested path.
		 */
		$auth = $this->makeSessionAuth();
		$auth->setLoginPage('Pages.Login');

		$savedService = self::$app->getService();
		$service = new TTestLoginPageService();
		self::$app->setService($service);
		try {
			// Requested page differs from LoginPage: authorization is not skipped.
			$service->requestedPath = 'Pages.Home';
			$auth->doAuthentication(null, null);
			self::assertFalse(PradoUnit::getProp($auth, '_skipAuthorization'));

			// Requested page IS the LoginPage: authorization is skipped.
			$service->requestedPath = 'Pages.Login';
			$auth->doAuthentication(null, null);
			self::assertTrue(PradoUnit::getProp($auth, '_skipAuthorization'));
		} finally {
			self::$app->setService($savedService);
			self::$usrMgr->setPasswordMode('MD5');
		}
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

	public function testLeaveRedirectsToLoginPageOn401()
	{
		/*
		 * leave() on a 401, with no behavior suppressing it and a TPageService
		 * active, stores the request URI as the ReturnUrl and redirects to the
		 * login page. A response double captures the redirect URL (instead of
		 * emitting a Location header and exiting) and a page-service double
		 * supplies the login URL.
		 */
		$obLevel = ob_get_level();
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage('Pages.Login');

		$savedResponse = self::$app->getResponse();
		$savedService = self::$app->getService();
		$response = new TTestAuthResponse();
		self::$app->setResponse($response);
		$service = new TTestLoginPageService();
		$service->loginUrl = '/login';
		self::$app->setService($service);
		try {
			$response->setStatusCode(401);
			$authManager->leave(null, null);

			self::assertSame(['/login'], $response->redirects);
			// The originally-requested URI is preserved for post-login return.
			self::assertEquals(self::$app->getRequest()->getRequestUri(), $authManager->getReturnUrl());
		} finally {
			self::$app->setService($savedService);
			self::$app->setResponse($savedResponse);
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	public function testLeaveDoesNotRedirectWithoutPageService()
	{
		// 401, not suppressed, but the active service is not a TPageService:
		// leave() does nothing (no redirect, no ReturnUrl write).
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		$savedResponse = self::$app->getResponse();
		$savedService = self::$app->getService();
		$response = new TTestAuthResponse();
		self::$app->setResponse($response);
		self::$app->setService(null);
		try {
			$response->setStatusCode(401);
			$authManager->leave(null, null);
			self::assertSame([], $response->redirects);
		} finally {
			self::$app->setService($savedService);
			self::$app->setResponse($savedResponse);
		}
	}

	public function testLeaveDyHandleUnauthorized()
	{
		/*
		 * leave() consults the dyHandleUnauthorized seam before redirecting a
		 * 401 response to the login page.  A behavior returning true (e.g.,
		 * THttpAuthBehavior after sending WWW-Authenticate challenge headers)
		 * suppresses the redirect.  The redirect itself requires a TPageService
		 * and is covered by the Playwright functional test.
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		$behavior = new TTestUnauthorizedSeamBehavior();
		$authManager->attachBehavior('unauthorized-seam', $behavior);

		// The lazily-created response opens an output buffer; capture the depth
		// so the buffers can be cleaned up at the end of the test.
		$obLevel = ob_get_level();

		// Non-401 response: the seam is not consulted.
		$authManager->leave(null, null);
		self::assertEquals(0, $behavior->called);

		// 401 response: the seam is consulted; true suppresses the redirect.
		self::$app->getResponse()->setStatusCode(401);
		$authManager->leave(null, null);
		self::assertEquals(1, $behavior->called);

		// Seam returning false falls through to the redirect path, which is a
		// no-op without a TPageService.
		$behavior->handled = false;
		$authManager->leave(null, null);
		self::assertEquals(2, $behavior->called);

		self::$app->getResponse()->setStatusCode(200);
		while (ob_get_level() > $obLevel) {
			ob_end_clean();
		}
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

	// onAuthenticate() and onAuthExpire() are exercised through the in-memory
	// session seam in the "session-seam-backed paths" section below
	// (testOnAuthenticate*, testOnAuthExpireLogsOutAndRaisesEvent).

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

	public function testOnAuthorizeDenyPath()
	{
		/*
		 * The deny branch of onAuthorize(): when the authorization rules reject
		 * the current user, the response status is set to 401 and the request
		 * is flagged complete.  Exercised here by injecting a deny-everyone rule
		 * into the application's rule collection.
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);

		self::$app->setUser(self::$usrMgr->getUser(null));
		PradoUnit::setProp(self::$app, '_requestCompleted', false);
		self::$app->getResponse()->setStatusCode(200);

		$rules = self::$app->getAuthorizationRules();
		$rules->add(new TAuthorizationRule('deny', '*'));

		$obLevel = ob_get_level();
		try {
			$authManager->onAuthorize(null);
			self::assertEquals(401, self::$app->getResponse()->getStatusCode());
			self::assertTrue(self::$app->getRequestCompleted());
		} finally {
			$rules->clear();
			self::$app->getResponse()->setStatusCode(200);
			PradoUnit::setProp(self::$app, '_requestCompleted', false);
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	public function testLoginWritesRememberMeCookie()
	{
		/*
		 * login() with $expire > 0 writes a remember-me cookie to the response:
		 * a THttpCookie keyed by getUserKey(), carrying the serialized auth token
		 * from the user manager.  The session-write side is a CLI no-op (see
		 * testUpdateSessionUser); the cookie side runs fully in CLI.
		 */
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setAllowAutoLogin(true);

		// Swap in a fresh response so the added cookie does not leak into the
		// shared application response; restore it afterward.
		$savedResponse = self::$app->getResponse();
		$response = new TTestAuthResponse();
		self::$app->setResponse($response);

		self::$usrMgr->setPasswordMode('Clear');
		try {
			self::assertTrue($authManager->login('Joe', 'demo', 3600));

			$cookie = $response->getCookies()->itemAt($authManager->getUserKey());
			self::assertInstanceOf(THttpCookie::class, $cookie);
			self::assertNotEquals('', $cookie->getValue());
			self::assertGreaterThan(time(), $cookie->getExpire());
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
			self::$app->setResponse($savedResponse);
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
		 * The dySkipSessionUpdate seam (consulted after the CLI guard, before the
		 * session write) is covered through the session seam in
		 * testUpdateSessionUserConsultsDySkipSessionUpdate, which forces a
		 * persistable SAPI so the seam is reachable.
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

	// logout() is exercised through the in-memory session seam below
	// (testLogoutDestroysSession, testLogoutClearsAutoLoginCookie).

	// ---------------------------------------------------------------- session-seam-backed paths
	//
	// These exercise the session-bound methods through TTestSessionAuthManager,
	// whose overridable session seams route to an in-memory session, so the
	// flows previously deferred to Playwright run in the CLI runner.

	protected function makeSessionAuth(): TTestSessionAuthManager
	{
		$auth = new TTestSessionAuthManager();
		$auth->setUserManager('users');
		$auth->init(null);
		self::$usrMgr->setPasswordMode('Clear');
		return $auth;
	}

	public function testOnAuthenticateGuestWhenNoSessionState()
	{
		$auth = $this->makeSessionAuth();
		try {
			$auth->setAuthExpire(3600);
			$auth->onAuthenticate(null);

			self::assertTrue($auth->fakeSession->opened, 'onAuthenticate must open the session');
			self::assertTrue(self::$app->getUser()->getIsGuest());
			// A guest's expiration time is still stamped for the next request.
			self::assertGreaterThan(time(), $auth->fakeSession->data[TAuthManager::AUTH_EXPIRE_TIME]);
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testOnAuthenticateRestoresUserFromSession()
	{
		$auth = $this->makeSessionAuth();
		try {
			// Pre-store a serialized non-guest user under the user key.
			$auth->fakeSession->data[$auth->getUserKey()] = self::$usrMgr->getUser('Joe')->saveToString();

			$captured = null;
			$auth->onAuthenticate[] = function ($sender, $param) use (&$captured) {
				$captured = [$sender, $param];
			};
			$auth->onAuthenticate('the-param');

			self::assertEquals('joe', self::$app->getUser()->getName());
			self::assertFalse(self::$app->getUser()->getIsGuest());
			// The OnAuthenticate event receives the manager as sender and the
			// pass-through param (not the application).
			self::assertSame($auth, $captured[0]);
			self::assertSame('the-param', $captured[1]);
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testOnAuthenticateExpiresStaleAuth()
	{
		$auth = $this->makeSessionAuth();
		try {
			$auth->setAuthExpire(3600);
			$auth->fakeSession->data[$auth->getUserKey()] = self::$usrMgr->getUser('Joe')->saveToString();
			$auth->fakeSession->data[TAuthManager::AUTH_EXPIRE_TIME] = time() - 100;

			$expired = false;
			$auth->onAuthExpire[] = function () use (&$expired) {
				$expired = true;
			};
			$auth->onAuthenticate(null);

			// Expiry triggers onAuthExpire → logout: session destroyed, user a guest.
			self::assertTrue($expired, 'OnAuthExpire must fire for stale authentication');
			self::assertTrue($auth->fakeSession->destroyed);
			self::assertTrue(self::$app->getUser()->getIsGuest());
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testUpdateSessionUserWritePath()
	{
		$auth = $this->makeSessionAuth();
		try {
			$joe = self::$usrMgr->getUser('Joe');
			$auth->updateSessionUser($joe);

			self::assertEquals($joe->saveToString(), $auth->fakeSession->data[$auth->getUserKey()]);
			self::assertTrue($auth->fakeSession->regenerated, 'session id must be regenerated to prevent fixation');
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testUpdateSessionUserSkipsGuest()
	{
		$auth = $this->makeSessionAuth();
		try {
			$auth->updateSessionUser(self::$usrMgr->getUser(null));
			self::assertArrayNotHasKey($auth->getUserKey(), $auth->fakeSession->data);
			self::assertFalse($auth->fakeSession->regenerated);
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testUpdateSessionUserSkippedWhenNotPersistable()
	{
		$auth = $this->makeSessionAuth();
		try {
			$auth->persist = false;	// simulate CLI / non-persistent SAPI
			$auth->updateSessionUser(self::$usrMgr->getUser('Joe'));
			self::assertArrayNotHasKey($auth->getUserKey(), $auth->fakeSession->data);
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testUpdateSessionUserConsultsDySkipSessionUpdate()
	{
		/*
		 * updateSessionUser() consults the dySkipSessionUpdate seam before the
		 * session write: an attached behavior returning true suppresses both the
		 * write and the id regeneration; returning false lets the write proceed.
		 */
		$auth = $this->makeSessionAuth();
		$behavior = new TTestSkipSessionBehavior();
		$auth->attachBehavior('skip-seam', $behavior);
		try {
			$joe = self::$usrMgr->getUser('Joe');

			// Behavior returns true → the seam is consulted and the write is skipped.
			$behavior->skip = true;
			$auth->updateSessionUser($joe);
			self::assertEquals(1, $behavior->called);
			self::assertArrayNotHasKey($auth->getUserKey(), $auth->fakeSession->data);
			self::assertFalse($auth->fakeSession->regenerated);

			// Behavior returns false → the write proceeds.
			$behavior->skip = false;
			$auth->updateSessionUser($joe);
			self::assertEquals($joe->saveToString(), $auth->fakeSession->data[$auth->getUserKey()]);
			self::assertTrue($auth->fakeSession->regenerated);
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testLogoutDestroysSession()
	{
		$auth = $this->makeSessionAuth();
		try {
			self::$app->setUser(self::$usrMgr->getUser('Joe'));
			$auth->fakeSession->data[$auth->getUserKey()] = 'stored';

			$auth->logout();

			self::assertTrue($auth->fakeSession->destroyed);
			self::assertTrue(self::$app->getUser()->getIsGuest());
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testLogoutClearsAutoLoginCookie()
	{
		$auth = $this->makeSessionAuth();
		$savedResponse = self::$app->getResponse();
		$response = new TTestAuthResponse();
		self::$app->setResponse($response);
		try {
			$auth->setAllowAutoLogin(true);
			self::$app->setUser(self::$usrMgr->getUser('Joe'));

			$auth->logout();

			// A cleared cookie under the user key is queued to erase the
			// remember-me cookie from the browser.
			$cookie = $response->getCookies()->itemAt($auth->getUserKey());
			self::assertInstanceOf(THttpCookie::class, $cookie);
			self::assertEquals('', $cookie->getValue());
		} finally {
			self::$app->setResponse($savedResponse);
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testOnAuthExpireLogsOutAndRaisesEvent()
	{
		$auth = $this->makeSessionAuth();
		try {
			self::$app->setUser(self::$usrMgr->getUser('Joe'));
			$auth->fakeSession->data[$auth->getUserKey()] = 'stored';

			$fired = false;
			$auth->onAuthExpire[] = function ($sender, $param) use (&$fired) {
				$fired = true;
			};
			$auth->onAuthExpire(null);

			// onAuthExpire logs the user out, then raises the OnAuthExpire event.
			self::assertTrue($auth->fakeSession->destroyed);
			self::assertTrue(self::$app->getUser()->getIsGuest());
			self::assertTrue($fired, 'OnAuthExpire event must fire');
		} finally {
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testOnAuthenticateRestoresFromAutoLoginCookie()
	{
		$auth = $this->makeSessionAuth();
		$savedResponse = self::$app->getResponse();
		self::$app->setResponse(new TTestAuthResponse());
		try {
			$auth->setAllowAutoLogin(true);

			// Build a valid auth cookie for Joe and place it in the request under
			// the user key; the session is empty, so the cookie path runs.
			self::$app->setUser(self::$usrMgr->getUser('Joe'));
			$authCookie = new THttpCookie($auth->getUserKey(), '');
			self::$usrMgr->saveUserToCookie($authCookie);
			self::$app->getRequest()->getCookies()->add($authCookie);
			self::$app->setUser(self::$usrMgr->getUser(null));

			$auth->onAuthenticate(null);

			self::assertEquals('joe', self::$app->getUser()->getName());
			self::assertFalse(self::$app->getUser()->getIsGuest());
			// Cookie restore writes the user back to the session.
			self::assertArrayHasKey($auth->getUserKey(), $auth->fakeSession->data);
		} finally {
			self::$app->getRequest()->getCookies()->clear();
			self::$app->setResponse($savedResponse);
			self::$usrMgr->setPasswordMode('MD5');
		}
	}

	public function testOnAuthenticateIgnoresInvalidAutoLoginCookie()
	{
		$auth = $this->makeSessionAuth();
		$savedResponse = self::$app->getResponse();
		self::$app->setResponse(new TTestAuthResponse());
		try {
			$auth->setAllowAutoLogin(true);

			// A well-formed [username, token] cookie whose token does not match
			// the computed auth token: getUserFromCookie() returns null and the
			// user stays a guest.
			$badCookie = new THttpCookie($auth->getUserKey(), serialize(['joe', 'wrong-token']));
			self::$app->getRequest()->getCookies()->add($badCookie);
			self::$app->setUser(self::$usrMgr->getUser(null));

			$auth->onAuthenticate(null);

			self::assertTrue(self::$app->getUser()->getIsGuest());
			self::assertArrayNotHasKey($auth->getUserKey(), $auth->fakeSession->data);
		} finally {
			self::$app->getRequest()->getCookies()->clear();
			self::$app->setResponse($savedResponse);
			self::$usrMgr->setPasswordMode('MD5');
		}
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
