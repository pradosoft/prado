<?php

use Prado\Exceptions\TExitException;

/**
 * Tests for TExitException exit code handling and message propagation.
 */
class TExitExceptionTest extends PHPUnit\Framework\TestCase
{
    public function testExitCodeAndMessagePropagation()
    {
        // Use a known message code so translation occurs consistently
        $ex = new TExitException(7, 'prado_application_singleton_required');
        $this->assertSame(7, $ex->getExitCode());
        // Message should come from translation of the provided code
        $this->assertStringStartsWith('Prado.Application must only be set once', $ex->getMessage());
    }

    public function testExitCodeGetter()
    {
        $ex = new \Prado\Exceptions\TExitException(9, 'test');
        $this->assertEquals(9, $ex->getExitCode());
    }
}
