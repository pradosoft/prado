<?php

use Prado\Exceptions\TTemplateException;

/**
 * Tests for TTemplateException basic accessors and edge handling.
 */
class TTemplateExceptionTest extends PHPUnit\Framework\TestCase
{
    public function testTemplateProperties()
    {
        $e = new TTemplateException('template_error_code');

        // Template source and file setters/getters
        $e->setTemplateSource('<div>error</div>');
        $this->assertEquals('<div>error</div>', $e->getTemplateSource());

        $e->setTemplateFile('/path/to/template.tpl');
        $this->assertEquals('/path/to/template.tpl', $e->getTemplateFile());

        // Line number setters use integer value parsing
        $e->setLineNumber(12);
        $this->assertEquals(12, $e->getLineNumber());
        // String that can be parsed as int
        $e->setLineNumber('7');
        $this->assertEquals(7, $e->getLineNumber());
    }

    public function testTemplateSourceGetterSetter()
    {
        $e = new TTemplateException('tmpl');
        $e->setTemplateSource('<div>err</div>');
        $this->assertEquals('<div>err</div>', $e->getTemplateSource());
    }

    public function testTemplateFileGetterSetter()
    {
        $e = new TTemplateException('tmpl');
        $e->setTemplateFile('/path/to/template.tpl');
        $this->assertEquals('/path/to/template.tpl', $e->getTemplateFile());
    }

    public function testLineNumberGetterSetterEdgeCases()
    {
        $e = new TTemplateException('tmpl');
        // numeric
        $e->setLineNumber(10);
        $this->assertEquals(10, $e->getLineNumber());
        // string that parses to int
        $e->setLineNumber('15');
        $this->assertEquals(15, $e->getLineNumber());
        // negative values should be cast to int as-is (PHP int casts)
        $e->setLineNumber(-3);
        $this->assertEquals(-3, $e->getLineNumber());
    }
}
