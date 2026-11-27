<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCircleHotSpot;
use Prado\Web\UI\WebControls\THotSpotCollection;
use Prado\Web\UI\WebControls\THotSpotMode;
use Prado\Web\UI\WebControls\TImageMap;
use Prado\Web\UI\WebControls\TImageMapEventParameter;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRectangleHotSpot;

/**
 * Page whose validate() records the validation groups it is asked to validate.
 */
class TImageMapTestPage extends TPage
{
	public array $validated = [];

	public function validate($validationGroup = null)
	{
		$this->validated[] = $validationGroup;
	}
}

class TImageMapTest extends TestCase
{
	use TWebControlRenderTrait;

	private TTestRecordingClientScript $cs;

	/**
	 * Creates an image map with an ID on a page with a recording client script manager.
	 * @param ?TPage $page the page, or null for a new one
	 * @return TImageMap the image map
	 */
	private function onPage(?TPage $page = null): TImageMap
	{
		$page ??= new TPage();
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$map = new TImageMap();
		$map->setID('map');
		$map->setImageUrl('plan.png');
		$page->getControls()->add($map);
		return $map;
	}

	/**
	 * Creates a circle hot spot.
	 * @param ?string $mode the HotSpotMode, or null to leave it NotSet
	 * @param string $text the AlternateText
	 * @return TCircleHotSpot the hot spot
	 */
	private function circle(?string $mode = null, string $text = ''): TCircleHotSpot
	{
		$circle = new TCircleHotSpot();
		$circle->setX(5);
		$circle->setY(5);
		$circle->setRadius(2);
		$circle->setNavigateUrl('/room');
		$circle->setAlternateText($text);
		if ($mode !== null) {
			$circle->setHotSpotMode($mode);
		}
		return $circle;
	}

	public function testDefaults()
	{
		$map = new TImageMap();
		$this->assertSame(THotSpotMode::NotSet, $map->getHotSpotMode());
		$this->assertSame('', $map->getTarget());
		$this->assertInstanceOf(THotSpotCollection::class, $map->getHotSpots());
		$this->assertSame($map->getHotSpots(), $map->getHotSpots());
	}

	public function testSetters()
	{
		$map = new TImageMap();
		$map->setHotSpotMode(THotSpotMode::PostBack);
		$map->setTarget('_top');
		$this->assertSame(THotSpotMode::PostBack, $map->getHotSpotMode());
		$this->assertSame('_top', $map->getTarget());
		$this->expectException(TInvalidDataValueException::class);
		$map->setHotSpotMode('Hover');
	}

	public function testAddParsedObjectAddsOnlyHotSpots()
	{
		$map = new TImageMap();
		$circle = $this->circle();
		$map->addParsedObject($circle);
		$map->addParsedObject('text');
		$this->assertSame([$circle], $map->getHotSpots()->toArray());
	}

	public function testWithoutHotSpotsRendersOnlyTheImage()
	{
		$map = $this->onPage();
		$this->assertSame('<img src="plan.png" alt="" id="map" />', $this->render($map));
	}

	public function testRendersTheImageWithItsClientIDAndTheMap()
	{
		$map = $this->onPage();
		$map->getHotSpots()->add($this->circle(null, 'Kitchen'));
		$this->assertSame(
			"<img src=\"plan.png\" alt=\"\" id=\"map\" usemap=\"#ImageMapmap\" />\n"
			. "<map name=\"ImageMapmap\">\n"
			. "<area shape=\"circle\" coords=\"5,5,2\" href=\"/room\" title=\"Kitchen\" alt=\"Kitchen\" />\n"
			. '</map>',
			$this->render($map)
		);
	}

	public function testImageIdIsTheClientIdInsideANamingContainer()
	{
		$page = new TPage();
		$panel = new \Prado\Web\UI\TTemplateControl();
		$panel->setID('grp');
		$page->getControls()->add($panel);
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$map = new TImageMap();
		$map->setID('map');
		$panel->getControls()->add($map);
		$map->getHotSpots()->add($this->circle());
		$html = $this->render($map);
		$this->assertStringContainsString('id="grp_map" usemap="#ImageMapgrp_map"', $html);
		$this->assertStringNotContainsString('$', $html);
	}

	public function testHotSpotWithoutAlternateTextRendersNoTitleOrAlt()
	{
		$map = $this->onPage();
		$map->getHotSpots()->add($this->circle());
		$this->assertStringContainsString('<area shape="circle" coords="5,5,2" href="/room" />', $this->render($map));
	}

