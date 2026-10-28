<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TDel;
use DateTimeImmutable;
use DateTimeZone;
use Prado\Web\UI\WebControls\TBaseEdit;

class TDelTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TDel();
	}

	protected function defaultTagName(): string
	{
		return 'del';
	}

	public function testCiteDefaultsToEmpty()
	{
		$control = new TDel();
		$this->assertSame('', $control->getCite());
		$this->assertSame('<del></del>', $this->render($control));
	}

	public function testCiteRendersTrimmed()
	{
		$control = new TDel();
		$control->setCite(' https://example.com/source?q="a" ');
		$this->assertSame('https://example.com/source?q="a"', $control->getCite());
		$this->assertSame('<del cite="https://example.com/source?q=&quot;a&quot;"></del>', $this->render($control));
	}

	public function testExtendsTBaseEdit()
	{
		$this->assertInstanceOf(TBaseEdit::class, new TDel());
	}

	public function testDateTimeString()
	{
		$control = new TDel();
		$this->assertSame('', $control->getDateTime());
		$control->setDateTime(' 2026-10-01 ');
		$this->assertSame('2026-10-01', $control->getDateTime());
		$this->assertSame('<del datetime="2026-10-01"></del>', $this->render($control));
	}

	public function testDateTimeInterface()
	{
		$control = new TDel();
		$control->setDateTime(new DateTimeImmutable('2026-10-01 12:30:00', new DateTimeZone('+02:00')));
		$this->assertSame('2026-10-01T12:30:00+02:00', $control->getDateTime());
	}

	public function testDateTimeTimestamp()
	{
		$control = new TDel();
		$control->setDateTime(0);
		$this->assertSame('1970-01-01T00:00:00+00:00', $control->getDateTime());
		$control->setDateTime('86400');
		$this->assertSame('1970-01-02T00:00:00+00:00', $control->getDateTime());
	}

	public function testCiteAndDateTimeRender()
	{
		$control = new TDel();
		$control->setID('edit');
		$control->setCite('/changes/12');
		$control->setDateTime('2026-10-01');
		$control->getControls()->add('$40');
		$this->assertSame('<del id="edit" cite="/changes/12" datetime="2026-10-01">$40</del>', $this->render($control));
	}
}
