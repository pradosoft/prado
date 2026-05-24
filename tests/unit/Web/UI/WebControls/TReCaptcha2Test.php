<?php

/**
 * TReCaptcha2Test class file.
 *
 * @author Beline Belisoful <govcorpwatch@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Exceptions\TConfigurationException;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TReCaptcha2;

// ---------------------------------------------------------------------------
// Spy subclass — controllable getValidationPropertyValue() and reset()
// ---------------------------------------------------------------------------

/**
 * SpyTReCaptcha2 overrides the two methods with external dependencies so that
 * validate() and the onCallbackExpired path of raiseCallbackEvent() can be
 * exercised without a live HTTP request or a real CAPTCHA widget.
 */
class SpyTReCaptcha2 extends TReCaptcha2
{
	private mixed $_spyValidationValue = null;
	private int $_resetCallCount = 0;
	private bool $_onCallbackFired = false;
	private bool $_onCallbackExpiredFired = false;

	/** Sets the value returned by getValidationPropertyValue(). */
	public function setSpyValidationValue(mixed $value): void
	{
		$this->_spyValidationValue = $value;
	}

	/** Returns the injected spy value instead of reading from the real request. */
	public function getValidationPropertyValue(): mixed
	{
		return $this->_spyValidationValue;
	}

	/**
	 * Overrides reset() to count invocations without requiring a real
	 * TCallbackClientScript / page lifecycle.
	 */
	public function reset(): void
	{
		$this->_resetCallCount++;
	}

	/** Returns the number of times reset() has been called. */
	public function getResetCallCount(): int
	{
		return $this->_resetCallCount;
	}
}

// ---------------------------------------------------------------------------
// TReCaptcha2Test
// ---------------------------------------------------------------------------