	public function testHotSpotAttributesAccessKeyAndTabIndex()
	{
		$map = $this->onPage();
		$circle = $this->circle(null, 'Kitchen');
		$circle->setAccessKey('k');
		$circle->setTabIndex(2);
		$circle->setAttribute('data-room', 'kitchen');
		$map->getHotSpots()->add($circle);
		$this->assertStringContainsString(
			'<area shape="circle" coords="5,5,2" href="/room" title="Kitchen" alt="Kitchen" accesskey="k" tabindex="2" data-room="kitchen" />',
			$this->render($map)
		);
	}

	public function testImageMapModeAndTargetApplyWithoutChangingTheHotSpots()
	{
		$map = $this->onPage();
		$map->setHotSpotMode(THotSpotMode::Inactive);
		$map->setTarget('_blank');
		$map->getHotSpots()->add($inherits = $this->circle());
		$map->getHotSpots()->add($own = $this->circle(THotSpotMode::Navigate));
		$own->setTarget('_self');

		$html = $this->render($map);
		$this->assertStringContainsString("<area shape=\"circle\" coords=\"5,5,2\" />\n<area shape=\"circle\" coords=\"5,5,2\" href=\"/room\" target=\"_self\" />", $html);
		$this->assertSame(THotSpotMode::NotSet, $inherits->getHotSpotMode());
		$this->assertSame('', $inherits->getTarget());

		$map->setHotSpotMode(THotSpotMode::Navigate);
		$this->assertStringContainsString('<area shape="circle" coords="5,5,2" href="/room" target="_blank" />', $this->render($map));
		$this->assertSame('', $inherits->getTarget());

		$map->setHotSpotMode(THotSpotMode::NotSet);
		$map->setTarget('');
		$this->assertStringContainsString("<area shape=\"circle\" coords=\"5,5,2\" href=\"/room\" />\n<area", $this->render($map));
	}

	public function testPostBackHotSpotsRegisterTheirScript()
	{
		$map = $this->onPage();
		$map->getHotSpots()->add($this->circle());
		$postBack = $this->circle(THotSpotMode::PostBack, 'Hall');
		$postBack->setCausesValidation(false);
		$postBack->setValidationGroup('rooms');
		$map->getHotSpots()->add($postBack);

		$html = $this->render($map);
		$this->assertStringContainsString('<area id="map_1" href="#map_1" shape="circle" coords="5,5,2" title="Hall" alt="Hall" />', $html);
		$this->assertSame([['Prado.WebUI.TImageMap', [
			'EventTarget' => 'map',
			'StopEvent' => true,
			'ID' => 'map_1',
			'EventParameter' => '1',
			'CausesValidation' => false,
			'ValidationGroup' => 'rooms',
		]]], $this->cs->postBackControls);
	}

	public function testImageMapPostBackModeAppliesToHotSpotsWithoutAMode()
	{
		$map = $this->onPage();
		$map->setHotSpotMode(THotSpotMode::PostBack);
		$map->getHotSpots()->add($this->circle());
		$this->assertStringContainsString('<area id="map_0" href="#map_0"', $this->render($map));
		$this->assertCount(1, $this->cs->postBackControls);
	}

	public function testDisabledImageMapRendersInactiveHotSpotsWithoutScript()
	{
		$map = $this->onPage();
		$map->setEnabled(false);
		$map->getHotSpots()->add($this->circle());
		$map->getHotSpots()->add($this->circle(THotSpotMode::PostBack));
		$html = $this->render($map);
		$this->assertSame(2, substr_count($html, '<area shape="circle" coords="5,5,2" />'));
		$this->assertStringNotContainsString('href', $html);
		$this->assertSame([], $this->cs->postBackControls);
	}

	public function testImageMapInADisabledParentRendersDisabledAndInactive()
	{
		$page = new TPage();
		$panel = new TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$this->cs = TTestRecordingClientScript::attachTo($page);
		$map = new TImageMap();
		$map->setImageUrl('plan.png');
		$panel->getControls()->add($map);
		$map->getHotSpots()->add($this->circle(THotSpotMode::PostBack));
		$html = $this->render($map);
		$this->assertStringContainsString('disabled="disabled"', $html);
		$this->assertStringContainsString('<area shape="circle" coords="5,5,2" />', $html);
		$this->assertSame([], $this->cs->postBackControls);
	}

	/**
	 * Creates an image map on a recording page whose hot spots post back.
	 * @return TImageMap the image map
	 */
	private function postBackMap(): TImageMap
	{
		$map = $this->onPage(new TImageMapTestPage());
		$first = $this->circle(THotSpotMode::PostBack);
		$first->setPostBackValue('first');
		$first->setValidationGroup('rooms');
		$map->getHotSpots()->add($first);
		$second = new TRectangleHotSpot();
		$second->setPostBackValue('second');
		$second->setCausesValidation(false);
		$map->getHotSpots()->add($second);
		$map->getHotSpots()->add($this->circle(THotSpotMode::Navigate));
		return $map;
	}

