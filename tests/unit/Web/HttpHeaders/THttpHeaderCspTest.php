<?php

/**
 * THttpHeaderCspTest
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints;
use Prado\Web\HttpHeaders\THttpHeadersManager;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\THttpHeaderName;

/**
 * Unit tests for {@see THttpHeaderCsp}.
 *
 * Does not exercise the full send pipeline — that belongs to
 * {@see THttpHeaderCspIntegrationTest}. Covers every public method of the
 * class directly: policy CRUD, header name/value rendering, NONCE
 * substitution, `setHeaderValue()` parsing, and the two lifecycle hooks.
 */
class THttpHeaderCspTest extends PHPUnit\Framework\TestCase
{
	private THttpHeaderCsp $csp;

	protected function setUp(): void
	{
		$this->csp = new THttpHeaderCsp();
		TJavaScript::setScriptNonce(null);
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	// =========================================================================
	// NONCE constant
	// =========================================================================

	public function testNonceConstantValue(): void
	{
		self::assertSame('NONCE', THttpHeaderCsp::NONCE);
	}

	// =========================================================================
	// REPORT_URI constant — own value and inheritance from THttpHeaderBase
	// =========================================================================

	public function testReportUriConstantValue(): void
	{
		self::assertSame('REPORT_URI', THttpHeaderCsp::REPORT_URI);
	}

	public function testReportUriConstantIsInheritedFromBase(): void
	{
		// THttpHeaderCsp::REPORT_URI resolves via the inheritance chain to
		// THttpHeaderBase::REPORT_URI; the values must be identical.
		self::assertSame(
			\Prado\Web\HttpHeaders\THttpHeaderBase::REPORT_URI,
			THttpHeaderCsp::REPORT_URI,
			'THttpHeaderCsp::REPORT_URI must be the constant inherited from THttpHeaderBase'
		);
	}

	// =========================================================================
	// getHeaderName / ReportOnly
	// =========================================================================

	public function testGetHeaderNameDefaultIsContentSecurityPolicy(): void
	{
		self::assertSame(THttpHeaderName::ContentSecurityPolicy, $this->csp->getHeaderName());
	}

	public function testGetHeaderNameReportOnlyIsContentSecurityPolicyReportOnly(): void
	{
		$this->csp->setReportOnly(true);
		self::assertSame(THttpHeaderName::ContentSecurityPolicyReportOnly, $this->csp->getHeaderName());
	}

	public function testGetHeaderNameRevertsToCspWhenReportOnlySetFalse(): void
	{
		$this->csp->setReportOnly(true);
		$this->csp->setReportOnly(false);
		self::assertSame(THttpHeaderName::ContentSecurityPolicy, $this->csp->getHeaderName());
	}

	// =========================================================================
	// getReportOnly / setReportOnly
	// =========================================================================

	public function testGetReportOnlyDefaultFalse(): void
	{
		self::assertFalse($this->csp->getReportOnly());
	}

	public function testSetReportOnlyTrue(): void
	{
		$this->csp->setReportOnly(true);
		self::assertTrue($this->csp->getReportOnly());
	}

	public function testSetReportOnlyStringTrue(): void
	{
		$this->csp->setReportOnly('true');
		self::assertTrue($this->csp->getReportOnly());
	}

	public function testSetReportOnlyStringFalse(): void
	{
		$this->csp->setReportOnly(true);
		$this->csp->setReportOnly('false');
		self::assertFalse($this->csp->getReportOnly());
	}

	public function testSetReportOnlyStringOne(): void
	{
		$this->csp->setReportOnly('1');
		self::assertTrue($this->csp->getReportOnly());
	}

	// =========================================================================
	// getReplace — CSP headers are always non-replacing
	// =========================================================================

	public function testGetReplaceReturnsFalseForEnforcingCsp(): void
	{
		self::assertFalse($this->csp->getReplace());
	}

	public function testGetReplaceReturnsFalseForReportOnlyCsp(): void
	{
		$this->csp->setReportOnly(true);
		self::assertFalse($this->csp->getReplace());
	}

	// =========================================================================
	// hasPolicies
	// =========================================================================

	public function testHasPoliciesReturnsFalseByDefault(): void
	{
		self::assertFalse($this->csp->hasPolicies());
	}

	public function testHasPoliciesReturnsTrueAfterAddPolicy(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($this->csp->hasPolicies());
	}

	public function testHasPoliciesReturnsFalseAfterAllPoliciesRemoved(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->removePolicy(TCspDirective::DefaultSrc);
		self::assertFalse($this->csp->hasPolicies());
	}

	public function testHasPoliciesReturnsFalseWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		self::assertFalse($this->csp->hasPolicies());
	}

	// =========================================================================
	// getPolicies — default and after modification
	// =========================================================================

	public function testGetPoliciesDefaultIsEmptyArray(): void
	{
		self::assertSame([], $this->csp->getPolicies());
	}

	public function testGetPoliciesReturnsMapAfterAddPolicy(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame([TCspDirective::DefaultSrc => "'self'"], $this->csp->getPolicies());
	}

	public function testGetPoliciesReturnsRawStringAfterUnparseableSetHeaderValue(): void
	{
		$this->csp->setHeaderValue('$#@!');
		self::assertIsString($this->csp->getPolicies());
	}

	// =========================================================================
	// setPolicies
	// =========================================================================

