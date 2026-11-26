<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\JuiControls\TJuiSelectableTemplate;
use Prado\Web\UI\TControl;

/**
 * Unit tests for {@see TJuiSelectableTemplate}.
 */
class TJuiSelectableTemplateTest extends TestCase
{
	public function testInstantiateInAddsTheMarkup(): void
	{
		$template = new TJuiSelectableTemplate('<ul id="L">');
		$this->assertInstanceOf(ITemplate::class, $template);
		$parent = new TControl();
		$template->instantiateIn($parent);
		$template->instantiateIn($parent);
		$this->assertSame(['<ul id="L">', '<ul id="L">'], $parent->getControls()->toArray());
		$this->assertSame([], $template->getIncludedFiles());
	}
}
