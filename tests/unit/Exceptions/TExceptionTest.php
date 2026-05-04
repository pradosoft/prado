<?php

use Prado\Exceptions\TException;
use Prado\Exceptions\TConfigurationException;

/**
 * Tests for TException behavior, including old/new style constructors,
 * translation of messages, placeholders, and exception chaining.
 */
class TExceptionTest extends PHPUnit\Framework\TestCase
{
    public function testOldStyleTranslationAndChaining()
    {
        $prev = new \Exception('previous');
        // Old style: first param is message code string
        $ex = new TException('prado_application_singleton_required', null, $prev);

        // The exception should carry the code as the provided error code in getErrorCode()
        $this->assertSame('prado_application_singleton_required', $ex->getErrorCode());
        // The PHP Exception code (second constructor param) should be 0
        $this->assertSame(0, $ex->getCode());
        // Message should be translated using the code with placeholders if any
        $this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());
        // Previous exception should be preserved
        $this->assertNotNull($ex->getPrevious());
        $this->assertInstanceOf(\Exception::class, $ex->getPrevious());
        $this->assertEquals('previous', $ex->getPrevious()->getMessage());
    }

    public function testErrorCodeGetterSetter()
    {
        $ex = new TException('prado_application_singleton_required');
        // Initial code from constructor
        $this->assertEquals('prado_application_singleton_required', $ex->getErrorCode());
        // Set a new error code and verify getter reflects it
        $ex->setErrorCode('custom_error_code');
        $this->assertEquals('custom_error_code', $ex->getErrorCode());
    }

    public function testGetErrorMessageEqualsExceptionMessage()
    {
        $ex = new TException('prado_application_singleton_required');
        // getErrorMessage should align with the Exception's message
        $this->assertSame($ex->getMessage(), $ex->getErrorMessage());
    }

    public function testAddMessageFileIsCallable()
    {
        // Ensure calling addMessageFile with a valid messages file does not throw
        $path = realpath(__DIR__ . '/../../../framework/Exceptions/messages/messages.txt');
        if ($path !== false) {
            TException::addMessageFile($path);
            $this->assertIsString($path);
        } else {
            $this->fail("Could not find path '$path'");
        }
    }
    public function testNewStyleTranslationWithPlaceholders()
    {
        // New style: integer errorCode, followed by message code and args
        $ex = new TException(404, 'prado_component_unknown', 'SomeComponent', 'SomeReason');

        // Code is preserved as 404
        $this->assertSame(404, $ex->getCode());
        // Error code string stored for translation reference
        $this->assertSame('prado_component_unknown', $ex->getErrorCode());
        // Message should be translated with placeholders replaced
        $expectedStart = 'Unknown component type';
        $this->assertStringStartsWith($expectedStart, $ex->getMessage());
        // Ensure placeholders were substituted
        $this->assertStringContainsString('SomeComponent', $ex->getMessage());
        $this->assertStringContainsString('SomeReason', $ex->getMessage());
    }

    public function testChainingWithOldStyleAndThrowable()
    {
        $cause = new \RuntimeException('boom');
        $ex = new TException('prado_using_invalid', null, $cause);
        $this->assertInstanceOf(\RuntimeException::class, $ex->getPrevious());
        $this->assertEquals('boom', $ex->getPrevious()->getMessage());
    }
}
