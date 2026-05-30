<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Exceptions\THttpException;
use Prado\Exceptions\TSystemException;
use Prado\Exceptions\TException;

/**
 * Comprehensive tests for {@see THttpException}: HTTP status code storage, mixed-type
 * inputs, message translation, placeholder substitution, getErrorMessage(),
 * setErrorCode(), getCode() vs getStatusCode() distinction, throw/catch,
 * exception chaining (getPrevious()), and the full inheritance chain.
 */
class THttpExceptionTest extends PHPUnit\Framework\TestCase
{
	// ── Basic construction and status code ───────────────────────────────────

	public function testStatusCodeGetterOnly()
	{
		$ex = new THttpException(500, 'prado_application_singleton_required');
		$this->assertSame(500, $ex->getStatusCode());
	}

	public function testIntStatusCode404()
	{
		$ex = new THttpException(404, 'test message');
		$this->assertSame(404, $ex->getStatusCode());
	}

	public function testIntStatusCode403()
	{
		$ex = new THttpException(403, 'test message');
		$this->assertSame(403, $ex->getStatusCode());
	}

	// ── Constructor accepts mixed (untyped); converts to int ─────────────────

	public function testStatusCodeStringConvertedToInt()
	{
		// setStatusCode($value) has no type hint — accepts any type
		$ex = new THttpException('404', 'test message');
		$this->assertSame(404, $ex->getStatusCode());
	}

	public function testStatusCodeFloatConvertedToInt()
	{
		$ex = new THttpException(404.9, 'test message');
		$this->assertSame(404, $ex->getStatusCode());
	}

	// ── Status code set before parent::__construct ────────────────────────────

	public function testStatusCodeAvailableWithNullMessage()
	{
		// setStatusCodeDirect is invoked before parent::__construct, so the code
		// is always set even when message construction fails or is null.
		$ex = new THttpException(503, null);
		$this->assertSame(503, $ex->getStatusCode());
	}

	// ── Edge-case status codes ────────────────────────────────────────────────

	public function testStatusCodeZero()
	{
		$ex = new THttpException(0, 'test');
		$this->assertSame(0, $ex->getStatusCode());
	}

	public function testStatusCode3xxRange()
	{
		foreach ([301, 302, 304, 307, 308] as $code) {
			$ex = new THttpException($code, 'redirect');
			$this->assertSame($code, $ex->getStatusCode(), "Status code $code not preserved");
		}
	}

	public function testStatusCode1xxRange()
	{
		foreach ([100, 101] as $code) {
			$ex = new THttpException($code, 'info');
			$this->assertSame($code, $ex->getStatusCode(), "Status code $code not preserved");
		}
	}

	public function testNonStandardLargeStatusCode()
	{
		$ex = new THttpException(999, 'custom error');
		$this->assertSame(999, $ex->getStatusCode());
	}

	public function testNegativeStatusCode()
	{
		// No lower-bound clamping; stored as-is after int cast
		$ex = new THttpException(-1, 'test');
		$this->assertSame(-1, $ex->getStatusCode());
	}

	// ── Message translation and placeholders ─────────────────────────────────

	public function testStatusCodeAndMessageTranslation()
	{
		$ex = new THttpException(404, 'prado_component_unknown', 'MyComponent', 'Missing');

		$this->assertSame(404, $ex->getStatusCode());
		$this->assertEquals('prado_component_unknown', $ex->getErrorCode());
		$this->assertStringStartsWith('Unknown component type', $ex->getMessage());
		$this->assertStringContainsString('MyComponent', $ex->getMessage());
		$this->assertStringContainsString('Missing', $ex->getMessage());
	}

	public function testPlainStringMessagePassedThrough()
	{
		$ex = new THttpException(500, 'Internal Server Error');
		$this->assertStringContainsString('Internal Server Error', $ex->getMessage());
	}

	public function testMessageCodeTranslatedNotRaw()
	{
		$ex = new THttpException(500, 'prado_application_singleton_required');
		$this->assertStringNotContainsString('prado_application_singleton_required', $ex->getMessage());
		$this->assertNotEmpty($ex->getMessage());
	}

