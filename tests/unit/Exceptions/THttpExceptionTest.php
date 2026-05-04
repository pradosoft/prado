<?php

use Prado\Exceptions\THttpException;

/**
 * Tests for THttpException behavior: status code, translated message, and code.
 */
class THttpExceptionTest extends PHPUnit\Framework\TestCase
{
    public function testStatusCodeAndMessageTranslation()
    {
        $ex = new THttpException(404, 'prado_component_unknown', 'MyComponent', 'Missing');

        // Status code should be preserved
        $this->assertSame(404, $ex->getStatusCode());
        // Exception code should be the message code (from translation path)
        $this->assertEquals('prado_component_unknown', $ex->getErrorCode());
        // Message should be translated with placeholders replaced
        $this->assertStringStartsWith('Unknown component type', $ex->getMessage());
        $this->assertStringContainsString('MyComponent', $ex->getMessage());
        $this->assertStringContainsString('Missing', $ex->getMessage());
    }

    public function testStatusCodeGetterOnly()
    {
        $ex = new THttpException(500, 'prado_application_singleton_required');
        $this->assertEquals(500, $ex->getStatusCode());
    }
}
