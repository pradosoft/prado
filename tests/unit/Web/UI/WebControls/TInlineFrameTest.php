<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TInlineFrame;
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
}