	public function testMessageWithMultiplePlaceholders()
	{
		$ex = new THttpException(400, 'prado_component_unknown', 'CompA', 'CompB');
		$this->assertStringContainsString('CompA', $ex->getMessage());
		$this->assertStringContainsString('CompB', $ex->getMessage());
	}

	public function testNullMessageResultsInEmptyMessage()
	{
		$ex = new THttpException(404, null);
		$this->assertSame('', $ex->getMessage());
	}

	public function testErrorCodeHoldsMessageKey()
	{
		$ex = new THttpException(404, 'prado_application_singleton_required');
		$this->assertSame('prado_application_singleton_required', $ex->getErrorCode());
	}

	public function testErrorCodeForPlainStringIsTheStringItself()
	{
		$ex = new THttpException(500, 'plain error text');
		$this->assertSame('plain error text', $ex->getErrorCode());
	}

	// ── getCode() vs getStatusCode() are distinct ─────────────────────────────

	public function testPhpExceptionCodeIsZeroForOldStyleMessage()
	{
		// TException old-style (string first arg) → PHP Exception getCode() = 0
		$ex = new THttpException(404, 'prado_application_singleton_required');
		$this->assertSame(0, $ex->getCode());
	}

	public function testGetCodeDistinctFromGetStatusCode()
	{
		$ex = new THttpException(404, 'prado_application_singleton_required');
		// HTTP status code lives in getStatusCode(), not getCode()
		$this->assertSame(404, $ex->getStatusCode());
		$this->assertNotSame($ex->getStatusCode(), $ex->getCode());
	}

	public function testGetCodeIsZeroAndGetStatusCodeIsZeroWhenStatusCodeIsZero()
	{
		// Both happen to be 0 when the HTTP status code is 0, but for
		// independent reasons: getCode() is 0 because TException old-style
		// always sets PHP code = 0; getStatusCode() is 0 because that was passed.
		$ex = new THttpException(0, 'test');
		$this->assertSame(0, $ex->getCode());
		$this->assertSame(0, $ex->getStatusCode());
	}

	public function testGetCodeIsAlwaysZeroForAnyStatusCode()
	{
		foreach ([200, 301, 400, 401, 403, 404, 500, 503] as $code) {
			$ex = new THttpException($code, 'test');
			$this->assertSame(0, $ex->getCode(), "getCode() should be 0 for status $code");
		}
	}

	// ── Broad HTTP status code coverage ──────────────────────────────────────

	public function testVarious4xxStatusCodes()
	{
		foreach ([400, 401, 403, 404, 405, 409, 422, 429] as $code) {
			$ex = new THttpException($code, 'test');
			$this->assertSame($code, $ex->getStatusCode(), "Status code $code not preserved");
		}
	}

	public function testVarious5xxStatusCodes()
	{
		foreach ([500, 501, 502, 503, 504] as $code) {
			$ex = new THttpException($code, 'test');
			$this->assertSame($code, $ex->getStatusCode(), "Status code $code not preserved");
		}
	}

	// ── getErrorMessage() alias ────────────────────────────────────────────────

	public function testGetErrorMessageReturnsSameAsGetMessage()
	{
		$ex = new THttpException(404, 'prado_application_singleton_required');
		$this->assertSame($ex->getMessage(), $ex->getErrorMessage());
	}

	public function testGetErrorMessageWithNullMessage()
	{
		$ex = new THttpException(404, null);
		$this->assertSame('', $ex->getErrorMessage());
	}

	public function testGetErrorMessageWithTranslatedCode()
	{
		$ex = new THttpException(500, 'prado_application_singleton_required');
		$this->assertNotEmpty($ex->getErrorMessage());
		$this->assertSame($ex->getMessage(), $ex->getErrorMessage());
	}

	public function testGetErrorMessageWithPlainString()
	{
		$ex = new THttpException(500, 'Service temporarily unavailable');
		$this->assertStringContainsString('Service temporarily unavailable', $ex->getErrorMessage());
		$this->assertSame($ex->getMessage(), $ex->getErrorMessage());
	}

