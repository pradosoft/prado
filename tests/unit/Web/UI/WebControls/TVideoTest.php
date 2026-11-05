<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TBaseMedia;
use Prado\Web\UI\WebControls\TVideo;
use Prado\Web\UI\WebControls\TMediaPreload;
use Prado\Web\UI\WebControls\TMediaSource;
use Prado\Web\UI\WebControls\TTextTrack;
use Prado\Web\UI\WebControls\TTextTrackKind;

class TVideoTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TVideo();
	}

	protected function defaultTagName(): string
	{
		return 'video';
	}

	public function testExtendsTBaseMedia()
	{
		$this->assertInstanceOf(TBaseMedia::class, new TVideo());
	}

	public function testShowControlsDefaultsToTrue()
	{
		$control = new TVideo();
		$this->assertTrue($control->getShowControls());
		$this->assertSame('<video controls="controls"></video>', $this->render($control));
		$control->setShowControls('false');
		$this->assertFalse($control->getShowControls());
		$this->assertSame('<video></video>', $this->render($control));
	}

	public function testPlaybackAttributes()
	{
		$control = new TVideo();
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
		$this->assertSame('<video src="media.file" controls="controls" autoplay="autoplay" loop="loop" muted="muted" preload="metadata" crossorigin="anonymous"></video>', $this->render($control));
	}

	public function testPlaybackDefaults()
	{
		$control = new TVideo();
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
		(new TVideo())->setCrossOrigin('credentials');
	}

	public function testRendersSourcesTracksAndFallback()
	{
		$control = new TVideo();
		$source = new TMediaSource();
		$source->setSrc('a.file');
		$track = new TTextTrack();
		$track->setKind(TTextTrackKind::Captions);
		$control->getControls()->add($source);
		$control->getControls()->add($track);
		$control->getControls()->add('<a href="a.file">Download</a>');
		$this->assertSame('<video controls="controls"><source src="a.file" /><track kind="captions" /><a href="a.file">Download</a></video>', $this->render($control));
	}

	public function testVideoAttributes()
	{
		$control = new TVideo();
		$control->setWidth('640');
		$control->setHeight(360);
		$control->setPoster('intro.jpg');
		$control->setPlaysInline(true);
		$this->assertSame(640, $control->getWidth());
		$this->assertSame(360, $control->getHeight());
		$this->assertSame('intro.jpg', $control->getPoster());
		$this->assertTrue($control->getPlaysInline());
		$this->assertSame('<video controls="controls" width="640" height="360" poster="intro.jpg" playsinline="playsinline"></video>', $this->render($control));
	}

	public function testVideoDefaults()
	{
		$control = new TVideo();
		$this->assertSame(-1, $control->getWidth());
		$this->assertSame(-1, $control->getHeight());
		$this->assertSame('', $control->getPoster());
		$this->assertFalse($control->getPlaysInline());
		$control->setWidth(-3);
		$this->assertSame(-1, $control->getWidth());
	}
}
