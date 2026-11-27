<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TAttributeCollection;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TCircleHotSpot;
use Prado\Web\UI\WebControls\THotSpot;
use Prado\Web\UI\WebControls\THotSpotCollection;
use Prado\Web\UI\WebControls\THotSpotMode;
use Prado\Web\UI\WebControls\TImageMapEventParameter;
use Prado\Web\UI\WebControls\TPolygonHotSpot;
use Prado\Web\UI\WebControls\TRectangleHotSpot;

class THotSpotTest extends TestCase
{
	/**
	 * Renders a hot spot.
	 * @param THotSpot $hotspot the hot spot
	 * @return string the rendered markup
	 */
	private function renderHotSpot(THotSpot $hotspot): string
	{
		$tw = new TTextWriter();
		$hotspot->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testCircleShapeAndCoordinates()
	{
		$circle = new TCircleHotSpot();
		$this->assertSame('circle', $circle->getShape());
		$this->assertSame('0,0,0', $circle->getCoordinates());
		$circle->setX('10');
		$circle->setY(20);
		$circle->setRadius(5);
		$this->assertSame([10, 20, 5], [$circle->getX(), $circle->getY(), $circle->getRadius()]);
		$this->assertSame('10,20,5', $circle->getCoordinates());
	}

	public function testRectangleShapeAndCoordinates()
	{
		$rect = new TRectangleHotSpot();
		$this->assertSame('rect', $rect->getShape());
		$this->assertSame('0,0,0,0', $rect->getCoordinates());
		$rect->setLeft(1);
		$rect->setTop('2');
		$rect->setRight(30);
		$rect->setBottom(40);
		$this->assertSame([1, 2, 30, 40], [$rect->getLeft(), $rect->getTop(), $rect->getRight(), $rect->getBottom()]);
		$this->assertSame('1,2,30,40', $rect->getCoordinates());
	}

	public function testPolygonShapeAndCoordinates()
	{
		$poly = new TPolygonHotSpot();
		$this->assertSame('poly', $poly->getShape());
		$this->assertSame('', $poly->getCoordinates());
		$poly->setCoordinates('0,0,10,0,5,8');
		$this->assertSame('0,0,10,0,5,8', $poly->getCoordinates());
	}

	public function testDefaults()
	{
		$hotspot = new TCircleHotSpot();
		$this->assertSame('', $hotspot->getAccessKey());
		$this->assertSame('', $hotspot->getAlternateText());
		$this->assertSame(THotSpotMode::NotSet, $hotspot->getHotSpotMode());
		$this->assertSame('', $hotspot->getNavigateUrl());
		$this->assertSame('', $hotspot->getPostBackValue());
		$this->assertSame(0, $hotspot->getTabIndex());
		$this->assertTrue($hotspot->getCausesValidation());
		$this->assertSame('', $hotspot->getValidationGroup());
		$this->assertSame('', $hotspot->getTarget());
	}

	public function testSetters()
	{
		$hotspot = new TCircleHotSpot();
		$hotspot->setAccessKey('k');
		$hotspot->setAlternateText('Kitchen');
		$hotspot->setHotSpotMode(THotSpotMode::PostBack);
		$hotspot->setNavigateUrl('/kitchen');
		$hotspot->setPostBackValue('kitchen');
		$hotspot->setTabIndex('3');
		$hotspot->setCausesValidation('false');
		$hotspot->setValidationGroup('rooms');
		$hotspot->setTarget('_blank');
		$this->assertSame('k', $hotspot->getAccessKey());
		$this->assertSame('Kitchen', $hotspot->getAlternateText());
		$this->assertSame(THotSpotMode::PostBack, $hotspot->getHotSpotMode());
		$this->assertSame('/kitchen', $hotspot->getNavigateUrl());
		$this->assertSame('kitchen', $hotspot->getPostBackValue());
		$this->assertSame(3, $hotspot->getTabIndex());
		$this->assertFalse($hotspot->getCausesValidation());
		$this->assertSame('rooms', $hotspot->getValidationGroup());
		$this->assertSame('_blank', $hotspot->getTarget());
	}

	public function testSettingDefaultsClearsTheValues()
	{
		$hotspot = new TCircleHotSpot();
		$hotspot->setTabIndex(4);
		$hotspot->setTabIndex(0);
		$hotspot->setHotSpotMode(THotSpotMode::Inactive);
		$hotspot->setHotSpotMode(THotSpotMode::NotSet);
		$this->assertSame(0, $hotspot->getTabIndex());
		$this->assertSame(THotSpotMode::NotSet, $hotspot->getHotSpotMode());
	}

	public function testHotSpotModeRejectsInvalidValues()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TCircleHotSpot())->setHotSpotMode('Hover');
	}

	public function testAttributesAreLazy()
	{
		$hotspot = new TCircleHotSpot();
		$this->assertFalse($hotspot->getHasAttributes());
		$this->assertFalse($hotspot->hasAttribute('data-room'));
		$this->assertNull($hotspot->getAttribute('data-room'));
		$this->assertNull($hotspot->removeAttribute('data-room'));
		$this->assertFalse($hotspot->getHasAttributes());

		$this->assertInstanceOf(TAttributeCollection::class, $hotspot->getAttributes());
		$this->assertSame($hotspot->getAttributes(), $hotspot->getAttributes());
		$this->assertFalse($hotspot->getHasAttributes());
	}

	public function testAttributes()
	{
		$hotspot = new TCircleHotSpot();
		$hotspot->setAttribute('data-room', 'kitchen');
		$this->assertTrue($hotspot->getHasAttributes());
		$this->assertTrue($hotspot->hasAttribute('data-room'));
		$this->assertSame('kitchen', $hotspot->getAttribute('data-room'));
		$this->assertSame('kitchen', $hotspot->removeAttribute('data-room'));
		$this->assertFalse($hotspot->getHasAttributes());
	}

	public function testCollectionAcceptsOnlyHotSpots()
	{
		$collection = new THotSpotCollection();
		$collection->add($circle = new TCircleHotSpot());
		$this->assertSame($circle, $collection->itemAt(0));
		try {
			$collection->add('area');
			$this->fail('Expected TInvalidDataTypeException');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('hotspotcollection_hotspot_required', $e->getErrorCode());
		}
	}

	public function testEventParameterCarriesThePostBackValue()
	{
		$this->assertSame('kitchen', (new TImageMapEventParameter('kitchen'))->getPostBackValue());
	}

	public function testRenderOnItsOwnNavigatesWhenTheModeIsNotSet()
	{
		$rect = new TRectangleHotSpot();
		$rect->setRight(10);
		$rect->setBottom(10);
		$rect->setNavigateUrl('/a');
		$rect->setTarget('_blank');
		$this->assertSame('<area shape="rect" coords="0,0,10,10" href="/a" target="_blank" />', $this->renderHotSpot($rect));
		$rect->setHotSpotMode(THotSpotMode::PostBack);
		$this->assertSame('<area shape="rect" coords="0,0,10,10" />', $this->renderHotSpot($rect));
	}
}
