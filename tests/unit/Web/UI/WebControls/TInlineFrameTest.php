<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TInlineFrame;
use Prado\Web\UI\WebControls\TInlineFrameAlign;
use Prado\Web\UI\WebControls\TInlineFrameScrollBars;
use Prado\Web\UI\WebControls\TLoadingMode;
use Prado\Web\UI\WebControls\TReferrerPolicy;

class TInlineFrameTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testDefaults()
	{
		$frame = new TInlineFrame();
		$this->assertNull($frame->getSandbox());
		$this->assertSame('', $frame->getAllow());
		$this->assertSame(TLoadingMode::NotSet, $frame->getLoading());
		$this->assertSame(TReferrerPolicy::NotSet, $frame->getReferrerPolicy());
		$this->assertSame('', $frame->getSrcDoc());
		$frame->setFrameUrl('/map');
		$this->assertSame('<iframe src="/map"></iframe>', $this->render($frame));
	}

	public function testAttributesRender()
	{
		$frame = new TInlineFrame();
		$frame->setFrameUrl('https://example.com/map');
		$frame->setSandbox(' allow-scripts   allow-forms ');
		$frame->setAllow("fullscreen; geolocation 'self'");
		$frame->setLoading('Lazy');
		$frame->setReferrerPolicy(TReferrerPolicy::NoReferrer);
		$frame->setToolTip('Office location map');
		$this->assertSame(
			'<iframe src="https://example.com/map" sandbox="allow-scripts allow-forms"'
			. ' allow="fullscreen; geolocation \'self\'" loading="lazy" referrerpolicy="no-referrer"'
			. ' title="Office location map"></iframe>',
			$this->render($frame)
		);
	}

	public function testSandboxStates()
	{
		$frame = new TInlineFrame();
		$frame->setSandbox(true);
		$this->assertSame('', $frame->getSandbox());
		$this->assertStringContainsString('sandbox=""', $this->render($frame));

		$frame->setSandbox('false');
		$this->assertNull($frame->getSandbox());
		$this->assertStringNotContainsString('sandbox', $this->render($frame));

		$frame->setSandbox('TRUE');
		$this->assertSame('', $frame->getSandbox());

		$frame->setSandbox('');
		$this->assertSame('', $frame->getSandbox());

		$frame->setSandbox(null);
		$this->assertNull($frame->getSandbox());
	}

	public function testSrcDocIsEncoded()
	{
		$frame = new TInlineFrame();
		$frame->setSrcDoc('<p class="note">Hi & bye &lt;3</p>');
		$this->assertStringContainsString(
			'srcdoc="&lt;p class=&quot;note&quot;&gt;Hi &amp; bye &amp;lt;3&lt;/p&gt;"',
			$this->render($frame)
		);
	}

	public function testLoadingInvalid()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TInlineFrame())->setLoading('soon');
	}

	public function testLegacyPropertyDefaults()
	{
		$frame = new TInlineFrame();
		$this->assertSame(TInlineFrameAlign::NotSet, $frame->getAlign());
		$this->assertSame('', $frame->getDescriptionUrl());
		$this->assertTrue($frame->getShowBorder());
		$this->assertSame('', $frame->getFrameUrl());
		$this->assertSame(TInlineFrameScrollBars::Auto, $frame->getScrollBars());
		foreach (['Width', 'Height', 'MarginWidth', 'MarginHeight'] as $property) {
			$this->assertSame(-1, $frame->{'get' . $property}(), $property);
		}
	}

	public function testLegacyPropertiesRender()
	{
		$frame = new TInlineFrame();
		$frame->setID('map');
		$frame->setData('/map');
		$frame->setAlign(TInlineFrameAlign::Middle);
		$frame->setScrollBars(TInlineFrameScrollBars::None);
		$frame->setShowBorder('false');
		$frame->setDescriptionUrl('/map-text');
		$frame->setWidth('300');
		$frame->setHeight(200);
		$frame->setMarginHeight(4);
		$frame->setMarginWidth(6);
		$this->assertSame('/map', $frame->getData());
		$this->assertSame(
			'<iframe name="map" src="/map" align="middle" scrolling="no" frameborder="0" longdesc="/map-text"'
			. ' width="300" height="200" marginheight="4" marginwidth="6" id="map"></iframe>',
			$this->render($frame)
		);
		$frame->setScrollBars(TInlineFrameScrollBars::Both);
		$this->assertStringContainsString('scrolling="yes"', $this->render($frame));
	}

	public function testNegativeSizesUnset()
	{
		$frame = new TInlineFrame();
		foreach (['Width', 'Height', 'MarginWidth', 'MarginHeight'] as $property) {
			$frame->{'set' . $property}(10);
			$frame->{'set' . $property}(-2);
			$this->assertSame(-1, $frame->{'get' . $property}(), $property);
		}
		$this->assertSame('<iframe></iframe>', $this->render($frame));
	}

	public function testEnumSettersRejectInvalidValues()
	{
		$frame = new TInlineFrame();
		foreach (['setAlign', 'setScrollBars'] as $setter) {
			try {
				$frame->$setter('Sideways');
				$this->fail("$setter accepted an invalid value");
			} catch (TInvalidDataValueException $e) {
				$this->assertStringContainsString('Sideways', $e->getMessage());
			}
		}
	}
}
