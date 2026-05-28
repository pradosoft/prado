<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/../PradoUnitRequires.php';

use Prado\Exceptions\TExitException;
use Prado\Exceptions\TSystemException;
use Prado\Exceptions\TException;

/**
 * Comprehensive tests for {@see TExitException}: exit code storage and edge values,
 * message translation, variadic placeholders, getErrorMessage(), setErrorCode(),
 * throw/catch, exception chaining (getPrevious()), and the full inheritance chain.
 */
class TExitExceptionTest extends PHPUnit\Framework\TestCase
{
	// ── Construction and exit code ────────────────────────────────────────────

	public function testDefaultExitCodeIsZero()
	{
		$ex = new TExitException();
		$this->assertSame(0, $ex->getExitCode());
	}

	public function testDefaultMessageIsEmpty()
	{
		$ex = new TExitException();
		// null message → translateErrorMessage('') → ''
		$this->assertSame('', $ex->getMessage());
	}

	public function testExitCodeGetter()
	{
		$ex = new TExitException(9, 'test');
		$this->assertSame(9, $ex->getExitCode());
	}

	public function testExitCodeAndMessagePropagation()
	{
		// Use a known message code so translation occurs consistently
		$ex = new TExitException(7, 'prado_application_singleton_required');
		$this->assertSame(7, $ex->getExitCode());
		// Message should come from translation of the provided code
		$this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());
	}

	public function testNegativeExitCode()
	{
		$ex = new TExitException(-1);
		$this->assertSame(-1, $ex->getExitCode());
	}

	public function testLargeExitCode()
	{
		$ex = new TExitException(255);
		$this->assertSame(255, $ex->getExitCode());
	}

	public function testExitCodeZeroExplicit()
	{
		$ex = new TExitException(0, 'test message');
		$this->assertSame(0, $ex->getExitCode());
	}

	public function testPhpIntMaxExitCode()
	{
		$ex = new TExitException(PHP_INT_MAX);
		$this->assertSame(PHP_INT_MAX, $ex->getExitCode());
	}

	public function testPhpIntMinExitCode()
	{
		$ex = new TExitException(PHP_INT_MIN);
		$this->assertSame(PHP_INT_MIN, $ex->getExitCode());
	}

	// ── Ordering: exitCode stored before parent::__construct ──────────────────

	public function testExitCodeAvailableBeforeMessageTranslation()
	{
		// setExitCodeDirect is called before parent::__construct, so the code
		// is always set even when message construction fails or is null.
		$ex = new TExitException(42, null);
		$this->assertSame(42, $ex->getExitCode());
	}

	public function testExitCodeAvailableWithNonTranslatableMessage()
	{
		$ex = new TExitException(3, 'plain string message no translation');
		$this->assertSame(3, $ex->getExitCode());
	}

	// ── Message handling ──────────────────────────────────────────────────────

	public function testNullMessageResultsInEmptyMessage()
	{
		$ex = new TExitException(1, null);
		$this->assertSame('', $ex->getMessage());
	}

	public function testPlainStringMessagePassedThrough()
	{
		$ex = new TExitException(1, 'Graceful shutdown');
		$this->assertStringContainsString('Graceful shutdown', $ex->getMessage());
	}

	public function testMessageCodeTranslated()
	{
		$ex = new TExitException(0, 'prado_application_singleton_required');
		// Should be translated, not the raw code
		$this->assertStringNotContainsString('prado_application_singleton_required', $ex->getMessage());
		$this->assertNotEmpty($ex->getMessage());
	}

	public function testVariadicPlaceholdersReplacedInMessage()
	{
		// prado_component_unknown uses {0} and {1}
		$ex = new TExitException(2, 'prado_component_unknown', 'MyClass', 'SomeContext');
		$this->assertStringContainsString('MyClass', $ex->getMessage());
		$this->assertStringContainsString('SomeContext', $ex->getMessage());
	}

	public function testErrorCodeHoldsMessageKey()
	{
		$ex = new TExitException(5, 'prado_application_singleton_required');
		$this->assertSame('prado_application_singleton_required', $ex->getErrorCode());
	}

	public function testErrorCodeForPlainStringIsTheStringItself()
	{
		$ex = new TExitException(5, 'some plain text');
		$this->assertSame('some plain text', $ex->getErrorCode());
	}

	public function testErrorCodeIsNullStringWhenMessageIsNull()
	{
		$ex = new TExitException(1, null);
		// TException old-style: null errorCode → stored as null
		$this->assertNull($ex->getErrorCode());
	}

	public function testPhpExceptionCodeIsZeroForOldStyleMessage()
	{
		// TException old-style (string as first arg to parent) → PHP getCode() = 0
		$ex = new TExitException(7, 'prado_application_singleton_required');
		$this->assertSame(0, $ex->getCode());
	}

	public function testPhpExceptionCodeIsZeroEvenForNullMessage()
	{
		$ex = new TExitException(5, null);
		$this->assertSame(0, $ex->getCode());
	}

	// ── getErrorMessage() alias ────────────────────────────────────────────────

	public function testGetErrorMessageReturnsSameAsGetMessage()
	{
		$ex = new TExitException(1, 'prado_application_singleton_required');
		$this->assertSame($ex->getMessage(), $ex->getErrorMessage());
	}

	public function testGetErrorMessageWithNullMessage()
	{
		$ex = new TExitException(0, null);
		$this->assertSame('', $ex->getErrorMessage());
	}

	public function testGetErrorMessageWithPlainString()
	{
		$ex = new TExitException(1, 'Shutdown requested');
		$this->assertStringContainsString('Shutdown requested', $ex->getErrorMessage());
		$this->assertSame($ex->getMessage(), $ex->getErrorMessage());
	}

	public function testGetErrorMessageWithTranslatedCode()
	{
		$ex = new TExitException(0, 'prado_application_singleton_required');
		$this->assertNotEmpty($ex->getErrorMessage());
		$this->assertSame($ex->getMessage(), $ex->getErrorMessage());
	}

	// ── setErrorCode() mutation ───────────────────────────────────────────────

	public function testSetErrorCodeMutatesGetErrorCode()
	{
		$ex = new TExitException(1, 'prado_application_singleton_required');
		$this->assertSame('prado_application_singleton_required', $ex->getErrorCode());
		$ex->setErrorCode('new_error_code');
		$this->assertSame('new_error_code', $ex->getErrorCode());
	}

	public function testSetErrorCodeDoesNotChangeMessage()
	{
		// setErrorCode() only updates the stored code; getMessage() is fixed at construction.
		$ex = new TExitException(1, 'prado_application_singleton_required');
		$originalMessage = $ex->getMessage();
		$ex->setErrorCode('something_else');
		$this->assertSame($originalMessage, $ex->getMessage());
	}

	public function testSetErrorCodeToEmptyString()
	{
		$ex = new TExitException(1, 'prado_application_singleton_required');
		$ex->setErrorCode('');
		$this->assertSame('', $ex->getErrorCode());
	}

	// ── Throw and catch ───────────────────────────────────────────────────────

	public function testThrowAndCatch()
	{
		$this->expectException(TExitException::class);
		throw new TExitException(1, 'Graceful shutdown');
	}

	public function testThrowAndCatchPreservesExitCode()
	{
		try {
			throw new TExitException(42, 'test');
		} catch (TExitException $ex) {
			$this->assertSame(42, $ex->getExitCode());

			return;
		}
		$this->fail('TExitException was not caught');
	}

	public function testThrowAndCatchPreservesMessage()
	{
		try {
			throw new TExitException(1, 'prado_application_singleton_required');
		} catch (TExitException $ex) {
			$this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());

			return;
		}
		$this->fail('TExitException was not caught');
	}

	public function testThrowAndCatchPreservesErrorCode()
	{
		try {
			throw new TExitException(1, 'prado_application_singleton_required');
		} catch (TExitException $ex) {
			$this->assertSame('prado_application_singleton_required', $ex->getErrorCode());

			return;
		}
		$this->fail('TExitException was not caught');
	}

	public function testCaughtAsTException()
	{
		$caught = null;
		try {
			throw new TExitException(5, 'test');
		} catch (TException $ex) {
			$caught = $ex;
		}
		$this->assertNotNull($caught);
		$this->assertInstanceOf(TExitException::class, $caught);
	}

	public function testCaughtAsException()
	{
		$caught = null;
		try {
			throw new TExitException(3, 'test');
		} catch (\Exception $ex) {
			$caught = $ex;
		}
		$this->assertNotNull($caught);
		$this->assertInstanceOf(TExitException::class, $caught);
	}

	public function testCaughtAsThrowable()
	{
		$caught = null;
		try {
			throw new TExitException(2, 'test');
		} catch (\Throwable $ex) {
			$caught = $ex;
		}
		$this->assertNotNull($caught);
		$this->assertInstanceOf(TExitException::class, $caught);
	}

	// ── Exception chaining ────────────────────────────────────────────────────

	public function testGetPreviousIsNullWhenNotProvided()
	{
		$ex = new TExitException(1, 'test');
		$this->assertNull($ex->getPrevious());
	}

	public function testGetPreviousIsNullForDefaultConstructor()
	{
		$ex = new TExitException();
		$this->assertNull($ex->getPrevious());
	}

	public function testExceptionChainingWithThrowable()
	{
		$prev = new \RuntimeException('previous cause');
		$ex = new TExitException(1, 'prado_application_singleton_required', $prev);
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testExceptionChainingPreservesExitCode()
	{
		$prev = new \RuntimeException('previous');
		$ex = new TExitException(7, 'prado_application_singleton_required', $prev);
		$this->assertSame(7, $ex->getExitCode());
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testExceptionChainingPreservesMessage()
	{
		$prev = new \RuntimeException('previous');
		$ex = new TExitException(1, 'prado_application_singleton_required', $prev);
		$this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testExceptionChainingWithNullMessage()
	{
		$prev = new \RuntimeException('previous');
		$ex = new TExitException(1, null, $prev);
		$this->assertSame($prev, $ex->getPrevious());
		$this->assertSame(1, $ex->getExitCode());
		$this->assertSame('', $ex->getMessage());
	}

	public function testExceptionChainingWithPlaceholders()
	{
		$prev = new \LogicException('root cause');
		$ex = new TExitException(2, 'prado_component_unknown', 'CompA', 'CompB', $prev);
		$this->assertSame($prev, $ex->getPrevious());
		$this->assertStringContainsString('CompA', $ex->getMessage());
		$this->assertStringContainsString('CompB', $ex->getMessage());
	}

	public function testExceptionChainingWithPreviousTException()
	{
		$prev = new TException('prado_application_singleton_required');
		$ex = new TExitException(1, 'prado_application_singleton_required', $prev);
		$this->assertSame($prev, $ex->getPrevious());
	}

	public function testPreviousChainedExceptionIsAccessibleAfterThrowCatch()
	{
		$prev = new \InvalidArgumentException('root');
		try {
			throw new TExitException(3, 'test', $prev);
		} catch (TExitException $ex) {
			$this->assertSame($prev, $ex->getPrevious());
			$this->assertSame(3, $ex->getExitCode());

			return;
		}
		$this->fail('Exception not caught');
	}

	// ── instanceof ────────────────────────────────────────────────────────────

	public function testIsInstanceOfTSystemException()
	{
		$this->assertInstanceOf(TSystemException::class, new TExitException());
	}

	public function testIsInstanceOfTException()
	{
		$this->assertInstanceOf(TException::class, new TExitException());
	}

	public function testIsInstanceOfException()
	{
		$this->assertInstanceOf(\Exception::class, new TExitException());
	}

	public function testIsInstanceOfThrowable()
	{
		$this->assertInstanceOf(\Throwable::class, new TExitException());
	}

	// ── Visibility / encapsulation ────────────────────────────────────────────

	public function testGetExitCodeDirectIsProtected()
	{
		$ref = PradoUnit::reflectionMethod(TExitException::class, 'getExitCodeDirect');
		$this->assertTrue($ref->isProtected());
	}

	public function testSetExitCodeDirectIsProtected()
	{
		$ref = PradoUnit::reflectionMethod(TExitException::class, 'setExitCodeDirect');
		$this->assertTrue($ref->isProtected());
	}

	public function testExitCodePropertyIsPrivate()
	{
		$ref = PradoUnit::reflectionProperty(TExitException::class, '_exitCode');
		$this->assertTrue($ref->isPrivate());
	}

	public function testGetExitCodeIsPublic()
	{
		$ref = PradoUnit::reflectionMethod(TExitException::class, 'getExitCode');
		$this->assertTrue($ref->isPublic());
	}

	// ── Multiple instances are independent ────────────────────────────────────

	public function testMultipleInstancesHaveIndependentExitCodes()
	{
		$ex1 = new TExitException(1);
		$ex2 = new TExitException(2);
		$ex3 = new TExitException(0);
		$this->assertSame(1, $ex1->getExitCode());
		$this->assertSame(2, $ex2->getExitCode());
		$this->assertSame(0, $ex3->getExitCode());
	}

	public function testMultipleInstancesHaveIndependentPrevious()
	{
		$prev1 = new \RuntimeException('first');
		$prev2 = new \RuntimeException('second');
		$ex1 = new TExitException(1, 'test', $prev1);
		$ex2 = new TExitException(2, 'test', $prev2);
		$ex3 = new TExitException(3, 'test');
		$this->assertSame($prev1, $ex1->getPrevious());
		$this->assertSame($prev2, $ex2->getPrevious());
		$this->assertNull($ex3->getPrevious());
	}

	public function testMultipleInstancesHaveIndependentErrorCodes()
	{
		$ex1 = new TExitException(1, 'prado_application_singleton_required');
		$ex2 = new TExitException(2, 'prado_component_unknown', 'X', 'Y');
		$ex1->setErrorCode('modified');
		$this->assertSame('modified', $ex1->getErrorCode());
		$this->assertSame('prado_component_unknown', $ex2->getErrorCode());
	}
}
