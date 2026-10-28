<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TDescriptionList;
use Prado\Web\UI\WebControls\TDescriptionDetails;
use Prado\Web\UI\WebControls\TDescriptionTerm;

class TDescriptionListTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TDescriptionList();
	}

	protected function defaultTagName(): string
	{
		return 'dl';
	}

	public function testRendersGroups()
	{
		$list = new TDescriptionList();
		$author = new TDescriptionDetails();
		$author->setTerm('Author');
		$author->getControls()->add('Brad');
		$editor = new TDescriptionTerm();
		$editor->getControls()->add('Editor');
		$name = new TDescriptionDetails();
		$name->getControls()->add('Qiang');
		$list->getControls()->add($author);
		$list->getControls()->add($editor);
		$list->getControls()->add($name);
		$this->assertSame('<dl><dt>Author</dt><dd>Brad</dd><dt>Editor</dt><dd>Qiang</dd></dl>', $this->render($list));
	}
}
