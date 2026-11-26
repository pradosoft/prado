<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveImage;
use Prado\Web\UI\ActiveControls\TActiveImageButton;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;

/**
 * TActiveImageButton and TActiveImage update the image attributes in a callback.
 */
class TActiveImageButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	public static function imageClassProvider(): array
	{
		return [
			'TActiveImageButton' => [TActiveImageButton::class],
			'TActiveImage' => [TActiveImage::class],
		];
	}

	public static function attributeProvider(): array
	{
		$rows = [];
		foreach (self::imageClassProvider() as $name => [$class]) {
			$rows[$name . ' AlternateText'] = [$class, 'AlternateText', 'Photo', 'alt'];
			$rows[$name . ' ImageAlign'] = [$class, 'ImageAlign', 'middle', 'align'];
			$rows[$name . ' ImageUrl'] = [$class, 'ImageUrl', '/a.png', 'src'];
			$rows[$name . ' DescriptionUrl'] = [$class, 'DescriptionUrl', '/a.html', 'longdesc'];
		}
		return $rows;
	}

	private function createImage(string $class, bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$image = $page->addControl(new $class(), 'Pic');
		return [$page, $image];
	}

	/**
	 * @dataProvider attributeProvider
	 */
	public function testSetPropertyUpdatesTheAttributeOnce(string $class, string $property, string $value, string $attribute)
	{
		[$page, $image] = $this->createImage($class);
		$image->{'set' . $property}($value);
		$image->{'set' . $property}($value);
		$this->assertSame([['Prado.Element.setAttribute' => ['Pic', $attribute, $value]]], $page->getClientFunctions());
		$this->assertSame($value, $image->{'get' . $property}());
	}

	/**
	 * @dataProvider attributeProvider
	 */
	public function testSetPropertyOutsideACallbackUpdatesNothing(string $class, string $property, string $value)
	{
		[$page, $image] = $this->createImage($class, false);
		$image->{'set' . $property}($value);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame($value, $image->{'get' . $property}());
	}

	public function testClientSideIsTheActiveControlClientSide()
	{
		$button = new TActiveImageButton();
		$this->assertInstanceOf(TCallbackClientSide::class, $button->getClientSide());
		$this->assertSame($button->getActiveControl()->getClientSide(), $button->getClientSide());
	}

	public function testRaiseCallbackEventClicksAndRaisesOnCallback()
	{
		[, $button] = $this->createImage(TActiveImageButton::class);
		$button->setCausesValidation(false);
		$events = [];
		$button->attachEventHandler('OnClick', function () use (&$events) {
			$events[] = 'click';
		});
		$button->attachEventHandler('OnCallback', function ($sender, $param) use (&$events) {
			$events[] = 'callback:' . $param->getCallbackParameter();
		});
		$button->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['click', 'callback:p'], $events);
	}

	public function testRenderRegistersTheCallbackControl()
	{
		[$page, $button] = $this->createImage(TActiveImageButton::class);
		$button->setImageUrl('/a.png');
		$html = $this->render($button);
		$this->assertStringContainsString('id="Pic"', $html);
		$this->assertStringNotContainsString('onclick', $html);
		$this->assertCount(1, $page->cs->callbackControls);
		[$class, $options] = $page->cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveImageButton', $class);
		$this->assertSame('Pic', $options['EventTarget']);
	}

	public function testRenderOfADefaultButtonRendersNoPostBackScript()
	{
		[$page, $button] = $this->createImage(TActiveImageButton::class);
		$button->setIsDefaultButton(true);
		$this->assertStringNotContainsString('onclick', $this->render($button));
		$this->assertSame([], PradoUnit::getProp($page->cs, '_endScripts'));
		$this->assertCount(1, $page->cs->callbackControls);
	}
}
