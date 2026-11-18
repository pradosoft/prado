<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\IO\TTextWriter;
use Prado\Web\UI\TCompositeLiteral;
use Prado\Web\UI\TControl;

class TCompositeLiteralTest extends \PHPUnit\Framework\TestCase
{
	private function render(TCompositeLiteral $literal): string
	{
		$writer = new TTextWriter();
		$literal->render($writer);
		return $writer->flush();
	}

	public function testConstructKeepsTextAndEmptiesDynamicItems()
	{
		$literal = new TCompositeLiteral([
			'a',
			[TCompositeLiteral::TYPE_EXPRESSION, '1 + 1'],
			[TCompositeLiteral::TYPE_STATEMENTS, 'echo "s";'],
			[TCompositeLiteral::TYPE_DATABINDING, '"b"'],
			[99, 'ignored'],
			'z',
		]);
		$this->assertSame('az', $this->render($literal));
	}

	public function testConstructWithoutItems()
	{
		$literal = new TCompositeLiteral([]);
		$literal->evaluateDynamicContent();
		$literal->dataBind();
		$this->assertSame('', $this->render($literal));
	}

	public function testContainer()
	{
		$literal = new TCompositeLiteral([]);
		$this->assertNull($literal->getContainer());
		$literal->setContainer($control = new TControl());
		$this->assertSame($control, $literal->getContainer());
	}

	public function testEvaluateDynamicContentInItselfWithoutAContainer()
	{
		$literal = new TCompositeLiteral([
			'<',
			[TCompositeLiteral::TYPE_EXPRESSION, 'get_class($this)'],
			'|',
			[TCompositeLiteral::TYPE_STATEMENTS, 'echo 2 * 3;'],
			'>',
		]);
		$literal->evaluateDynamicContent();
		$this->assertSame('<' . TCompositeLiteral::class . '|6>', $this->render($literal));
	}

	public function testEvaluateDynamicContentInTheContainer()
	{
		$literal = new TCompositeLiteral([[TCompositeLiteral::TYPE_EXPRESSION, '$this->getID()']]);
		$control = new TControl();
		$control->setID('ctl');
		$literal->setContainer($control);
		$literal->evaluateDynamicContent();
		$this->assertSame('ctl', $this->render($literal));
	}

	public function testDataBindInItselfWithoutAContainer()
	{
		$literal = new TCompositeLiteral(['[', [TCompositeLiteral::TYPE_DATABINDING, 'get_class($this)'], ']']);
		$this->assertSame('[]', $this->render($literal));
		$literal->dataBind();
		$this->assertSame('[' . TCompositeLiteral::class . ']', $this->render($literal));
	}

	public function testDataBindInTheContainer()
	{
		$literal = new TCompositeLiteral([[TCompositeLiteral::TYPE_DATABINDING, '$this->getID()']]);
		$control = new TControl();
		$control->setID('ctl');
		$literal->setContainer($control);
		$literal->dataBind();
		$this->assertSame('ctl', $this->render($literal));
	}

	public function testConstructWithOneItemOfAnUnknownType()
	{
		$this->assertSame('', $this->render(new TCompositeLiteral([[99, 'ignored']])));
	}

	public function testEvaluateAndBindInAContainerWithoutDynamicItems()
	{
		$literal = new TCompositeLiteral(['text']);
		$literal->setContainer(new TControl());
		$literal->evaluateDynamicContent();
		$literal->dataBind();
		$this->assertSame('text', $this->render($literal));
	}
}