	public function testSetPoliciesReplacesEntireMap(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->setPolicies([TCspDirective::ScriptSrc => "'none'"]);
		self::assertSame([TCspDirective::ScriptSrc => "'none'"], $this->csp->getPolicies());
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc),
			'The previous directive must be gone after setPolicies()');
	}

	public function testSetPoliciesAcceptsRawString(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->setPolicies("default-src 'none'");
		self::assertIsString($this->csp->getPolicies());
	}

	public function testSetPoliciesEmptyArrayClearsAllDirectives(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->setPolicies([]);
		self::assertSame([], $this->csp->getPolicies());
		self::assertFalse($this->csp->hasPolicies());
	}

	public function testSetPoliciesArrayPathTrimsNameAndValue(): void
	{
		$this->csp->setPolicies([' default-src ' => " 'self' "]);
		self::assertSame("'self'", $this->csp->getPolicy(TCspDirective::DefaultSrc),
			'setPolicies() array path must trim directive names and values via setPolicy()');
	}

	public function testSetPoliciesArrayPathNormalizesBlankReportUri(): void
	{
		// A blank report-uri value in the array input must be normalized to
		// REPORT_URI — the same normalization setPolicy() applies for all call paths.
		$this->csp->setPolicies([TCspDirective::ReportUri => '']);
		self::assertSame(THttpHeaderCsp::REPORT_URI, $this->csp->getPolicy(TCspDirective::ReportUri),
			'setPolicies() array path must normalize blank report-uri to REPORT_URI sentinel');
		self::assertTrue($this->csp->hasReportUriPlaceholder());
	}

	// =========================================================================
	// hasPolicy
	// =========================================================================

	public function testHasPolicyReturnsFalseByDefault(): void
	{
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyReturnsTrueAfterAddPolicy(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyReturnsFalseForUnaddedDirective(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertFalse($this->csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	public function testHasPolicyReturnsFalseAfterRemovePolicy(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->removePolicy(TCspDirective::DefaultSrc);
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyReturnsFalseWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyTrimsName(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($this->csp->hasPolicy(' default-src '));
	}

	public function testHasPolicyCaseInsensitive(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($this->csp->hasPolicy('DEFAULT-SRC'),
			'CSP directive names are case-insensitive per the CSP3 specification');
	}

	// =========================================================================
	// addPolicy
	// =========================================================================

	public function testAddPolicyAddsNewDirective(): void
	{
		$this->csp->addPolicy(TCspDirective::ScriptSrc, "'self'");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	public function testAddPolicyReplacesExistingDirective(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'none'");
		$policies = $this->csp->getPolicies();
		self::assertSame("'none'", $policies[TCspDirective::DefaultSrc]);
	}

	public function testAddPolicyIsNoOpWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		// Must still be a raw string; addPolicy is a no-op.
		self::assertIsString($this->csp->getPolicies());
	}

	public function testAddPolicyStoresEmptyValueForBareDirective(): void
	{
		// $value defaults to '' — bare directives need no second argument.
		$this->csp->addPolicy(TCspDirective::UpgradeInsecureRequests);
		self::assertTrue($this->csp->hasPolicy(TCspDirective::UpgradeInsecureRequests));
		$policies = $this->csp->getPolicies();
		self::assertSame('', $policies[TCspDirective::UpgradeInsecureRequests]);
	}

	public function testAddPolicyDefaultValueEqualsExplicitEmptyString(): void
	{
		$a = new THttpHeaderCsp();
		$b = new THttpHeaderCsp();
		$a->addPolicy(TCspDirective::UpgradeInsecureRequests);
		$b->addPolicy(TCspDirective::UpgradeInsecureRequests, '');
		self::assertSame($a->getPolicies(), $b->getPolicies(),
			'Omitting $value must produce the same state as passing an explicit empty string');
	}

	// =========================================================================
	// setPolicy
	// =========================================================================

	public function testSetPolicyAddsNewDirective(): void
	{
		$this->csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame("'self'", $this->csp->getPolicy(TCspDirective::DefaultSrc));
	}

	public function testSetPolicyReplacesExistingDirective(): void
	{
		$this->csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
		self::assertSame("'none'", $this->csp->getPolicy(TCspDirective::DefaultSrc));
	}

	public function testSetPolicyIsNoOpWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		$this->csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertIsString($this->csp->getPolicies(),
			'setPolicy must be a no-op when the backing field holds a raw string');
	}

	public function testSetPolicyStoresEmptyValueForBareDirective(): void
	{
		// $value defaults to '' — bare directives need no second argument.
		$this->csp->setPolicy(TCspDirective::UpgradeInsecureRequests);
		self::assertTrue($this->csp->hasPolicy(TCspDirective::UpgradeInsecureRequests));
		$policies = $this->csp->getPolicies();
		self::assertSame('', $policies[TCspDirective::UpgradeInsecureRequests]);
	}

	public function testSetPolicyDefaultValueEqualsExplicitEmptyString(): void
	{
		$a = new THttpHeaderCsp();
		$b = new THttpHeaderCsp();
		$a->setPolicy(TCspDirective::UpgradeInsecureRequests);
		$b->setPolicy(TCspDirective::UpgradeInsecureRequests, '');
		self::assertSame($a->getPolicies(), $b->getPolicies(),
			'Omitting $value must produce the same state as passing an explicit empty string');
	}

	public function testAddPolicyDelegatesToSetPolicy(): void
	{
		// addPolicy and setPolicy must produce identical backing state.
		$a = new THttpHeaderCsp();
		$b = new THttpHeaderCsp();
		$a->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$b->setPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame($a->getPolicies(), $b->getPolicies());
	}

	public function testSetPolicyLowercasesDirectiveName(): void
	{
		$this->csp->setPolicy('Default-Src', "'self'");
		self::assertSame("'self'", $this->csp->getPolicy(TCspDirective::DefaultSrc),
			'setPolicy() must lowercase the directive name per the CSP3 case-insensitivity rule');
		$policies = $this->csp->getPolicies();
		self::assertArrayHasKey(TCspDirective::DefaultSrc, $policies,
			'Stored key must be lowercase regardless of the input case');
	}

	public function testGetPolicyTrimsName(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame("'self'", $this->csp->getPolicy(' default-src '));
	}

	public function testGetPolicyCaseInsensitive(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame("'self'", $this->csp->getPolicy('DEFAULT-SRC'));
	}

	// =========================================================================
	// removePolicy
	// =========================================================================

	public function testRemovePolicyRemovesExistingDirective(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->removePolicy(TCspDirective::DefaultSrc);
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyReturnsTrueWhenDirectiveWasPresent(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($this->csp->removePolicy(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyReturnsFalseWhenDirectiveAbsent(): void
	{
		self::assertFalse($this->csp->removePolicy(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyIsNoOpForAbsentDirective(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->removePolicy(TCspDirective::ScriptSrc);
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyReturnsFalseWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		self::assertFalse($this->csp->removePolicy(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyIsNoOpWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		$raw = $this->csp->getPolicies();
		$this->csp->removePolicy(TCspDirective::DefaultSrc);
		self::assertSame($raw, $this->csp->getPolicies());
	}

	public function testRemovePolicyTrimsName(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$result = $this->csp->removePolicy(' default-src ');
		self::assertTrue($result);
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyCaseInsensitive(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$result = $this->csp->removePolicy('DEFAULT-SRC');
		self::assertTrue($result);
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	// =========================================================================
	// getReportToNames
	// =========================================================================

	public function testGetReportToNamesReturnsEmptyArrayByDefault(): void
	{
		self::assertSame([], $this->csp->getReportToNames());
	}

	public function testGetReportToNamesReturnsEndpointName(): void
	{
		$this->csp->addPolicy(TCspDirective::ReportTo, 'csp-endpoint');
		self::assertSame(['csp-endpoint'], $this->csp->getReportToNames());
	}

	public function testGetReportToNamesTrimsWhitespace(): void
	{
		$this->csp->addPolicy(TCspDirective::ReportTo, '  my-endpoint  ');
		self::assertSame(['my-endpoint'], $this->csp->getReportToNames());
	}

	public function testGetReportToNamesReturnsEmptyArrayForEmptyValue(): void
	{
		$this->csp->addPolicy(TCspDirective::ReportTo, '');
		self::assertSame([], $this->csp->getReportToNames());
	}

	public function testGetReportToNamesReturnsEmptyArrayWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		self::assertSame([], $this->csp->getReportToNames());
	}

	// =========================================================================
	// hasReportUriPlaceholder
	// =========================================================================

	public function testHasReportUriPlaceholderReturnsFalseByDefault(): void
	{
		self::assertFalse($this->csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsTrueForSentinelValue(): void
	{
		$this->csp->addPolicy(TCspDirective::ReportUri, THttpHeaderCsp::REPORT_URI);
		self::assertTrue($this->csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsTrueForEmptyValueAfterInit(): void
	{
		// Blank value is normalized to the REPORT_URI sentinel during init().
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [['name' => TCspDirective::ReportUri, 'value' => '']]]);
		self::assertSame(THttpHeaderCsp::REPORT_URI, $csp->getPolicy(TCspDirective::ReportUri),
			'Blank report-uri must be promoted to REPORT_URI sentinel during init()');
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsTrueForWhitespaceOnlyValueAfterInit(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [['name' => TCspDirective::ReportUri, 'value' => '   ']]]);
		self::assertSame(THttpHeaderCsp::REPORT_URI, $csp->getPolicy(TCspDirective::ReportUri),
			'Whitespace-only report-uri must be promoted to REPORT_URI sentinel during init()');
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsFalseForDeveloperSuppliedUrl(): void
	{
		$this->csp->addPolicy(TCspDirective::ReportUri, 'https://example.com/csp-report');
		self::assertFalse($this->csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsFalseWhenPoliciesIsRawString(): void
	{
		$this->csp->setHeaderValue('$#@!');
		self::assertFalse($this->csp->hasReportUriPlaceholder());
	}

	// =========================================================================
	// getHeaderValue
	// =========================================================================

	public function testGetHeaderValueEmptyPoliciesReturnsEmptyString(): void
	{
		self::assertSame('', $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueSingleDirective(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame("default-src 'self'", $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueMultipleDirectivesSemicolonSeparated(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::ScriptSrc, "'self'");
		self::assertSame("default-src 'self'; script-src 'self'", $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueBareDirectiveNoTrailingSpace(): void
	{
		$this->csp->addPolicy(TCspDirective::UpgradeInsecureRequests, '');
		self::assertSame('upgrade-insecure-requests', $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueMixedNormalAndBareDirectives(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::UpgradeInsecureRequests, '');
		self::assertSame("default-src 'self'; upgrade-insecure-requests", $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueNoncePlaceholderReplacedWhenNonceSet(): void
	{
		TJavaScript::setScriptNonce('abc123');
		$this->csp->addPolicy(TCspDirective::ScriptSrc, "'self' " . THttpHeaderCsp::NONCE);
		self::assertSame("script-src 'self' 'nonce-abc123'", $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueNoncePlaceholderLeftWhenNonceNull(): void
	{
		// Nonce is null (not yet generated) — NONCE token is left in-place.
		TJavaScript::setScriptNonce(null);
		$this->csp->addPolicy(TCspDirective::ScriptSrc, "'self' " . THttpHeaderCsp::NONCE);
		self::assertStringContainsString(THttpHeaderCsp::NONCE, $this->csp->getHeaderValue());
	}

	public function testGetHeaderValueRawStringReturnedWhenPoliciesUnparseable(): void
	{
		$raw = '$#@!';
		$this->csp->setHeaderValue($raw);
		self::assertSame($raw, $this->csp->getHeaderValue());
	}

	// =========================================================================
	// setHeaderValue — parsing
	// =========================================================================

	public function testSetHeaderValueSingleDirectiveWithValue(): void
	{
		$this->csp->setHeaderValue("default-src 'self'");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		$policies = $this->csp->getPolicies();
		self::assertSame("'self'", $policies[TCspDirective::DefaultSrc]);
	}

	public function testSetHeaderValueBareDirective(): void
	{
		$this->csp->setHeaderValue('upgrade-insecure-requests');
		self::assertTrue($this->csp->hasPolicy(TCspDirective::UpgradeInsecureRequests));
		$policies = $this->csp->getPolicies();
		self::assertSame('', $policies[TCspDirective::UpgradeInsecureRequests]);
	}

	public function testSetHeaderValueMultipleDirectives(): void
	{
		$this->csp->setHeaderValue("default-src 'self'; script-src 'self' 'unsafe-inline'");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
		$policies = $this->csp->getPolicies();
		self::assertSame("'self'", $policies[TCspDirective::DefaultSrc]);
		self::assertSame("'self' 'unsafe-inline'", $policies[TCspDirective::ScriptSrc]);
	}

	public function testSetHeaderValueHandlesExtraWhitespace(): void
	{
		$this->csp->setHeaderValue("  default-src   'self'  ;  script-src 'none'  ");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	public function testSetHeaderValueMixedNormalAndBareDirectives(): void
	{
		$this->csp->setHeaderValue("default-src 'self'; upgrade-insecure-requests");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::UpgradeInsecureRequests));
		$policies = $this->csp->getPolicies();
		self::assertSame('', $policies[TCspDirective::UpgradeInsecureRequests]);
	}

	public function testSetHeaderValueUnparseableInputStoredAsRawString(): void
	{
		$raw = '$#@!';
		$this->csp->setHeaderValue($raw);
		self::assertIsString($this->csp->getPolicies());
		self::assertSame($raw, $this->csp->getPolicies());
	}

	public function testSetHeaderValueEmptyStringStoredAsRawString(): void
	{
		$this->csp->setHeaderValue('');
		// Empty input cannot produce a directive map; stored as raw.
		self::assertIsString($this->csp->getPolicies());
	}

	public function testSetHeaderValueOnlySemicolonsStoredAsRawString(): void
	{
		// ';;;' after PREG_SPLIT_NO_EMPTY yields zero tokens → empty($directives) is
		// true → stored as the raw string, not as an empty policy map.
		$this->csp->setHeaderValue(';;;');
		$policies = $this->csp->getPolicies();
		self::assertIsString($policies,
			'All-semicolon input must be stored as a raw string, not a parsed map');
		self::assertSame(';;;', $policies);
	}

	public function testSetHeaderValueReplacesExistingPolicies(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->setHeaderValue("script-src 'none'");
		self::assertFalse($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	public function testSetHeaderValueSandboxWithTokens(): void
	{
		$this->csp->setHeaderValue('sandbox allow-scripts allow-same-origin');
		self::assertTrue($this->csp->hasPolicy(TCspDirective::Sandbox));
		$policies = $this->csp->getPolicies();
		self::assertSame('allow-scripts allow-same-origin', $policies[TCspDirective::Sandbox]);
	}

	// =========================================================================
	// setHeaderValue ↔ getHeaderValue round-trip
	// =========================================================================

	public function testRoundTripSingleDirective(): void
	{
		$value = "default-src 'self'";
		$this->csp->setHeaderValue($value);
		self::assertSame($value, $this->csp->getHeaderValue());
	}

	public function testRoundTripMultipleDirectives(): void
	{
		$value = "default-src 'self'; script-src 'self'; upgrade-insecure-requests";
		$this->csp->setHeaderValue($value);
		self::assertSame($value, $this->csp->getHeaderValue());
	}

	public function testSetHeaderValueLowercasesDirectiveNames(): void
	{
		$this->csp->setHeaderValue("Default-Src 'self'; Script-Src 'none'");
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc),
			'setHeaderValue() must lowercase directive names for consistent lookup');
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
		$policies = $this->csp->getPolicies();
		self::assertArrayHasKey(TCspDirective::DefaultSrc, $policies);
		self::assertArrayHasKey(TCspDirective::ScriptSrc, $policies);
	}

	public function testSetHeaderValueNormalizesBlankReportUri(): void
	{
		// A bare `report-uri` directive (no URL) should normalize to REPORT_URI sentinel
		// via setPolicy(), just like an empty value passed to setPolicy() directly.
		$this->csp->setHeaderValue("default-src 'self'; report-uri");
		self::assertSame(THttpHeaderCsp::REPORT_URI, $this->csp->getPolicy(TCspDirective::ReportUri),
			'setHeaderValue() must normalize a blank report-uri to the REPORT_URI sentinel');
		self::assertTrue($this->csp->hasReportUriPlaceholder());
	}

	public function testSetHeaderValueEmptyNameEntrySkippedViaLoadPolicies(): void
	{
		// An init() config entry with no 'name' key must be silently skipped.
		// loadPolicies() now guards against blank names; this tests via the array path.
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [
			['value' => "'self'"],                                  // no 'name' key → skipped
			['name' => TCspDirective::ScriptSrc, 'value' => "'none'"], // valid
		]]);
		self::assertFalse($csp->hasPolicy(''),
			'A policy entry with no name must be silently skipped');
		self::assertTrue($csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	// =========================================================================
	// finalizeHeader — sandbox stripping
	// =========================================================================

	public function testFinalizeHeaderSandboxKeptWhenNotReportOnly(): void
	{
		$this->csp->addPolicy(TCspDirective::Sandbox, '');
		$this->csp->finalizeHeader();
		// Not report-only — sandbox must still be present.
		self::assertTrue($this->csp->hasPolicy(TCspDirective::Sandbox));
	}

	public function testFinalizeHeaderRemovesSandboxWhenReportOnly(): void
	{
		$this->csp->setReportOnly(true);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::Sandbox, '');
		$this->csp->finalizeHeader();
		self::assertFalse($this->csp->hasPolicy(TCspDirective::Sandbox));
	}

	public function testFinalizeHeaderPreservesOtherDirectivesWhenSandboxRemoved(): void
	{
		$this->csp->setReportOnly(true);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::Sandbox, '');
		$this->csp->finalizeHeader();
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testFinalizeHeaderIsNoOpWhenReportOnlyButNoSandbox(): void
	{
		$this->csp->setReportOnly(true);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->finalizeHeader();
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	// =========================================================================
	// finalizeHeader
	// =========================================================================

	public function testFinalizeHeaderIsNoOpWhenNoReportTo(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		// No manager, no report-to — must not throw.
		$this->csp->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderIsNoOpWhenNoManager(): void
	{
		$this->csp->addPolicy(TCspDirective::ReportTo, 'missing-endpoint');
		// No manager set — must return early without throwing.
		$this->csp->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderDoesNotThrowWhenEndpointMissing(): void
	{
		// Manager present but no matching Reporting-Endpoints header — logs a
		// warning and returns; must not throw.
		$manager = new THttpHeadersManager();
		$this->csp->setManager($manager);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::ReportTo, 'missing-endpoint');
		$this->csp->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderDoesNotThrowWhenEndpointPresent(): void
	{
		$manager = new THttpHeadersManager();
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('csp-ep', 'https://example.com/csp');

		// Wire both headers to the same manager so getHeaders() sees them.
		PradoUnit::setProp($manager, '_headers', [$this->csp, $re]);

		$this->csp->setManager($manager);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::ReportTo, 'csp-ep');

		$this->csp->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	// =========================================================================
	// init — array config
	// =========================================================================

	public function testInitWithNullConfigDoesNotThrow(): void
	{
		$this->csp->init(null);
		$this->addToAssertionCount(1);
	}

	public function testInitWithNullConfigLeavesNoPolicies(): void
	{
		$this->csp->init(null);
		self::assertEmpty($this->csp->getPolicies());
	}

	public function testInitWithEmptyArrayConfigDoesNotThrow(): void
	{
		$this->csp->init([]);
		$this->addToAssertionCount(1);
	}

	public function testInitLoadsArrayPolicies(): void
	{
		$this->csp->init([
			'policies' => [
				['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
				['name' => TCspDirective::ScriptSrc,  'value' => "'none'"],
			],
		]);
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	// =========================================================================
	// init — NONCE policy handling
	// =========================================================================

	public function testInitWithNoncePolicyStoresPolicyValue(): void
	{
		// The NONCE placeholder is stored verbatim in the policy map at init time
		// and substituted at render time by getHeaderValue().
		$this->csp->init([
			'policies' => [
				['name' => TCspDirective::ScriptSrc, 'value' => "'self' " . THttpHeaderCsp::NONCE],
			],
		]);
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
		$policies = $this->csp->getPolicies();
		self::assertStringContainsString(THttpHeaderCsp::NONCE, $policies[TCspDirective::ScriptSrc]);
	}

	public function testInitWithNoncePolicyDoesNotThrow(): void
	{
		// Exercises the null-safe ?-> chain on getApplication()/getSecurityManager();
		// must not crash regardless of application or security-manager state.
		$this->csp->init([
			'policies' => [
				['name' => TCspDirective::ScriptSrc, 'value' => THttpHeaderCsp::NONCE],
			],
		]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithNoncePolicyAppliesNonceFromSecurityManagerWhenPresent(): void
	{
		// The null-safe chain: getApplication()?->getSecurityManager()?->getCSPNonce()
		// Whatever the chain produces, TJavaScript must reflect it consistently:
		//   • non-null nonce  → TJavaScript::getScriptNonce() === that nonce
		//   • null (no SM or SM returns null) → TJavaScript::getScriptNonce() === null
		// This test verifies the chain is wired correctly without assuming whether the
		// test environment has a security manager.
		$app = \Prado\Prado::getApplication();
		$expectedNonce = $app?->getSecurityManager()?->getCSPNonce();

		TJavaScript::setScriptNonce(null); // clean slate before init
		$this->csp->init([
			'policies' => [
				['name' => TCspDirective::ScriptSrc, 'value' => THttpHeaderCsp::NONCE],
			],
		]);

		if ($expectedNonce !== null) {
			self::assertSame($expectedNonce, TJavaScript::getScriptNonce(),
				'When the security manager provides a nonce, TJavaScript must receive it');
		} else {
			self::assertNull(TJavaScript::getScriptNonce(),
				'When no nonce is available from the security manager, TJavaScript nonce must remain null');
		}
	}

	public function testGetHeaderValueNoncePlaceholderReplacedAtAllOccurrences(): void
	{
		// str_replace replaces ALL occurrences of NONCE within a single policy value.
		TJavaScript::setScriptNonce('xyz');
		$this->csp->addPolicy(
			TCspDirective::ScriptSrc,
			THttpHeaderCsp::NONCE . " 'self' " . THttpHeaderCsp::NONCE
		);
		$value = $this->csp->getHeaderValue();
		self::assertStringNotContainsString(THttpHeaderCsp::NONCE, $value,
			'All occurrences of the NONCE placeholder must be replaced');
		self::assertSame(2, substr_count($value, "'nonce-xyz'"),
			'Both NONCE placeholders must be replaced with the actual nonce');
	}

	// =========================================================================
	// init — defensive loading (missing keys in policy entries)
	// =========================================================================

	public function testInitWithMissingPolicyNameDoesNotThrow(): void
	{
		// loadPolicies() uses ?? '' for absent 'name'; must not produce a TypeError.
		$this->csp->init(['policies' => [['value' => "'self'"]]]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithMissingPolicyValueDoesNotThrow(): void
	{
		// loadPolicies() uses ?? '' for absent 'value'; must not produce a TypeError.
		$this->csp->init(['policies' => [['name' => TCspDirective::DefaultSrc]]]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithMissingPolicyValueStoresBareDirective(): void
	{
		// An entry with only a 'name' key is loaded as a bare directive (empty value).
		$this->csp->init(['policies' => [['name' => TCspDirective::DefaultSrc]]]);
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		$policies = $this->csp->getPolicies();
		self::assertSame('', $policies[TCspDirective::DefaultSrc]);
	}

	// =========================================================================
	// configToArray / normalizeConfig — XML input path
	// =========================================================================

	public function testInitWithXmlElementLoadsPoliciesToSameResultAsArrayConfig(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			"<header>"
			. "<policy Name=\"default-src\">'self'</policy>"
			. "<policy Name=\"script-src\">'none'</policy>"
			. "</header>"
		);

		$this->csp->init($doc);

		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
		$policies = $this->csp->getPolicies();
		self::assertSame("'self'", $policies[TCspDirective::DefaultSrc]);
		self::assertSame("'none'", $policies[TCspDirective::ScriptSrc]);
	}

	public function testXmlConfigProducesSameHeaderValueAsArrayConfig(): void
	{
		// Build via PHP array.
		$arrayCsp = new THttpHeaderCsp();
		$arrayCsp->init([
			'policies' => [
				['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
				['name' => TCspDirective::ScriptSrc,  'value' => "'self' 'unsafe-inline'"],
			],
		]);

		// Build via XML.
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			"<header>"
			. "<policy Name=\"default-src\">'self'</policy>"
			. "<policy Name=\"script-src\">'self' 'unsafe-inline'</policy>"
			. "</header>"
		);
		$this->csp->init($doc);

		self::assertSame($arrayCsp->getHeaderValue(), $this->csp->getHeaderValue());
	}

	public function testXmlConfigWithBareDirective(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			"<header>"
			. '<policy Name="upgrade-insecure-requests"></policy>'
			. "</header>"
		);
		$this->csp->init($doc);

		self::assertTrue($this->csp->hasPolicy(TCspDirective::UpgradeInsecureRequests));
		self::assertSame('upgrade-insecure-requests', $this->csp->getHeaderValue());
	}

	public function testXmlConfigWithNoChildElementsIsNoOp(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<header></header>');
		$this->csp->init($doc);

		self::assertSame([], $this->csp->getPolicies());
	}

	public function testConfigToArrayPreservesInsertionOrder(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			"<header>"
			. "<policy Name=\"default-src\">'self'</policy>"
			. "<policy Name=\"img-src\">'self' data:</policy>"
			. "<policy Name=\"script-src\">'none'</policy>"
			. "</header>"
		);
		$this->csp->init($doc);

		$policies = $this->csp->getPolicies();
		self::assertIsArray($policies);
		self::assertSame(
			[TCspDirective::DefaultSrc, TCspDirective::ImgSrc, TCspDirective::ScriptSrc],
			array_keys($policies)
		);
	}

	// =========================================================================
	// __toString — inherited from THttpHeaderBase
	// =========================================================================

	public function testToStringEnforcingFormat(): void
	{
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame(
			"Content-Security-Policy: default-src 'self'",
			(string) $this->csp
		);
	}

	public function testToStringReportOnlyFormat(): void
	{
		$this->csp->setReportOnly(true);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame(
			"Content-Security-Policy-Report-Only: default-src 'self'",
			(string) $this->csp
		);
	}

	// =========================================================================
	// Protected Direct accessors — tested via anonymous subclass
	// =========================================================================

	private function makeExposed(): object
	{
		return new class extends THttpHeaderCsp {
			public function exposedHasPoliciesDirect(): bool
			{
				return $this->hasPoliciesDirect();
			}

			public function exposedGetPolicyDirect(string $name): ?string
			{
				return $this->getPolicyDirect($name);
			}

			public function exposedHasPolicyDirect(string $name): bool
			{
				return $this->hasPolicyDirect($name);
			}

			public function exposedSetPolicyDirect(string $name, string $value): void
			{
				$this->setPolicyDirect($name, $value);
			}

			public function exposedRemovePolicyDirect(string $name): void
			{
				$this->removePolicyDirect($name);
			}
		};
	}

	// ---- isPoliciesStructured -----------------------------------------------

	public function testIsPoliciesStructuredFalseWhenRawString(): void
	{
		// setPolicies(string) stores the string as-is in the raw fallback slot.
		$csp = new THttpHeaderCsp();
		$csp->setPolicies("default-src 'self'");
		self::assertFalse($csp->isPoliciesStructured());
	}

	public function testIsPoliciesStructuredTrueAfterInit(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		self::assertTrue($csp->isPoliciesStructured());
	}

	public function testIsPoliciesStructuredTrueAfterAddPolicy(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($csp->isPoliciesStructured());
	}

	// ---- hasPoliciesDirect --------------------------------------------------

	public function testHasPoliciesDirectFalseWhenRawString(): void
	{
		$csp = $this->makeExposed();
		$csp->setPolicies("default-src 'self'");
		self::assertFalse($csp->exposedHasPoliciesDirect());
	}

	public function testHasPoliciesDirectFalseWhenEmptyArray(): void
	{
		$csp = $this->makeExposed();
		$csp->init([]);
		self::assertFalse($csp->exposedHasPoliciesDirect());
	}

	public function testHasPoliciesDirectTrueAfterAddPolicy(): void
	{
		$csp = $this->makeExposed();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($csp->exposedHasPoliciesDirect());
	}

	// ---- getPolicyDirect / hasPolicyDirect ----------------------------------

	public function testGetPolicyDirectReturnsNullWhenAbsent(): void
	{
		$csp = $this->makeExposed();
		$csp->init([]);
		self::assertNull($csp->exposedGetPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testGetPolicyDirectReturnsNullWhenPoliciesIsRawString(): void
	{
		$csp = $this->makeExposed();
		$csp->setPolicies("default-src 'self'");
		self::assertNull($csp->exposedGetPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testGetPolicyDirectReturnsValueWhenPresent(): void
	{
		$csp = $this->makeExposed();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertSame("'self'", $csp->exposedGetPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyDirectFalseWhenAbsent(): void
	{
		$csp = $this->makeExposed();
		$csp->init([]);
		self::assertFalse($csp->exposedHasPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyDirectFalseWhenPoliciesIsRawString(): void
	{
		$csp = $this->makeExposed();
		$csp->setPolicies("default-src 'self'");
		self::assertFalse($csp->exposedHasPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testHasPolicyDirectTrueWhenPresent(): void
	{
		$csp = $this->makeExposed();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertTrue($csp->exposedHasPolicyDirect(TCspDirective::DefaultSrc));
	}

	// ---- setPolicyDirect ----------------------------------------------------

	public function testSetPolicyDirectWritesToBackingStore(): void
	{
		$csp = $this->makeExposed();
		$csp->init([]);
		$csp->exposedSetPolicyDirect(TCspDirective::DefaultSrc, "'self'");
		self::assertSame("'self'", $csp->exposedGetPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testSetPolicyDirectReplacesExistingValue(): void
	{
		$csp = $this->makeExposed();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$csp->exposedSetPolicyDirect(TCspDirective::DefaultSrc, "'none'");
		self::assertSame("'none'", $csp->exposedGetPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testSetPolicyDirectIsNoOpWhenPoliciesIsRawString(): void
	{
		$csp = $this->makeExposed();
		$csp->setPolicies("default-src 'self'");
		// Must not throw; raw string must be unchanged.
		$csp->exposedSetPolicyDirect(TCspDirective::DefaultSrc, "'none'");
		self::assertSame("default-src 'self'", $csp->getPolicies());
	}

	// ---- removePolicyDirect -------------------------------------------------

	public function testRemovePolicyDirectDeletesEntry(): void
	{
		$csp = $this->makeExposed();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$csp->exposedRemovePolicyDirect(TCspDirective::DefaultSrc);
		self::assertFalse($csp->exposedHasPolicyDirect(TCspDirective::DefaultSrc));
	}

	public function testRemovePolicyDirectIsNoOpWhenAbsent(): void
	{
		$csp = $this->makeExposed();
		$csp->init([]);
		// Must not throw.
		$csp->exposedRemovePolicyDirect(TCspDirective::DefaultSrc);
		self::assertFalse($csp->exposedHasPolicyDirect(TCspDirective::DefaultSrc));
	}

	// =========================================================================
	// hasReportUriPlaceholder
	// =========================================================================

	public function testHasReportUriPlaceholderFalseWhenAbsent(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		self::assertFalse($csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderFalseWhenRealUrl(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportUri, 'https://collector.example.com/report');
		self::assertFalse($csp->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderTrueWhenSentinelSet(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportUri, THttpHeaderCsp::REPORT_URI);
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	// =========================================================================
	// setPolicy() / addPolicy() blank report-uri normalization
	// =========================================================================

	public function testSetPolicyNormalizesBlankReportUriToSentinel(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy(TCspDirective::ReportUri, '');
		self::assertSame(THttpHeaderCsp::REPORT_URI, $csp->getPolicy(TCspDirective::ReportUri));
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	public function testSetPolicyNormalizesWhitespaceOnlyReportUriToSentinel(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy(TCspDirective::ReportUri, '   ');
		self::assertSame(THttpHeaderCsp::REPORT_URI, $csp->getPolicy(TCspDirective::ReportUri));
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	public function testAddPolicyNormalizesBlankReportUriToSentinel(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportUri, '');
		self::assertSame(THttpHeaderCsp::REPORT_URI, $csp->getPolicy(TCspDirective::ReportUri));
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	public function testSetPolicyDoesNotNormalizeBlankForOtherDirectives(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy(TCspDirective::UpgradeInsecureRequests, '');
		self::assertSame('', $csp->getPolicy(TCspDirective::UpgradeInsecureRequests));
	}

	public function testSetPolicyDoesNotNormalizeRealReportUri(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy(TCspDirective::ReportUri, 'https://collector.example.com/report');
		self::assertSame('https://collector.example.com/report', $csp->getPolicy(TCspDirective::ReportUri));
		self::assertFalse($csp->hasReportUriPlaceholder());
	}

	public function testInitWithBlankReportUriNormalizesToSentinelViaSetterChain(): void
	{
		// loadPolicies() → addPolicy() → setPolicy() — normalization flows through the chain.
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [['name' => TCspDirective::ReportUri, 'value' => '']]]);
		self::assertSame(THttpHeaderCsp::REPORT_URI, $csp->getPolicy(TCspDirective::ReportUri));
		self::assertTrue($csp->hasReportUriPlaceholder());
	}

	public function testInitWithRealReportUriPreservesValue(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [
			['name' => TCspDirective::ReportUri, 'value' => 'https://collector.example.com/report'],
		]]);
		self::assertSame('https://collector.example.com/report', $csp->getPolicy(TCspDirective::ReportUri));
		self::assertFalse($csp->hasReportUriPlaceholder());
	}

	public function testInitWithoutReportUriLeavesItAbsent(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		self::assertNull($csp->getPolicy(TCspDirective::ReportUri));
		self::assertFalse($csp->hasReportUriPlaceholder());
	}

	// =========================================================================
	// setPolicy() name and value trimming
	// =========================================================================

	public function testSetPolicyTrimsName(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy('  ' . TCspDirective::DefaultSrc . '  ', "'self'");
		// Stored under the trimmed key; getPolicy() also trims its name argument,
		// so a padded lookup finds the same value.
		self::assertSame("'self'", $csp->getPolicy(TCspDirective::DefaultSrc));
		self::assertSame("'self'", $csp->getPolicy('  ' . TCspDirective::DefaultSrc . '  '));
	}

	public function testSetPolicyTrimsValue(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy(TCspDirective::DefaultSrc, "  'self' cdn.example.com  ");
		self::assertSame("'self' cdn.example.com", $csp->getPolicy(TCspDirective::DefaultSrc));
	}

	public function testAddPolicyTrimsNameAndValue(): void
	{
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->addPolicy('  ' . TCspDirective::ScriptSrc . '  ', "  'nonce-abc'  ");
		self::assertSame("'nonce-abc'", $csp->getPolicy(TCspDirective::ScriptSrc));
		// getPolicy() trims its name argument, so a padded lookup also finds the value.
		self::assertSame("'nonce-abc'", $csp->getPolicy('  ' . TCspDirective::ScriptSrc . '  '));
	}

	public function testSetPolicyWhitespaceOnlyValueStoredAsEmptyForBareDirectives(): void
	{
		// For directives other than report-uri, whitespace-only trims to '' (bare directive).
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->setPolicy(TCspDirective::UpgradeInsecureRequests, '   ');
		self::assertSame('', $csp->getPolicy(TCspDirective::UpgradeInsecureRequests));
	}

	// =========================================================================
	// isPoliciesStructured — default state
	// =========================================================================

	public function testIsPoliciesStructuredTrueByDefault(): void
	{
		// A freshly constructed THttpHeaderCsp has _policies = [] (an array),
		// so isPoliciesStructured() must return true even before any init() call.
		$csp = new THttpHeaderCsp();
		self::assertTrue($csp->isPoliciesStructured(),
			'A fresh THttpHeaderCsp must be structured (array backing) by default');
	}

	// =========================================================================
	// getPolicy — raw-string state
	// =========================================================================

	public function testGetPolicyReturnsNullWhenPoliciesIsRawString(): void
	{
		// When the backing field holds an unparseable raw string, the public
		// getPolicy() accessor must return null (not the raw string).
		$csp = new THttpHeaderCsp();
		$csp->setHeaderValue('$#@!'); // unparseable → stored raw
		self::assertNull($csp->getPolicy(TCspDirective::DefaultSrc),
			'getPolicy() must return null when _policies is a raw string, not a structured map');
	}

	// =========================================================================
	// setPolicies — empty string produces raw-string backing
	// =========================================================================

	public function testSetPoliciesEmptyStringRawBackingField(): void
	{
		// setPolicies(string) stores the string as-is without parsing.
		// An empty string is a valid raw-string state.
		$csp = new THttpHeaderCsp();
		$csp->setPolicies('');
		self::assertFalse($csp->isPoliciesStructured(),
			'setPolicies("") must leave _policies as a raw string, not an empty array');
		self::assertSame('', $csp->getPolicies(),
			'getPolicies() must return the empty string stored by setPolicies("")');
	}

	// =========================================================================
	// getReportToNames — multi-token value
	// =========================================================================

	public function testGetReportToNamesWithMultiTokenValue(): void
	{
		// The Reporting API spec restricts report-to to a single endpoint-group
		// name token. When a developer supplies multiple space-separated tokens,
		// getReportToNames() wraps the full stored string as one element — it
		// does not split on whitespace. This documents the current behaviour.
		$csp = new THttpHeaderCsp();
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportTo, 'ep1 ep2');
		self::assertSame(['ep1 ep2'], $csp->getReportToNames(),
			'getReportToNames() must return the full stored string as a single element');
	}

	// =========================================================================
	// setReportOnly — integer coercion
	// =========================================================================

	public function testSetReportOnlyZeroFalse(): void
	{
		// PHP integer 0 is falsy; TPropertyValue::ensureBoolean(0) must return false.
		$csp = new THttpHeaderCsp();
		$csp->setReportOnly(true); // prime to a non-default state first
		$csp->setReportOnly(0);
		self::assertFalse($csp->getReportOnly(),
			'setReportOnly(0) must coerce to false via TPropertyValue::ensureBoolean()');
	}

	// =========================================================================
	// finalizeHeader — endpoint in second Reporting-Endpoints header
	// =========================================================================

	public function testFinalizeHeaderAcceptsEndpointNameDeclaredInSecondReHeader(): void
	{
		// finalizeHeader() iterates ALL sibling headers for Reporting-Endpoints
		// instances. The endpoint name in report-to must be found even when it is
		// declared in the second (not the first) RE header.
		$manager = new THttpHeadersManager();

		$re1 = new THttpHeaderReportingEndpoints();
		$re1->addEndpoint('other-ep', 'https://example.com/other');

		$re2 = new THttpHeaderReportingEndpoints();
		$re2->addEndpoint('csp-ep', 'https://example.com/csp');

		PradoUnit::setProp($manager, '_headers', [$this->csp, $re1, $re2]);

		$this->csp->setManager($manager);
		$this->csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$this->csp->addPolicy(TCspDirective::ReportTo, 'csp-ep');

		// Must not throw or log an unresolved-endpoint warning.
		$this->csp->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	// =========================================================================
	// finalizeHeader — raw-string policies + ReportOnly
	// =========================================================================

	public function testFinalizeHeaderWithRawStringPoliciesAndReportOnly(): void
	{
		// When _policies is a raw string, hasPolicy() always returns false.
		// finalizeHeader() must handle this gracefully:
		//   1. The sandbox-strip branch sees hasPolicy(Sandbox) = false → skips.
		//   2. getReportToNames() returns [] → early return, no warning.
		$csp = new THttpHeaderCsp();
		$csp->setReportOnly(true);
		$csp->setHeaderValue('$#@!'); // forces raw-string backing
		// Must not throw.
		$csp->finalizeHeader();
		// Raw string must be preserved — no mutation.
		self::assertSame('$#@!', $csp->getPolicies());
	}

	// =========================================================================
	// loadPolicies — whitespace-only name skipped (fixed guard)
	// =========================================================================

	public function testLoadPoliciesWithWhitespaceOnlyNameIsSkipped(): void
	{
		// loadPolicies() guards against entries with a missing OR whitespace-only
		// 'name' key. After trim(), a whitespace-only name collapses to '' and must
		// be skipped — it would otherwise store under key '' producing a malformed
		// header value.
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [
			['name' => '   ', 'value' => "'self'"],                         // whitespace-only → skipped
			['name' => TCspDirective::ScriptSrc, 'value' => "'none'"],      // valid
		]]);
		self::assertFalse($csp->hasPolicy(''),
			'A whitespace-only policy name must be silently skipped, not stored under key ""');
		self::assertTrue($csp->hasPolicy(TCspDirective::ScriptSrc));
		self::assertCount(1, $csp->getPolicies());
	}

	// =========================================================================
	// configToArray — XML attribute names lowercased
	// =========================================================================

	public function testConfigToArrayNormalizesAttributeNamesToLowercase(): void
	{
		// array_change_key_case(..., CASE_LOWER) normalises PascalCase XML attribute
		// names to lowercase so the result matches the PHP array config format.
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			"<header>"
			. "<policy Name=\"default-src\">'self'</policy>"
			. "</header>"
		);
		$csp = new THttpHeaderCsp();
		$csp->init($doc);
		// The 'Name' XML attribute must have been lowercased to 'name' so that
		// loadPolicies() can read it. Verifiable via the resulting policy map.
		self::assertTrue($csp->hasPolicy(TCspDirective::DefaultSrc),
			"configToArray() must lowercase 'Name' to 'name' so loadPolicies() finds it");
	}

	// =========================================================================
	// setHeaderValue — edge cases: trailing/consecutive semicolons, invalid char
	// =========================================================================

	public function testSetHeaderValueWithTrailingSemicolon(): void
	{
		// A trailing semicolon produces an empty token after the split; PREG_SPLIT_NO_EMPTY
		// eliminates it, so the valid directives are still parsed into the structured map.
		$this->csp->setHeaderValue("default-src 'self';");
		self::assertTrue($this->csp->isPoliciesStructured(),
			'A trailing semicolon must not prevent structured parsing');
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertSame("'self'", $this->csp->getPolicy(TCspDirective::DefaultSrc));
	}

	public function testSetHeaderValueWithConsecutiveSemicolons(): void
	{
		// Consecutive semicolons produce empty tokens that PREG_SPLIT_NO_EMPTY drops.
		$this->csp->setHeaderValue("default-src 'self';;script-src 'none'");
		self::assertTrue($this->csp->isPoliciesStructured(),
			'Consecutive semicolons must not prevent structured parsing');
		self::assertTrue($this->csp->hasPolicy(TCspDirective::DefaultSrc));
		self::assertTrue($this->csp->hasPolicy(TCspDirective::ScriptSrc));
	}

	public function testSetHeaderValueDirectiveWithInvalidNameCharacterStoredRaw(): void
	{
		// The directive-name regex requires [a-zA-Z0-9_-]+. A leading '!' does not
		// match, so the whole input is stored as a raw string fallback.
		$raw = "!invalid 'self'";
		$this->csp->setHeaderValue($raw);
		self::assertFalse($this->csp->isPoliciesStructured(),
			'A directive with an invalid name character must force raw-string storage');
		self::assertSame($raw, $this->csp->getPolicies());
	}

	// =========================================================================
	// setHeaderValue — null coercion
	// =========================================================================

	public function testSetHeaderValueNullCoercedToEmptyString(): void
	{
		// setHeaderValue() passes the argument through TPropertyValue::ensureString(),
		// which converts null to ''. An empty string is stored as a raw string
		// (same behaviour as setHeaderValue('')).
		$csp = new THttpHeaderCsp();
		$csp->setHeaderValue(null);
		self::assertIsString($csp->getPolicies(),
			'null input must produce raw-string backing, not a structured map');
		self::assertFalse($csp->isPoliciesStructured(),
			'null input coerced to "" must leave _policies as a raw string');
	}

	// =========================================================================
	// setReportOnly — string '0' coercion
	// =========================================================================

	public function testSetReportOnlyStringZeroCoercesToFalse(): void
	{
		// TPropertyValue::ensureBoolean() treats '0' as false (falsy string).
		$csp = new THttpHeaderCsp();
		$csp->setReportOnly(true); // prime to a non-default state first
		$csp->setReportOnly('0');
		self::assertFalse($csp->getReportOnly(),
			"setReportOnly('0') must coerce to false via TPropertyValue::ensureBoolean()");
	}

	// =========================================================================
	// init — 'policies' key present but empty array
	// =========================================================================

	public function testInitWithPoliciesKeyPresentButEmptyArray(): void
	{
		// loadPolicies() iterates config['policies'] ?? []; an empty array is a
		// no-op and must leave the structured map empty.
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => []]);
		self::assertSame([], $csp->getPolicies(),
			"init(['policies' => []]) must leave an empty structured policy map");
		self::assertTrue($csp->isPoliciesStructured(),
			"init(['policies' => []]) must still produce a structured (array) backing");
		self::assertFalse($csp->hasPolicies());
	}

	// =========================================================================
	// getPolicy — whitespace-only name
	// =========================================================================

	public function testGetPolicyWithWhitespaceOnlyNameReturnsNull(): void
	{
		// getPolicy() trims its argument before lookup. Whitespace-only trims to ''
		// which is not present in the map unless explicitly added via addPolicy('', …).
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertNull($csp->getPolicy('   '),
			"getPolicy() with a whitespace-only name must return null (trims to '' which is absent)");
	}

	// =========================================================================
	// getHeaderValue — NONCE replacement across multiple directives
	// =========================================================================

	public function testGetHeaderValueNoncePlaceholderReplacedAcrossMultipleDirectives(): void
	{
		// getHeaderValue() applies str_replace per-directive-value; a NONCE in
		// script-src AND in style-src must both be independently replaced.
		TJavaScript::setScriptNonce('abc123');
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::ScriptSrc, "'self' " . THttpHeaderCsp::NONCE);
		$csp->addPolicy(TCspDirective::StyleSrc,  "'self' " . THttpHeaderCsp::NONCE);

		$value = $csp->getHeaderValue();
		self::assertStringNotContainsString(THttpHeaderCsp::NONCE, $value,
			'All NONCE placeholders across all directives must be replaced');
		self::assertStringContainsString("script-src 'self' 'nonce-abc123'", $value);
		self::assertStringContainsString("style-src 'self' 'nonce-abc123'", $value);
	}

	// =========================================================================
	// getHeaderValue — insertion order preserved across four directives
	// =========================================================================

	public function testGetHeaderValuePreservesInsertionOrderAcrossMultipleDirectives(): void
	{
		// PHP associative arrays preserve insertion order; getHeaderValue() must
		// emit directives in the order they were added.
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::DefaultSrc,               "'self'");
		$csp->addPolicy(TCspDirective::ScriptSrc,                "'none'");
		$csp->addPolicy(TCspDirective::ImgSrc,                   'data:');
		$csp->addPolicy(TCspDirective::UpgradeInsecureRequests,  '');

		self::assertSame(
			"default-src 'self'; script-src 'none'; img-src data:; upgrade-insecure-requests",
			$csp->getHeaderValue()
		);
	}
}