	// ── setErrorCode() mutation ───────────────────────────────────────────────

	public function testSetErrorCodeMutatesGetErrorCode()
	{
		$ex = new THttpException(404, 'prado_application_singleton_required');
		$this->assertSame('prado_application_singleton_required', $ex->getErrorCode());
		$ex->setErrorCode('custom_error_code');
		$this->assertSame('custom_error_code', $ex->getErrorCode());
	}

	public function testSetErrorCodeDoesNotChangeMessage()
	{
		// setErrorCode() only updates the stored key; getMessage() is fixed at construction.
		$ex = new THttpException(404, 'prado_application_singleton_required');
		$originalMessage = $ex->getMessage();
		$ex->setErrorCode('something_else');
		$this->assertSame($originalMessage, $ex->getMessage());
	}

	public function testSetErrorCodeToEmptyString()
	{
		$ex = new THttpException(404, 'prado_application_singleton_required');
		$ex->setErrorCode('');
		$this->assertSame('', $ex->getErrorCode());
	}

	// ── Throw and catch ───────────────────────────────────────────────────────

	public function testThrowAndCatch()
	{
		$this->expectException(THttpException::class);
		throw new THttpException(404, 'Not Found');
	}

	public function testThrowAndCatchPreservesStatusCode()
	{
		try {
			throw new THttpException(404, 'prado_application_singleton_required');
		} catch (THttpException $ex) {
			$this->assertSame(404, $ex->getStatusCode());

			return;
		}
		$this->fail('THttpException was not caught');
	}

