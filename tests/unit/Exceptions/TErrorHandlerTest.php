<?php

use Prado\Exceptions\TErrorHandler;
use Prado\Prado;
use Prado\Exceptions\TConfigurationException;

/**
 * Tests for TErrorHandler small, isolated behaviors.
 */
class TErrorHandlerTest extends PHPUnit\Framework\TestCase
{
    public function testDefaultErrorTemplatePath()
    {
        $handler = new TErrorHandler();
        $path = $handler->getErrorTemplatePath();
        $this->assertIsString($path);
        $this->assertStringEndsWith('/Exceptions/templates', $path);
    }

    public function testSetErrorTemplatePathInvalidThrows()
    {
        $handler = new TErrorHandler();
        $this->expectException(TConfigurationException::class);
        // Intentionally invalid namespace to trigger exception
        $handler->setErrorTemplatePath('Prado\NonExistentNamespace\ThatDoesNotExist');
    }

    public function testSetErrorTemplatePathValidNoException()
    {
        $handler = new TErrorHandler();
        // Should not throw for a valid framework namespace; this should resolve to a real path
        $handler->setErrorTemplatePath('Prado\\Exceptions');
        $expectedPath = Prado::getPathOfNamespace('Prado\\Exceptions');
        $this->assertEquals($expectedPath, $handler->getErrorTemplatePath());
    }

    public function testErrorTemplatePathGetterAfterSetter()
    {
        $handler = new TErrorHandler();
        // Resolve namespace path using Prado helper
        $expected = Prado::getPathOfNamespace('Prado\\Exceptions');
        $handler->setErrorTemplatePath('Prado\\Exceptions');
        $this->assertEquals($expected, $handler->getErrorTemplatePath());
    }
}
