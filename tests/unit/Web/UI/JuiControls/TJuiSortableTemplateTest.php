<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\JuiControls\TJuiSortableTemplate;
use Prado\Web\UI\TControl;

/**
 * Unit tests for {@see TJuiSortableTemplate}.
 */
class TJuiSortableTemplateTest extends TestCase
{
	public function testInstantiateInAddsTheMarkup(): void
	{
		$template = new TJuiSortableTemplate('<ul id="L">');
		$this->assertInstanceOf(ITemplate::class, $template);
		$parent = new TControl();
		$template->instantiateIn($parent);
		$template->instantiateIn($parent);
		$this->assertSame(['<ul id="L">', '<ul id="L">'], $parent->getControls()->toArray());
		$this->assertSame([], $template->getIncludedFiles());
	}
}
