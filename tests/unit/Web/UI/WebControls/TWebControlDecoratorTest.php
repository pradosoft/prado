<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TWebControlDecorator;

class TWebControlDecoratorTestTemplate implements ITemplate
{
	public function __construct(private string $text)
	{
	}

	public function instantiateIn($parent)
	{
		$literal = new TLiteral();
		$literal->setText($this->text);
		$parent->getControls()->add($literal);
	}

	public function getIncludedFiles()
	{
		return [];
	}
}

class TWebControlDecoratorTest extends TestCase
{
	private function texts(TControl $control): array
	{
		$texts = [];
		foreach ($control->getControls() as $child) {
			$tw = new TTextWriter();
			$child->renderControl(new THtmlWriter($tw));
			$texts[] = is_string($child) ? $child : $tw->flush();
		}
		return $texts;
	}

	private function createControl(): array
	{
		$page = new TPage();
		$parent = new TPanel();
		$page->getControls()->add($parent);
		$control = new TPanel();
		$parent->getControls()->add(new TLiteral());
		$parent->getControls()->add($control);
		$parent->getControls()->add(new TLiteral());
		$control->getControls()->add(new TLiteral());
		return [$control, $parent, $page];
	}

	private function setAllTemplates(TWebControlDecorator $decorator): void
	{
		$decorator->setPreTagTemplate(new TWebControlDecoratorTestTemplate('pretag'));
		$decorator->setPreContentsTemplate(new TWebControlDecoratorTestTemplate('precontents'));
		$decorator->setPostContentsTemplate(new TWebControlDecoratorTestTemplate('postcontents'));
		$decorator->setPostTagTemplate(new TWebControlDecoratorTestTemplate('posttag'));
	}

	public function testTextsAndUseState()
	{
		$decorator = new TWebControlDecorator(new TPanel());
		$this->assertFalse($decorator->getUseState());
		foreach (['PreTag', 'PreContents', 'PostContents', 'PostTag'] as $name) {
			$this->assertSame('', $decorator->{'get' . $name . 'Text'}());
			$this->assertNull($decorator->{'get' . $name . 'Template'}());
			$decorator->{'set' . $name . 'Text'}(12);
			$this->assertSame('12', $decorator->{'get' . $name . 'Text'}());
		}
		$decorator->setUseState('true');
		$this->assertTrue($decorator->getUseState());
	}

	public function testInternalOnlyKeepsTheTagTextsAndTemplates()
	{
		$decorator = new TWebControlDecorator(new TPanel(), true);
		$decorator->setPreTagText('a');
		$decorator->setPostTagText('b');
		$decorator->setPreContentsText('c');
		$decorator->setPostContentsText('d');
		$this->setAllTemplates($decorator);
		$this->assertSame('', $decorator->getPreTagText());
		$this->assertSame('', $decorator->getPostTagText());
		$this->assertNull($decorator->getPreTagTemplate());
		$this->assertNull($decorator->getPostTagTemplate());
		$this->assertSame('c', $decorator->getPreContentsText());
		$this->assertSame('d', $decorator->getPostContentsText());
		$this->assertNotNull($decorator->getPreContentsTemplate());
		$this->assertNotNull($decorator->getPostContentsTemplate());
	}

	public function testAnAppliedSkinKeepsEverything()
	{
		$control = new TPanel();
		PradoUnit::setProp($control, '_flags', TControl::IS_SKIN_APPLIED);
		$decorator = new TWebControlDecorator($control);
		foreach (['PreTag', 'PreContents', 'PostContents', 'PostTag'] as $name) {
			$decorator->{'set' . $name . 'Text'}('x');
			$this->assertSame('', $decorator->{'get' . $name . 'Text'}(), $name);
		}
		$this->setAllTemplates($decorator);
		foreach (['PreTag', 'PreContents', 'PostContents', 'PostTag'] as $name) {
			$this->assertNull($decorator->{'get' . $name . 'Template'}(), $name);
		}
	}

	public function testRenderTexts()
	{
		$decorator = new TWebControlDecorator(new TPanel());
		$decorator->setPreTagText('<a>');
		$decorator->setPreContentsText('<b>');
		$decorator->setPostContentsText('</b>');
		$decorator->setPostTagText('</a>');
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$decorator->renderPreTagText($writer);
		$decorator->renderPreContentsText($writer);
		$decorator->renderPostContentsText($writer);
		$decorator->renderPostTagText($writer);
		$this->assertSame('<a><b></b></a>', $tw->flush());
	}

	public function testInstantiateWithUseStateDecoratesAtOnce()
	{
		[$control, $parent] = $this->createControl();
		$decorator = new TWebControlDecorator($control);
		$this->setAllTemplates($decorator);
		$decorator->setUseState(true);
		$decorator->instantiate();
		$this->assertSame(['precontents', '', 'postcontents'], $this->texts($control));
		$siblings = $parent->getControls();
		$this->assertCount(5, $siblings);
		$this->assertSame(['pretag', 'posttag'], [$this->texts($siblings[1])[0], $this->texts($siblings[3])[0]]);
		$this->assertSame($control, $siblings[2]);
		$this->assertTrue($decorator->ensureTemplateDecoration());
		$this->assertCount(3, $control->getControls());
	}

	public function testInstantiateWaitsForSaveStateComplete()
	{
		[$control, , $page] = $this->createControl();
		$decorator = new TWebControlDecorator($control);
		$decorator->setPostContentsTemplate(new TWebControlDecoratorTestTemplate('postcontents'));
		$decorator->instantiate();
		$this->assertCount(1, $control->getControls());
		$page->onSaveStateComplete(null);
		$this->assertSame(['', 'postcontents'], $this->texts($control));
	}

	public function testInstantiateWithoutTemplatesDoesNothing()
	{
		[$control, , $page] = $this->createControl();
		$decorator = new TWebControlDecorator($control);
		$decorator->instantiate();
		$this->assertFalse($page->hasEventHandler('OnSaveStateComplete') && $page->getEventHandlers('OnSaveStateComplete')->getCount() > 0);
	}

	public function testDecorationAroundAnOuterControl()
	{
		[$control, $parent] = $this->createControl();
		$outer = $parent;
		$decorator = new TWebControlDecorator($control);
		$decorator->setPreTagTemplate(new TWebControlDecoratorTestTemplate('pretag'));
		$decorator->setUseState(true);
		$decorator->instantiate($outer);
		$siblings = $outer->getParent()->getControls();
		$this->assertSame('pretag', $this->texts($outer->getParent())[0]);
		$this->assertSame($outer, $siblings[1]);
	}

	public function testDecorationWithoutAParentStopsAtTheContents()
	{
		$control = new TPanel();
		$decorator = new TWebControlDecorator($control);
		$this->setAllTemplates($decorator);
		$this->assertTrue($decorator->ensureTemplateDecoration());
		$this->assertSame(['precontents', 'postcontents'], $this->texts($control));
	}
}
