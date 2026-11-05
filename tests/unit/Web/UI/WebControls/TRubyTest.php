<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TRuby;
use Prado\Web\UI\WebControls\TRubyParenthesis;
use Prado\Web\UI\WebControls\TRubyText;

class TRubyTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testElements()
	{
		foreach ([[new TRuby(), 'ruby'], [new TRubyText(), 'rt'], [new TRubyParenthesis(), 'rp']] as [$control, $tag]) {
			$this->assertInstanceOf(THtmlElement::class, $control);
			$this->assertSame($tag, $control->getTagName());
			$this->assertFalse($control->getIsMutated());
			$control->getControls()->add('x');
			$this->assertSame("<$tag>x</$tag>", $this->render($control));
		}
	}

	public function testDefaults()
	{
		$control = new TRuby();
		$this->assertSame('', $control->getBase());
		$this->assertSame('', $control->getAnnotation());
		$this->assertTrue($control->getShowParentheses());
		$this->assertSame('(', $control->getOpenParenthesis());
		$this->assertSame(')', $control->getCloseParenthesis());
		$this->assertFalse($control->getEncode());
		$this->assertSame('<ruby></ruby>', $this->render($control));
	}

	public function testBaseAndAnnotation()
	{
		$control = new TRuby();
		$control->setLang('ja');
		$control->setBase('漢字');
		$control->setAnnotation('かんじ');
		$this->assertSame('<ruby lang="ja">漢字<rp>(</rp><rt>かんじ</rt><rp>)</rp></ruby>', $this->render($control));
	}

	public function testWithoutParentheses()
	{
		$control = new TRuby();
		$control->setBase('漢字');
		$control->setAnnotation('かんじ');
		$control->setShowParentheses('false');
		$this->assertFalse($control->getShowParentheses());
		$this->assertSame('<ruby>漢字<rt>かんじ</rt></ruby>', $this->render($control));
	}

	public function testCustomParentheses()
	{
		$control = new TRuby();
		$control->setBase('漢字');
		$control->setAnnotation('かんじ');
		$control->setOpenParenthesis('（');
		$control->setCloseParenthesis('）');
		$this->assertSame('<ruby>漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>', $this->render($control));
	}

	public function testChildrenRenderAfterBaseAndAnnotation()
	{
		$control = new TRuby();
		$control->setBase('A');
		$control->setAnnotation('a');
		$control->setShowParentheses(false);
		$rt = new TRubyText();
		$rt->getControls()->add('b');
		$control->getControls()->add('B');
		$control->getControls()->add($rt);
		$this->assertSame('<ruby>A<rt>a</rt>B<rt>b</rt></ruby>', $this->render($control));
	}

	public function testPerCharacterChildren()
	{
		$control = new TRuby();
		foreach (['漢' => 'かん', '字' => 'じ'] as $base => $reading) {
			$rt = new TRubyText();
			$rt->getControls()->add($reading);
			$control->getControls()->add($base);
			$control->getControls()->add($rt);
		}
		$this->assertSame('<ruby>漢<rt>かん</rt>字<rt>じ</rt></ruby>', $this->render($control));
	}

	public function testEncode()
	{
		$control = new TRuby();
		$control->setBase('<b>A</b>');
		$control->setAnnotation('"a"');
		$control->setOpenParenthesis('<');
		$this->assertSame('<ruby><b>A</b><rp><</rp><rt>"a"</rt><rp>)</rp></ruby>', $this->render($control));
		$control->setEncode(true);
		$this->assertSame('<ruby>&lt;b&gt;A&lt;/b&gt;<rp>&lt;</rp><rt>&quot;a&quot;</rt><rp>)</rp></ruby>', $this->render($control));
	}
}
