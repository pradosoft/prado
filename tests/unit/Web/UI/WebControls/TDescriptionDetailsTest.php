<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TDescriptionDetails;

class TDescriptionDetailsTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TDescriptionDetails();
	}

	protected function defaultTagName(): string
	{
		return 'dd';
	}

	public function testTermDefaultsToEmpty()
	{
		$control = new TDescriptionDetails();
		$this->assertSame('', $control->getTerm());
		$this->assertSame('<dd></dd>', $this->render($control));
	}

	public function testTermRendersBeforeDetails()
	{
		$control = new TDescriptionDetails();
		$control->setID('author');
		$control->setCssClass('value');
		$control->setTerm('Author');
		$control->getControls()->add('Brad');
		$this->assertSame('<dt>Author</dt><dd id="author" class="value">Brad</dd>', $this->render($control));
	}

	public function testEncodeEncodesTerm()
	{
		$control = new TDescriptionDetails();
		$control->setTerm('<b>A</b>');
		$this->assertSame('<dt><b>A</b></dt><dd></dd>', $this->render($control));
		$control->setEncode(true);
		$this->assertSame('<dt>&lt;b&gt;A&lt;/b&gt;</dt><dd></dd>', $this->render($control));
	}
}
