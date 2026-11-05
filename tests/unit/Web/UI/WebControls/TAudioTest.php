<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TBaseMedia;
use Prado\Web\UI\WebControls\TAudio;
use Prado\Web\UI\WebControls\TMediaPreload;
use Prado\Web\UI\WebControls\TMediaSource;
use Prado\Web\UI\WebControls\TTextTrack;
use Prado\Web\UI\WebControls\TTextTrackKind;

class TAudioTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TAudio();
	}

	protected function defaultTagName(): string
	{
		return 'audio';
	}

	public function testExtendsTBaseMedia()
	{
		$this->assertInstanceOf(TBaseMedia::class, new TAudio());
	}

	public function testShowControlsDefaultsToTrue()
	{
		$control = new TAudio();
		$this->assertTrue($control->getShowControls());
		$this->assertSame('<audio controls="controls"></audio>', $this->render($control));
		$control->setShowControls('false');
		$this->assertFalse($control->getShowControls());
		$this->assertSame('<audio></audio>', $this->render($control));
	}

	public function testPlaybackAttributes()
	{
		$control = new TAudio();
		$control->setSrc('media.file');
		$control->setAutoPlay(true);
		$control->setLoop(true);
		$control->setMuted(true);
		$control->setPreload('metadata');
		$control->setCrossOrigin(' Anonymous ');
		$this->assertSame('media.file', $control->getSrc());
		$this->assertTrue($control->getAutoPlay());
		$this->assertTrue($control->getLoop());
		$this->assertTrue($control->getMuted());
		$this->assertSame(TMediaPreload::Metadata, $control->getPreload());
		$this->assertSame('anonymous', $control->getCrossOrigin());
		$this->assertSame('<audio src="media.file" controls="controls" autoplay="autoplay" loop="loop" muted="muted" preload="metadata" crossorigin="anonymous"></audio>', $this->render($control));
	}

	public function testPlaybackDefaults()
	{
		$control = new TAudio();
		$this->assertFalse($control->getAutoPlay());
		$this->assertFalse($control->getLoop());
		$this->assertFalse($control->getMuted());
		$this->assertSame(TMediaPreload::NotSet, $control->getPreload());
		$this->assertSame('', $control->getCrossOrigin());
		$control->setPreload(TMediaPreload::None);
		$this->assertStringContainsString('preload="none"', $this->render($control));
	}

	public function testInvalidCrossOriginThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TAudio())->setCrossOrigin('credentials');
	}

	public function testRendersSourcesTracksAndFallback()
	{
		$control = new TAudio();
		$source = new TMediaSource();
		$source->setSrc('a.file');
		$track = new TTextTrack();
		$track->setKind(TTextTrackKind::Captions);
		$control->getControls()->add($source);
		$control->getControls()->add($track);
		$control->getControls()->add('<a href="a.file">Download</a>');
		$this->assertSame('<audio controls="controls"><source src="a.file" /><track kind="captions" /><a href="a.file">Download</a></audio>', $this->render($control));
	}
}
