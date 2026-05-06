<?php

use Prado\Security\TSecurityManager;
use Prado\TApplication;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\THttpHeaderCSP;
use Prado\Web\THttpHeadersManager;

/** Minimal manager stub — needed by the THttpHeader constructor. */
class TTestCSPManagerStub extends THttpHeadersManager {}

class THttpHeaderCSPTest extends PHPUnit\Framework\TestCase
{
	public static ?TApplication $app = null;

	private TTestCSPManagerStub $manager;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../Security/app');
		}
		$this->manager = new TTestCSPManagerStub();
		TJavaScript::setScriptNonce(null);
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testNonceConstantValue()
	{
		self::assertEquals('NONCE', THttpHeaderCSP::NONCE);
	}

	// -----------------------------------------------------------------------
	// getName
	// -----------------------------------------------------------------------

	public function testGetNameReturnsContentSecurityPolicy()
	{
		$csp = new THttpHeaderCSP($this->manager);
		self::assertEquals('Content-Security-Policy', $csp->getName());
	}

	// -----------------------------------------------------------------------
	// loadPolicies / getValue — without nonce
	// -----------------------------------------------------------------------

	public function testGetValueWithSinglePolicy()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
		]]);

		self::assertStringContainsString("default-src 'self'", $csp->getValue());
	}

	public function testGetValueWithMultiplePolicies()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
			['name' => 'frame-src',   'value' => "'self' www.google.com"],
		]]);

		$value = $csp->getValue();
		self::assertStringContainsString("default-src 'self'", $value);
		self::assertStringContainsString("frame-src 'self' www.google.com", $value);
	}

	public function testGetValuePoliciesAreSemicolonSeparated()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
			['name' => 'script-src',  'value' => "'self'"],
		]]);

		self::assertStringContainsString(';', $csp->getValue());
	}

	public function testGetValueWithEmptyPoliciesIsEmptyString()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init([]);
		self::assertEquals('', $csp->getValue());
	}

	// -----------------------------------------------------------------------
	// getValue — with nonce
	// -----------------------------------------------------------------------

	public function testGetValueReplacesNoncePlaceholderWithActualNonce()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'script-src', 'value' => "'self' NONCE"],
		]]);

		// init() set the real CSP nonce; override with a known value so the
		// assertion is deterministic — getValue() reads TJavaScript at call time.
		TJavaScript::setScriptNonce('testNonce999');

		$value = $csp->getValue();
		self::assertStringNotContainsString('NONCE', $value);
		self::assertStringContainsString("'nonce-testNonce999'", $value);
		self::assertStringContainsString("script-src 'self' 'nonce-testNonce999'", $value);
	}

	public function testGetValueDoesNotReplaceNonceWhenNonceIsNull()
	{
		// TJavaScript nonce is null (set in setUp); no replacement should occur.
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
		]]);

		self::assertStringNotContainsString('nonce-', $csp->getValue());
	}

	public function testGetValueNoncePlaceholderReplacedInAllMatchingPolicies()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'script-src', 'value' => "'self' NONCE"],
			['name' => 'style-src',  'value' => "'self' NONCE"],
		]]);

		// Override with a known value after init() has set the real CSP nonce.
		TJavaScript::setScriptNonce('myN0nce');

		$value = $csp->getValue();
		// Both directives must have the placeholder replaced.
		self::assertEquals(2, substr_count($value, "'nonce-myN0nce'"));
		self::assertStringNotContainsString(' NONCE', $value);
	}

	// -----------------------------------------------------------------------
	// init — NONCE placeholder triggers TJavaScript::setScriptNonce()
	// -----------------------------------------------------------------------

	public function testInitWithNoncePlaceholderRegistersNonceWithTJavaScript()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'script-src', 'value' => "'self' NONCE"],
		]]);

		// init() must have called TJavaScript::setScriptNonce() with a real value.
		self::assertNotNull(TJavaScript::getScriptNonce());
		self::assertIsString(TJavaScript::getScriptNonce());
		self::assertNotEmpty(TJavaScript::getScriptNonce());
	}

	public function testInitWithoutNoncePlaceholderDoesNotSetScriptNonce()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
		]]);

		// No NONCE placeholder — TJavaScript nonce must remain null.
		self::assertNull(TJavaScript::getScriptNonce());
	}

	public function testInitWithNoncePlaceholderOnlyInOnePolicy()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'frame-src',  'value' => "'self'"],       // no NONCE
			['name' => 'script-src', 'value' => "'self' NONCE"], // has NONCE
		]]);

		// At least one policy has NONCE — the nonce must have been set.
		self::assertNotNull(TJavaScript::getScriptNonce());
	}

	// -----------------------------------------------------------------------
	// __toString (via THttpHeader)
	// -----------------------------------------------------------------------

	public function testToStringFormat()
	{
		// No nonce so the output is deterministic.
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
		]]);

		$str = (string) $csp;
		self::assertStringStartsWith('Content-Security-Policy: ', $str);
		self::assertStringContainsString("default-src 'self'", $str);
	}
}
