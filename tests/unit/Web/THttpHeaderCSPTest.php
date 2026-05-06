<?php

use Prado\Security\TSecurityManager;
use Prado\TApplication;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\THttpHeaderCSP;
use Prado\Web\THttpHeadersManager;

/**
 * Exposes the protected setNonce() method so tests can drive nonce state
 * without triggering the full init() / TSecurityManager path.
 */
class TTestHttpHeaderCSP extends THttpHeaderCSP
{
	public function publicSetNonce(?string $nonce): void
	{
		$this->setNonce($nonce);
	}
}

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

		// Each directive must end with "; "
		$value = $csp->getValue();
		self::assertStringContainsString(';', $value);
	}

	public function testGetValueWithEmptyPoliciesIsEmptyString()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init([]);
		self::assertEquals('', $csp->getValue());
	}

	// -----------------------------------------------------------------------
	// getNonce — no NONCE placeholder
	// -----------------------------------------------------------------------

	public function testGetNonceIsNullWhenNoPoliciesUsePlaceholder()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
		]]);

		self::assertNull($csp->getNonce());
	}

	public function testGetNonceIsNullBeforeInit()
	{
		$csp = new THttpHeaderCSP($this->manager);
		self::assertNull($csp->getNonce());
	}

	// -----------------------------------------------------------------------
	// setNonce (via subclass) — nonce state and TJavaScript side-effect
	// -----------------------------------------------------------------------

	public function testSetNonceSetsNonceProperty()
	{
		$csp = new TTestHttpHeaderCSP($this->manager);
		$csp->publicSetNonce('abc123');
		self::assertEquals('abc123', $csp->getNonce());
	}

	public function testSetNonceForwardedToTJavaScript()
	{
		$csp = new TTestHttpHeaderCSP($this->manager);
		$csp->publicSetNonce('myNonce');
		self::assertEquals('myNonce', TJavaScript::getScriptNonce());
	}

	public function testSetNonceNullClearsNonce()
	{
		$csp = new TTestHttpHeaderCSP($this->manager);
		$csp->publicSetNonce('abc');
		$csp->publicSetNonce(null);
		self::assertNull($csp->getNonce());
		self::assertNull(TJavaScript::getScriptNonce());
	}

	// -----------------------------------------------------------------------
	// getNonce / getValue — with NONCE placeholder
	// -----------------------------------------------------------------------

	public function testInitWithNoncePlaceholderSetsNonce()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self' NONCE"],
		]]);

		self::assertNotNull($csp->getNonce());
		self::assertIsString($csp->getNonce());
		self::assertNotEmpty($csp->getNonce());
	}

	public function testInitWithNoncePlaceholderRegistersNonceWithTJavaScript()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'script-src', 'value' => "'self' NONCE"],
		]]);

		self::assertEquals($csp->getNonce(), TJavaScript::getScriptNonce());
	}

	public function testGetValueReplacesNoncePlaceholderWithActualNonce()
	{
		$csp = new TTestHttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'script-src', 'value' => "'self' NONCE"],
		]]);
		$csp->publicSetNonce('testNonce999');

		$value = $csp->getValue();
		self::assertStringNotContainsString('NONCE', $value);
		self::assertStringContainsString("'nonce-testNonce999'", $value);
		self::assertStringContainsString("script-src 'self' 'nonce-testNonce999'", $value);
	}

	public function testGetValueDoesNotReplaceNonceWhenNonceIsNull()
	{
		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			// No NONCE placeholder → nonce stays null
			['name' => 'default-src', 'value' => "'self'"],
		]]);

		$value = $csp->getValue();
		self::assertStringNotContainsString("nonce-", $value);
	}

	public function testGetValueNoncePlaceholderReplacedInAllMatchingPolicies()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new TTestHttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'script-src', 'value' => "'self' NONCE"],
			['name' => 'style-src',  'value' => "'self' NONCE"],
		]]);
		$csp->publicSetNonce('myN0nce');

		$value = $csp->getValue();
		// Both directives must have the nonce replaced
		self::assertEquals(2, substr_count($value, "'nonce-myN0nce'"));
		self::assertStringNotContainsString(' NONCE', $value);
	}

	public function testInitWithNoncePlaceholderOnlyInOnePolicy()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new THttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'frame-src',  'value' => "'self'"],    // no NONCE
			['name' => 'script-src', 'value' => "'self' NONCE"], // has NONCE
		]]);

		// Should have generated a nonce because at least one policy has the placeholder
		self::assertNotNull($csp->getNonce());
	}

	// -----------------------------------------------------------------------
	// __toString (via THttpHeader)
	// -----------------------------------------------------------------------

	public function testToStringFormat()
	{
		$sm = new TSecurityManager();
		$sm->init(null);

		$csp = new TTestHttpHeaderCSP($this->manager);
		$csp->init(['policies' => [
			['name' => 'default-src', 'value' => "'self'"],
		]]);
		$csp->publicSetNonce(null); // ensure no nonce for predictable output

		$str = (string) $csp;
		self::assertStringStartsWith('Content-Security-Policy: ', $str);
		self::assertStringContainsString("default-src 'self'", $str);
	}
}