	public function testThrowAndCatchPreservesMessage()
	{
		try {
			throw new THttpException(500, 'prado_application_singleton_required');
		} catch (THttpException $ex) {
			$this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());

			return;
		}
		$this->fail('THttpException was not caught');
	}

	public function testThrowAndCatchPreservesErrorCode()
	{
		try {
			throw new THttpException(404, 'prado_application_singleton_required');
		} catch (THttpException $ex) {
			$this->assertSame('prado_application_singleton_required', $ex->getErrorCode());

			return;
		}
		$this->fail('THttpException was not caught');
	}

	public function testCaughtAsTException()
	{
		$caught = null;
		try {
			throw new THttpException(500, 'test');
		} catch (TException $ex) {
			$caught = $ex;
		}
		$this->assertNotNull($caught);
		$this->assertInstanceOf(THttpException::class, $caught);
	}

	public function testCaughtAsException()
	{
		$caught = null;
		try {
			throw new THttpException(500, 'test');
		} catch (\Exception $ex) {
			$caught = $ex;
		}
		$this->assertNotNull($caught);
		$this->assertInstanceOf(THttpException::class, $caught);
	}

	public function testCaughtAsThrowable()
	{
		$caught = null;
		try {
			throw new THttpException(500, 'test');
		} catch (\Throwable $ex) {
			$caught = $ex;
		}
		$this->assertNotNull($caught);
		$this->assertInstanceOf(THttpException::class, $caught);
	}

	// ── Exception chaining ────────────────────────────────────────────────────

	public function testGetPreviousIsNullWhenNotProvided()
	{
		$ex = new THttpException(404, 'test');
		$this->assertNull($ex->getPrevious());
	}

	public function testExceptionChainingWithThrowable()
	{
		$prev = new \RuntimeException('previous cause');
		$ex = new THttpException(404, 'prado_application_singleton_required', $prev);
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testExceptionChainingPreservesStatusCode()
	{
		$prev = new \RuntimeException('previous');
		$ex = new THttpException(404, 'prado_application_singleton_required', $prev);
		$this->assertSame(404, $ex->getStatusCode());
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testExceptionChainingPreservesMessage()
	{
		$prev = new \RuntimeException('previous');
		$ex = new THttpException(404, 'prado_application_singleton_required', $prev);
		$this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testExceptionChainingWithNullMessage()
	{
		$prev = new \RuntimeException('previous');
		$ex = new THttpException(503, null, $prev);
		$this->assertSame($prev, $ex->getPrevious());
		$this->assertSame(503, $ex->getStatusCode());
		$this->assertSame('', $ex->getMessage());
	}

	public function testExceptionChainingWithPlaceholders()
	{
		$prev = new \LogicException('root cause');
		$ex = new THttpException(400, 'prado_component_unknown', 'CompA', 'CompB', $prev);
		$this->assertSame($prev, $ex->getPrevious());
		$this->assertStringContainsString('CompA', $ex->getMessage());
		$this->assertStringContainsString('CompB', $ex->getMessage());
	}

	public function testExceptionChainingWithPreviousTException()
	{
		$prev = new TException('prado_application_singleton_required');
		$ex = new THttpException(500, 'prado_application_singleton_required', $prev);
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testPreviousChainedExceptionIsAccessibleAfterThrowCatch()
	{
		$prev = new \InvalidArgumentException('root');
		try {
			throw new THttpException(422, 'test', $prev);
		} catch (THttpException $ex) {
			$this->assertSame($prev, $ex->getPrevious());
			$this->assertSame(422, $ex->getStatusCode());

			return;
		}
		$this->fail('Exception not caught');
	}

	// ── instanceof ────────────────────────────────────────────────────────────

	public function testIsInstanceOfTSystemException()
	{
		$this->assertInstanceOf(TSystemException::class, new THttpException(500, 'test'));
	}

	public function testIsInstanceOfTException()
	{
		$this->assertInstanceOf(TException::class, new THttpException(500, 'test'));
	}

	public function testIsInstanceOfException()
	{
		$this->assertInstanceOf(\Exception::class, new THttpException(500, 'test'));
	}

	public function testIsInstanceOfThrowable()
	{
		$this->assertInstanceOf(\Throwable::class, new THttpException(500, 'test'));
	}

	// ── Visibility / encapsulation ────────────────────────────────────────────

	public function testGetStatusCodeDirectIsProtected()
	{
		$ref = PradoUnit::reflectionMethod(THttpException::class, 'getStatusCodeDirect');
		$this->assertTrue($ref->isProtected());
	}

	public function testSetStatusCodeDirectIsProtected()
	{
		$ref = PradoUnit::reflectionMethod(THttpException::class, 'setStatusCodeDirect');
		$this->assertTrue($ref->isProtected());
	}

	public function testStatusCodePropertyIsPrivate()
	{
		$ref = PradoUnit::reflectionProperty(THttpException::class, '_statusCode');
		$this->assertTrue($ref->isPrivate());
	}

	public function testGetStatusCodeIsPublic()
	{
		$ref = PradoUnit::reflectionMethod(THttpException::class, 'getStatusCode');
		$this->assertTrue($ref->isPublic());
	}

	// ── Multiple instances are independent ────────────────────────────────────

	public function testMultipleInstancesHaveIndependentStatusCodes()
	{
		$ex1 = new THttpException(404, 'test');
		$ex2 = new THttpException(500, 'test');
		$ex3 = new THttpException(403, 'test');
		$this->assertSame(404, $ex1->getStatusCode());
		$this->assertSame(500, $ex2->getStatusCode());
		$this->assertSame(403, $ex3->getStatusCode());
	}

	public function testMultipleInstancesHaveIndependentPrevious()
	{
		$prev1 = new \RuntimeException('first');
		$prev2 = new \RuntimeException('second');
		$ex1 = new THttpException(404, 'test', $prev1);
		$ex2 = new THttpException(500, 'test', $prev2);
		$ex3 = new THttpException(403, 'test');
		$this->assertSame($prev1, $ex1->getPrevious());
		$this->assertSame($prev2, $ex2->getPrevious());
		$this->assertNull($ex3->getPrevious());
	}

	public function testMultipleInstancesHaveIndependentErrorCodes()
	{
		$ex1 = new THttpException(404, 'prado_application_singleton_required');
		$ex2 = new THttpException(500, 'prado_component_unknown', 'X', 'Y');
		$ex1->setErrorCode('modified');
		$this->assertSame('modified', $ex1->getErrorCode());
		$this->assertSame('prado_component_unknown', $ex2->getErrorCode());
	}
}
