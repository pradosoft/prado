<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TTextTrack;
use Prado\Web\UI\WebControls\TTextTrackKind;

class TTextTrackTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testDefaults()
	{
		$control = new TTextTrack();
		$this->assertSame(TTextTrackKind::Subtitles, $control->getKind());
		$this->assertSame('', $control->getSrc());
		$this->assertSame('', $control->getSrcLang());
		$this->assertSame('', $control->getLabel());
		$this->assertFalse($control->getIsDefault());
		$this->assertSame('<track kind="subtitles" />', $this->render($control));
	}

	public function testCaptionsTrack()
	{
		$control = new TTextTrack();
		$control->setSrc('intro.en.vtt');
		$control->setKind('captions');
		$control->setSrcLang(' en ');
		$control->setLabel('English');
		$control->setIsDefault('true');
		$this->assertSame(TTextTrackKind::Captions, $control->getKind());
		$this->assertSame('en', $control->getSrcLang());
		$this->assertTrue($control->getIsDefault());
		$this->assertSame('<track kind="captions" src="intro.en.vtt" srclang="en" label="English" default="default" />', $this->render($control));
	}

	public function testTrackAttributeCombinations()
	{
		foreach ([false, true] as $all) {
			foreach ([false, true] as $isDefault) {
				$control = new TTextTrack();
				if ($all) {
					$control->setSrc('a.vtt');
					$control->setSrcLang('en');
					$control->setLabel('English');
				}
				$control->setIsDefault($isDefault);
				$expected = '<track kind="subtitles"'
					. ($all ? ' src="a.vtt" srclang="en" label="English"' : '')
					. ($isDefault ? ' default="default"' : '')
					. ' />';
				$this->assertSame($expected, $this->render($control));
			}
		}
	}

	public function testEachKindRendersLowercase()
	{
		$control = new TTextTrack();
		foreach (['Subtitles', 'Captions', 'Descriptions', 'Chapters', 'Metadata'] as $kind) {
			$control->setKind($kind);
			$this->assertSame('<track kind="' . strtolower($kind) . '" />', $this->render($control));
		}
	}

	public function testInvalidKindThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TTextTrack())->setKind('Lyrics');
	}

	public function testChildrenDoNotRender()
	{
		$control = new TTextTrack();
		$control->getControls()->add('ignored');
		$this->assertSame('<track kind="subtitles" />', $this->render($control));
	}
}