class TReCaptcha2Test extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Builds a SpyTReCaptcha2 attached to a fresh TPage so that controls
	 * which call getPage() work correctly in unit tests.
	 *
	 * @param string $id The control ID to assign.
	 * @return array{SpyTReCaptcha2, TPage}
	 */
	private function makeSpyWithPage(string $id = 'Captcha'): array
	{
		$page = new TPage();
		$captcha = new SpyTReCaptcha2();
		$captcha->setID($id);
		$page->getControls()->add($captcha);
		return [$captcha, $page];
	}

	/**
	 * Runs $callable inside a try/finally that restores the application's service
	 * after setting it to a fresh TPageService.  Required for tests that call
	 * onPreRender() with both keys set (which reaches getClientScript()).
	 */
	private function withPageService(callable $callable): void
	{
		$app = \Prado\Prado::getApplication();
		$original = $app->getService();
		$app->setService(new TPageService());
		try {
			$callable();
		} finally {
			$app->setService($original);
		}
	}

	/**
	 * Creates a minimal stdClass callback parameter that triggers the onCallback
	 * path inside raiseCallbackEvent().
	 */
	private function makeCallbackParam(
		bool $onCallback = true,
		string $response = 'tok123',
		string $responseField = 'g-recaptcha-response',
		int $widgetId = 7
	): \stdClass {
		$p = new \stdClass();
		$p->onCallback = $onCallback;
		$p->response = $response;
		$p->responseField = $responseField;
		$p->widgetId = $widgetId;
		return $p;
	}

	/**
	 * Creates a minimal stdClass callback parameter that triggers the
	 * onCallbackExpired path inside raiseCallbackEvent().
	 */
	private function makeExpiredParam(bool $onCallbackExpired = true): \stdClass
	{
		$p = new \stdClass();
		$p->onCallbackExpired = $onCallbackExpired;
		return $p;
	}

	/**
	 * Builds a TCallbackEventParameter whose getCallbackParameter() returns the
	 * supplied value.  The response argument is null because raiseCallbackEvent()
	 * only calls getCallbackParameter() on the event param.
	 */
	private function makeEventParam(mixed $callbackParameter): TCallbackEventParameter
	{
		return new TCallbackEventParameter(null, $callbackParameter);
	}

	// -----------------------------------------------------------------------
	// Constant
	// -----------------------------------------------------------------------

	public function testChallengeFieldNameConstant(): void
	{
		$this->assertSame('g-recaptcha-response', TReCaptcha2::ChallengeFieldName);
	}

	// -----------------------------------------------------------------------
	// Client metadata
	// -----------------------------------------------------------------------

	public function testGetClientClassName(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('Prado.WebUI.TReCaptcha2', $captcha->getClientClassName());
	}

	public function testGetTagName(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('div', $captcha->getTagName());
	}

	// -----------------------------------------------------------------------
	// IValidatable — IsValid
	// -----------------------------------------------------------------------

	public function testGetIsValidDefaultsToTrue(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertTrue($captcha->getIsValid());
	}

	public function testSetIsValidWithBooleanTrue(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setIsValid(true);
		$this->assertTrue($captcha->getIsValid());
	}

	public function testSetIsValidWithBooleanFalse(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setIsValid(false);
		$this->assertFalse($captcha->getIsValid());
	}

	public function testSetIsValidCoercesTruthyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setIsValid('true');
		$this->assertTrue($captcha->getIsValid());
	}

	public function testSetIsValidCoercesFalsyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setIsValid('false');
		$this->assertFalse($captcha->getIsValid());
	}

	public function testSetIsValidCoercesIntOne(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setIsValid(1);
		$this->assertTrue($captcha->getIsValid());
	}

	public function testSetIsValidCoercesIntZero(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setIsValid(0);
		$this->assertFalse($captcha->getIsValid());
	}

	// -----------------------------------------------------------------------
	// ViewState properties
	// -----------------------------------------------------------------------

	public function testSiteKeyDefaultIsNull(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertNull($captcha->getSiteKey());
	}

	public function testSiteKeyRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSiteKey('my-site-key');
		$this->assertSame('my-site-key', $captcha->getSiteKey());
	}

	public function testSiteKeyCoercesToString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSiteKey(42);
		$this->assertSame('42', $captcha->getSiteKey());
	}

	public function testSecretKeyDefaultIsNull(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertNull($captcha->getSecretKey());
	}

	public function testSecretKeyRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSecretKey('my-secret-key');
		$this->assertSame('my-secret-key', $captcha->getSecretKey());
	}

	public function testLanguageDefaultIsEn(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('en', $captcha->getLanguage());
	}

	public function testLanguageRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setLanguage('fr');
		$this->assertSame('fr', $captcha->getLanguage());
	}

	public function testLanguageResetsToDefault(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setLanguage('de');
		$captcha->setLanguage('en');
		$this->assertSame('en', $captcha->getLanguage());
	}

	public function testThemeDefaultIsLight(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('light', $captcha->getTheme());
	}

	public function testThemeRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setTheme('dark');
		$this->assertSame('dark', $captcha->getTheme());
	}

	public function testTypeDefaultIsImage(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('image', $captcha->getType());
	}

	public function testTypeRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setType('audio');
		$this->assertSame('audio', $captcha->getType());
	}

	public function testSizeDefaultIsNormal(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('normal', $captcha->getSize());
	}

	public function testSizeRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSize('compact');
		$this->assertSame('compact', $captcha->getSize());
	}

	public function testTabIndexDefaultIsZero(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame(0, $captcha->getTabIndex());
	}

	public function testTabIndexRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setTabIndex(5);
		$this->assertSame(5, $captcha->getTabIndex());
	}

	public function testTabIndexCoercesToInteger(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setTabIndex('3');
		$this->assertSame(3, $captcha->getTabIndex());
	}

	public function testCaptchaResponseDefaultIsEmptyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame('', $captcha->getCaptchaResponse());
	}

	public function testCaptchaResponseRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setCaptchaResponse('token-abc');
		$this->assertSame('token-abc', $captcha->getCaptchaResponse());
	}

	public function testCaptchaResponseCoercesToString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setCaptchaResponse(99);
		$this->assertSame('99', $captcha->getCaptchaResponse());
	}

	public function testWidgetIdDefaultIsZero(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertSame(0, $captcha->getWidgetId());
	}

	public function testWidgetIdRoundTrip(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setWidgetId(3);
		$this->assertSame(3, $captcha->getWidgetId());
	}

	public function testWidgetIdCoercesToInteger(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setWidgetId('2');
		$this->assertSame(2, $captcha->getWidgetId());
	}

	// -----------------------------------------------------------------------
	// validate()
	// -----------------------------------------------------------------------

	public function testValidateReturnsFalseWhenValidationPropertyValueIsNull(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSpyValidationValue(null);
		$this->assertFalse($captcha->validate());
	}

	public function testValidateReturnsFalseWhenValidationPropertyValueIsEmptyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSpyValidationValue('');
		$this->assertFalse($captcha->validate());
	}

	public function testValidateReturnsTrueWhenValidationPropertyValueIsNonEmpty(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSpyValidationValue('03ANYolqt_valid_token');
		$this->assertTrue($captcha->validate());
	}

	public function testValidateReturnsTrueForArbitraryNonEmptyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSpyValidationValue('x');
		$this->assertTrue($captcha->validate());
	}

	// -----------------------------------------------------------------------
	// getResponseFieldName()
	// -----------------------------------------------------------------------

	public function testGetResponseFieldNameForFirstCaptchaIsBaseName(): void
	{
		[$captcha] = $this->makeSpyWithPage('Captcha1');
		$this->assertSame(TReCaptcha2::ChallengeFieldName, $captcha->getResponseFieldName());
	}

	public function testGetResponseFieldNameForSecondCaptchaHasSuffix(): void
	{
		$page = new TPage();

		$first = new SpyTReCaptcha2();
		$first->setID('Captcha1');
		$page->getControls()->add($first);

		$second = new SpyTReCaptcha2();
		$second->setID('Captcha2');
		$page->getControls()->add($second);

		$expected = TReCaptcha2::ChallengeFieldName . '-1';
		$this->assertSame($expected, $second->getResponseFieldName());
	}

	public function testGetResponseFieldNameFirstCaptchaUnchangedWhenSecondExists(): void
	{
		$page = new TPage();

		$first = new SpyTReCaptcha2();
		$first->setID('Captcha1');
		$page->getControls()->add($first);

		$second = new SpyTReCaptcha2();
		$second->setID('Captcha2');
		$page->getControls()->add($second);

		$this->assertSame(TReCaptcha2::ChallengeFieldName, $first->getResponseFieldName());
	}

	// -----------------------------------------------------------------------
	// onPreRender() — exception paths (no TPageService required)
	// -----------------------------------------------------------------------

	public function testOnPreRenderThrowsWhenSiteKeyIsEmpty(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSecretKey('secret');
		// SiteKey is null (default) — treated as empty string by TConfigurationException check
		$this->expectException(TConfigurationException::class);
		$captcha->onPreRender(null);
	}

	public function testOnPreRenderThrowsWhenSiteKeyIsEmptyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSiteKey('');
		$captcha->setSecretKey('secret');
		$this->expectException(TConfigurationException::class);
		$captcha->onPreRender(null);
	}

	public function testOnPreRenderThrowsWhenSecretKeyIsEmpty(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSiteKey('sitekey');
		// SecretKey is null (default)
		$this->expectException(TConfigurationException::class);
		$captcha->onPreRender(null);
	}

	public function testOnPreRenderThrowsWhenSecretKeyIsEmptyString(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setSiteKey('sitekey');
		$captcha->setSecretKey('');
		$this->expectException(TConfigurationException::class);
		$captcha->onPreRender(null);
	}

	// -----------------------------------------------------------------------
	// onPreRender() — happy path (requires TPageService)
	// -----------------------------------------------------------------------

	public function testOnPreRenderRegistersPostDataFieldWithPage(): void
	{
		$this->withPageService(function (): void {
			[$captcha, $page] = $this->makeSpyWithPage('Cap');
			$captcha->setSiteKey('my-site-key');
			$captcha->setSecretKey('my-secret-key');

			$captcha->onPreRender(null);

			// $_controlsRegisteredForPostData is protected; use reflection to inspect it.
			$ref = new \ReflectionProperty(TPage::class, '_controlsRegisteredForPostData');
			$ref->setAccessible(true);
			$registered = $ref->getValue($page);
			$fieldName = $captcha->getResponseFieldName();
			$this->assertArrayHasKey($fieldName, $registered);
		});
	}

	public function testOnPreRenderRegistersRecaptchaScriptInClientScript(): void
	{
		$this->withPageService(function (): void {
			[$captcha] = $this->makeSpyWithPage('Cap');
			$captcha->setSiteKey('my-site-key');
			$captcha->setSecretKey('my-secret-key');
			$captcha->setLanguage('es');

			$captcha->onPreRender(null);

			$cs = $captcha->getPage()->getClientScript();
			$this->assertTrue($cs->isHeadScriptFileRegistered('grecaptcha2'));
		});
	}

	public function testOnPreRenderIncludesLanguageInScriptUrl(): void
	{
		$this->withPageService(function (): void {
			[$captcha] = $this->makeSpyWithPage('Cap');
			$captcha->setSiteKey('s');
			$captcha->setSecretKey('k');
			$captcha->setLanguage('pt');

			$captcha->onPreRender(null);

			// $_headScriptFiles is private on TClientScriptManager; use reflection to
			// verify the URL contains the correct language query parameter.
			$cs = $captcha->getPage()->getClientScript();
			$ref = new \ReflectionProperty(\Prado\Web\UI\TClientScriptManager::class, '_headScriptFiles');
			$ref->setAccessible(true);
			$headFiles = $ref->getValue($cs);
			$asset = $headFiles['grecaptcha2'];
			$this->assertStringContainsString('hl=pt', $asset->getUrl());
		});
	}

	// -----------------------------------------------------------------------
	// raiseCallbackEvent() — non-stdClass is a no-op
	// -----------------------------------------------------------------------

	public function testRaiseCallbackEventWithNonStdClassParamIsNoop(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setCaptchaResponse('initial');

		$eventParam = $this->makeEventParam('not-an-object');
		$captcha->raiseCallbackEvent($eventParam);

		// Nothing must have changed.
		$this->assertSame('initial', $captcha->getCaptchaResponse());
		$this->assertSame(0, $captcha->getResetCallCount());
	}

	public function testRaiseCallbackEventWithNullParamIsNoop(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setCaptchaResponse('initial');

		$eventParam = $this->makeEventParam(null);
		$captcha->raiseCallbackEvent($eventParam);

		$this->assertSame('initial', $captcha->getCaptchaResponse());
		$this->assertSame(0, $captcha->getResetCallCount());
	}

	public function testRaiseCallbackEventWithArrayParamIsNoop(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$eventParam = $this->makeEventParam(['onCallback' => true]);
		$captcha->raiseCallbackEvent($eventParam);

		$this->assertSame('', $captcha->getCaptchaResponse());
		$this->assertSame(0, $captcha->getResetCallCount());
	}

	// -----------------------------------------------------------------------
	// raiseCallbackEvent() — onCallback path
	// -----------------------------------------------------------------------

	public function testRaiseCallbackEventOnCallbackPathSetsWidgetId(): void
	{
		[$captcha] = $this->makeSpyWithPage();

		$p = $this->makeCallbackParam(true, 'token-xyz', 'g-recaptcha-response', 42);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame(42, $captcha->getWidgetId());
	}

	public function testRaiseCallbackEventOnCallbackPathSetsCaptchaResponse(): void
	{
		[$captcha] = $this->makeSpyWithPage();

		$p = $this->makeCallbackParam(true, 'my-token', 'g-recaptcha-response', 0);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame('my-token', $captcha->getCaptchaResponse());
	}

	public function testRaiseCallbackEventOnCallbackPathDoesNotCallReset(): void
	{
		[$captcha] = $this->makeSpyWithPage();

		$p = $this->makeCallbackParam(true);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame(0, $captcha->getResetCallCount());
	}

	public function testRaiseCallbackEventOnCallbackPathRaisesOnCallbackEvent(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$fired = false;
		$captcha->attachEventHandler('OnCallback', function () use (&$fired): void {
			$fired = true;
		});

		$p = $this->makeCallbackParam(true);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertTrue($fired);
	}

	public function testRaiseCallbackEventOnCallbackFalseFlagDoesNotRaiseOnCallback(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$fired = false;
		$captcha->attachEventHandler('OnCallback', function () use (&$fired): void {
			$fired = true;
		});

		// onCallback property is present (triggers the if block) but is false
		$p = $this->makeCallbackParam(false);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertFalse($fired);
		// State is still set even when the event flag is false
		$this->assertSame('tok123', $captcha->getCaptchaResponse());
	}

	// -----------------------------------------------------------------------
	// raiseCallbackEvent() — onCallbackExpired path
	// -----------------------------------------------------------------------

	public function testRaiseCallbackEventOnCallbackExpiredClearsCaptchaResponse(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setCaptchaResponse('stale-token');

		$p = $this->makeExpiredParam(true);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame('', $captcha->getCaptchaResponse());
	}

	public function testRaiseCallbackEventOnCallbackExpiredCallsReset(): void
	{
		[$captcha] = $this->makeSpyWithPage();

		$p = $this->makeExpiredParam(true);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame(1, $captcha->getResetCallCount());
	}

	public function testRaiseCallbackEventOnCallbackExpiredRaisesOnCallbackExpiredEvent(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$fired = false;
		$captcha->attachEventHandler('OnCallbackExpired', function () use (&$fired): void {
			$fired = true;
		});

		$p = $this->makeExpiredParam(true);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertTrue($fired);
	}

	public function testRaiseCallbackEventOnCallbackExpiredFalseFlagDoesNotRaiseEvent(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$fired = false;
		$captcha->attachEventHandler('OnCallbackExpired', function () use (&$fired): void {
			$fired = true;
		});

		// onCallbackExpired property is present but is false
		$p = $this->makeExpiredParam(false);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertFalse($fired);
		// reset() must still have been called
		$this->assertSame(1, $captcha->getResetCallCount());
	}

	public function testRaiseCallbackEventOnCallbackExpiredDoesNotChangeWidgetId(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setWidgetId(9);

		$p = $this->makeExpiredParam(true);
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame(9, $captcha->getWidgetId());
	}

	// -----------------------------------------------------------------------
	// raiseCallbackEvent() — both paths in same param (neither flag set)
	// -----------------------------------------------------------------------

	public function testRaiseCallbackEventStdClassWithNeitherPropertyIsNoop(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$captcha->setCaptchaResponse('keep');

		// stdClass with no relevant properties
		$p = new \stdClass();
		$captcha->raiseCallbackEvent($this->makeEventParam($p));

		$this->assertSame('keep', $captcha->getCaptchaResponse());
		$this->assertSame(0, $captcha->getResetCallCount());
	}

	// -----------------------------------------------------------------------
	// onCallback() / onCallbackExpired() — direct event raising
	// -----------------------------------------------------------------------

	public function testOnCallbackRaisesOnCallbackEvent(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$fired = false;
		$captcha->attachEventHandler('OnCallback', function () use (&$fired): void {
			$fired = true;
		});

		$captcha->onCallback(null);
		$this->assertTrue($fired);
	}

	public function testOnCallbackExpiredRaisesOnCallbackExpiredEvent(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$fired = false;
		$captcha->attachEventHandler('OnCallbackExpired', function () use (&$fired): void {
			$fired = true;
		});

		$captcha->onCallbackExpired(null);
		$this->assertTrue($fired);
	}

	public function testOnCallbackPassesParameterToHandler(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$received = null;
		$captcha->attachEventHandler('OnCallback', function ($sender, $param) use (&$received): void {
			$received = $param;
		});
		$sentinel = new \stdClass();

		$captcha->onCallback($sentinel);
		$this->assertSame($sentinel, $received);
	}

	public function testOnCallbackExpiredPassesParameterToHandler(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$received = null;
		$captcha->attachEventHandler('OnCallbackExpired', function ($sender, $param) use (&$received): void {
			$received = $param;
		});
		$sentinel = new \stdClass();

		$captcha->onCallbackExpired($sentinel);
		$this->assertSame($sentinel, $received);
	}

	// -----------------------------------------------------------------------
	// getActiveControl() / getClientSide()
	// -----------------------------------------------------------------------

	public function testGetActiveControlReturnsNonNull(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertNotNull($captcha->getActiveControl());
	}

	public function testGetClientSideReturnsNonNull(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertNotNull($captcha->getClientSide());
	}

	public function testGetActiveControlIsBaseActiveCallbackControl(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::class,
			$captcha->getActiveControl()
		);
	}

	public function testGetClientSideIsCallbackClientSide(): void
	{
		[$captcha] = $this->makeSpyWithPage();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TCallbackClientSide::class,
			$captcha->getClientSide()
		);
	}
}
