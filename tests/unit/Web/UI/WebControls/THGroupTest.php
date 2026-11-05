<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\THGroup;
use Prado\Exceptions\TInvalidDataValueException;

class THGroupTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new THGroup();
	}

	protected function defaultTagName(): string
	{
		return 'hgroup';
	}

	public function testRendersChildrenWithoutHeaderText()
	{
		$control = new THGroup();
		$control->getControls()->add('<h2>Title</h2><p>Sub</p>');
		$this->assertSame('<hgroup><h2>Title</h2><p>Sub</p></hgroup>', $this->render($control));
	}

	public function testHeaderTextRendersHeadingBeforeChildren()
	{
		$control = new THGroup();
		$this->assertSame(1, $control->getHeaderLevel());
		$control->setHeaderText('Prado');
		$control->getControls()->add('<p>Framework</p>');
		$this->assertSame('<hgroup><h1>Prado</h1><p>Framework</p></hgroup>', $this->render($control));
		$control->setHeaderLevel('3');
		$this->assertSame(3, $control->getHeaderLevel());
		$this->assertSame('<hgroup><h3>Prado</h3><p>Framework</p></hgroup>', $this->render($control));
	}

	public function testEncodeEncodesHeaderText()
	{
		$control = new THGroup();
		$control->setHeaderText('A "<b>B</b>"');
		$control->setEncode(true);
		$this->assertSame('<hgroup><h1>A &quot;&lt;b&gt;B&lt;/b&gt;&quot;</h1></hgroup>', $this->render($control));
	}

	public function testHeaderLevelOutOfRangeThrows()
	{
		foreach ([0, 7] as $level) {
			try {
				(new THGroup())->setHeaderLevel($level);
				$this->fail("level $level");
			} catch (TInvalidDataValueException $e) {
				$this->assertStringContainsString('hgroup_headerlevel_invalid', $e->getErrorCode());
			}
		}
	}
}