	/**
	 * Raises a postback on an image map and returns the clicked post back values.
	 * @param TImageMap $map the image map
	 * @param string $param the postback parameter
	 * @return array the post back values of the raised OnClick events
	 */
	private function click(TImageMap $map, string $param): array
	{
		$values = [];
		$map->attachEventHandler('OnClick', function ($sender, TImageMapEventParameter $param) use (&$values) {
			$values[] = $param->getPostBackValue();
		});
		$map->raisePostBackEvent($param);
		return $values;
	}

	public function testPostBackOnAPostBackHotSpotValidatesAndClicks()
	{
		$map = $this->postBackMap();
		$this->assertSame(['first'], $this->click($map, '0'));
		$this->assertSame(['rooms'], $map->getPage()->validated);
	}

	public function testPostBackUsesTheImageMapModeForAHotSpotWithoutOne()
	{
		$map = $this->postBackMap();
		$this->assertSame([], $this->click($map, '1'));
		$map->setHotSpotMode(THotSpotMode::PostBack);
		$this->assertSame(['second'], $this->click($map, '1'));
		$this->assertSame([], $map->getPage()->validated);
	}

	public static function ignoredPostBacks(): array
	{
		return [
			'empty' => [''],
			'negative' => ['-1'],
			'past the end' => ['3'],
			'navigate hot spot' => ['2'],
		];
	}

	/**
	 * @dataProvider ignoredPostBacks
	 */
	public function testPostBackIgnoresOtherParameters(string $param)
	{
		$map = $this->postBackMap();
		$this->assertSame([], $this->click($map, $param));
		$this->assertSame([], $map->getPage()->validated);
	}

	public function testImageAttributeCombinationsOfHotSpotsAndEnabled()
	{
		foreach ([false, true] as $withHotSpot) {
			foreach (['enabled', 'self disabled', 'parent disabled'] as $state) {
				$page = new TPage();
				$this->cs = TTestRecordingClientScript::attachTo($page);
				$panel = new TPanel();
				$page->getControls()->add($panel);
				$map = new TImageMap();
				$map->setImageUrl('plan.png');
				$panel->getControls()->add($map);
				if ($withHotSpot) {
					$map->getHotSpots()->add($this->circle());
				}
				if ($state === 'self disabled') {
					$map->setEnabled(false);
				} elseif ($state === 'parent disabled') {
					$panel->setEnabled(false);
				}
				$html = $this->render($map);
				$label = ($withHotSpot ? 'hot spot' : 'no hot spot') . ", $state";
				$this->assertSame($withHotSpot, str_contains($html, 'usemap='), $label);
				$this->assertSame($state !== 'enabled', str_contains($html, 'disabled="disabled"'), $label);
			}
		}
	}

	public function testRenderHotSpotCombinationsOfModeAndTarget()
	{
		$cases = [
			// [enabled, hot spot mode, map mode, hot spot target, map target, expected mode, expected target]
			[false, null, null, '_self', '_top', THotSpotMode::Inactive, '_self'],
			[false, null, null, '', '', THotSpotMode::Inactive, ''],
			[true, THotSpotMode::PostBack, null, '', '_top', THotSpotMode::PostBack, '_top'],
			[true, null, null, '', '', THotSpotMode::Navigate, ''],
			[true, null, THotSpotMode::Inactive, '_self', '_top', THotSpotMode::Inactive, '_self'],
		];
		foreach ($cases as $i => [$enabled, $mode, $mapMode, $target, $mapTarget, $expectedMode, $expectedTarget]) {
			$map = new TImageMap();
			if ($mapMode !== null) {
				$map->setHotSpotMode($mapMode);
			}
			$map->setTarget($mapTarget);
			$hotspot = $this->circle($mode);
			$hotspot->setTarget($target);
			$area = \Prado\Test\Unit\PradoUnit::invoke($map, 'getRenderHotSpot', $hotspot, $enabled);
			$this->assertNotSame($hotspot, $area, "case $i");
			$this->assertSame($expectedMode, $area->getHotSpotMode(), "case $i mode");
			$this->assertSame($expectedTarget, $area->getTarget(), "case $i target");
			$this->assertSame($mode ?? THotSpotMode::NotSet, $hotspot->getHotSpotMode(), "case $i keeps the hot spot");
		}
	}
}
