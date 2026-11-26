<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TFetchPriority;
use Prado\Web\UI\WebControls\TImage;
use Prado\Web\UI\WebControls\TImageDecoding;
use Prado\Web\UI\WebControls\TLoadingMode;
use Prado\Web\UI\WebControls\TReferrerPolicy;

class TImageTestInput extends TImage
{
	protected function getTagName()
	{
		return 'input';
	}
}

class TImageTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testDefaults()
	{
		$image = new TImage();
		$this->assertSame('', $image->getSrcset());
		$this->assertSame('', $image->getSizes());
		$this->assertSame(-1, $image->getIntrinsicWidth());
		$this->assertSame(-1, $image->getIntrinsicHeight());
		$this->assertSame(TLoadingMode::NotSet, $image->getLoading());
		$this->assertSame(TImageDecoding::NotSet, $image->getDecoding());
		$this->assertSame(TFetchPriority::NotSet, $image->getFetchPriority());
		$this->assertSame('', $image->getCrossOrigin());
		$this->assertSame(TReferrerPolicy::NotSet, $image->getReferrerPolicy());
		$image->setImageUrl('a.png');
		$this->assertSame('<img src="a.png" alt="" />', $this->render($image));
	}

	public function testLoadingAttributesRender()
	{
		$image = new TImage();
		$image->setImageUrl('a-800.jpg');
		$image->setSrcset(' a-800.jpg 800w, a-1600.jpg 1600w ');
		$image->setSizes('(min-width: 60em) 50vw, 100vw');
		$image->setIntrinsicWidth('800');
		$image->setIntrinsicHeight(600);
		$image->setLoading('lazy');
		$image->setDecoding(TImageDecoding::Async);
		$image->setFetchPriority('High');
		$image->setCrossOrigin(' Anonymous ');
		$image->setReferrerPolicy('StrictOriginWhenCrossOrigin');
		$this->assertSame(
			'<img src="a-800.jpg" alt="" width="800" height="600" srcset="a-800.jpg 800w, a-1600.jpg 1600w"'
			. ' sizes="(min-width: 60em) 50vw, 100vw" loading="lazy" decoding="async" fetchpriority="high"'
			. ' crossorigin="anonymous" referrerpolicy="strict-origin-when-cross-origin" />',
			$this->render($image)
		);
	}

	public function testIntrinsicSizeNegativeUnsets()
	{
		$image = new TImage();
		$image->setIntrinsicWidth(10);
		$image->setIntrinsicWidth(-5);
		$this->assertSame(-1, $image->getIntrinsicWidth());
	}

	public function testIntrinsicSizeIsSeparateFromCssSize()
	{
		$image = new TImage();
		$image->setWidth('50%');
		$image->setIntrinsicWidth(800);
		$output = $this->render($image);
		$this->assertStringContainsString('width="800"', $output);
		$this->assertStringContainsString('style="width:50%;"', $output);
	}

	public function testInputRendersOnlyIntrinsicSize()
	{
		$image = new TImageTestInput();
		$image->setIntrinsicWidth(32);
		$image->setSrcset('a@2x.png 2x');
		$image->setLoading(TLoadingMode::Lazy);
		$image->setReferrerPolicy(TReferrerPolicy::NoReferrer);
		$output = $this->render($image);
		$this->assertStringContainsString('width="32"', $output);
		$this->assertStringNotContainsString('srcset', $output);
		$this->assertStringNotContainsString('loading', $output);
		$this->assertStringNotContainsString('referrerpolicy', $output);
	}

	public function testCrossOriginInvalid()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TImage())->setCrossOrigin('everyone');
	}

	public function testLoadingInvalid()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TImage())->setLoading('later');
	}

	public static function referrerPolicyProvider(): array
	{
		return [
			[TReferrerPolicy::NoReferrer, 'no-referrer'],
			[TReferrerPolicy::NoReferrerWhenDowngrade, 'no-referrer-when-downgrade'],
			[TReferrerPolicy::Origin, 'origin'],
			[TReferrerPolicy::OriginWhenCrossOrigin, 'origin-when-cross-origin'],
			[TReferrerPolicy::SameOrigin, 'same-origin'],
			[TReferrerPolicy::StrictOrigin, 'strict-origin'],
			[TReferrerPolicy::StrictOriginWhenCrossOrigin, 'strict-origin-when-cross-origin'],
			[TReferrerPolicy::UnsafeUrl, 'unsafe-url'],
		];
	}

	/**
	 * @dataProvider referrerPolicyProvider
	 */
	public function testReferrerPolicyAttributeValue(string $policy, string $attribute)
	{
		$this->assertSame($attribute, TReferrerPolicy::toAttributeValue($policy));
	}

	public function testCrossOriginAcceptsEachKeyword()
	{
		$image = new TImage();
		foreach (['anonymous' => 'anonymous', ' USE-Credentials ' => 'use-credentials', '' => ''] as $value => $expected) {
			$image->setCrossOrigin($value);
			$this->assertSame($expected, $image->getCrossOrigin());
		}
	}

	public function testDataIsTheImageUrl()
	{
		$image = new TImage();
		$image->setData('a.png');
		$this->assertSame('a.png', $image->getImageUrl());
		$this->assertSame('a.png', $image->getData());
	}

	public function testDescriptionUrlAndAlignRender()
	{
		$image = new TImage();
		$image->setImageUrl('a.png');
		$image->setAlternateText('A');
		$image->setDescriptionUrl('/about-a');
		$image->setImageAlign('right');
		$this->assertSame('/about-a', $image->getDescriptionUrl());
		$this->assertSame('right', $image->getImageAlign());
		$this->assertSame('<img src="a.png" alt="A" longdesc="/about-a" align="right" />', $this->render($image));
	}

	public function testIntrinsicHeightRendersAndNegativeUnsets()
	{
		$image = new TImage();
		$image->setIntrinsicHeight('24');
		$this->assertSame(24, $image->getIntrinsicHeight());
		$this->assertStringContainsString('height="24"', $this->render($image));
		$image->setIntrinsicHeight(-3);
		$this->assertSame(-1, $image->getIntrinsicHeight());
		$this->assertStringNotContainsString('height', $this->render($image));
	}
}
