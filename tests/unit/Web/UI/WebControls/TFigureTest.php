<?php

use Prado\Web\UI\WebControls\TFigure;
use Prado\Web\UI\WebControls\TFigureCaption;
use Prado\Web\UI\WebControls\TFigureCaptionOrder;
use PHPUnit\Framework\TestCase;

class TFigureTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersFigureTag()
	{
		$control = new TFigure();
		$output = $this->render($control);
		$this->assertStringContainsString('<figure', $output);
		$this->assertStringContainsString('</figure>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TFigure();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testCaptionDefaultEmpty()
	{
		$control = new TFigure();
		$this->assertEquals('', $control->getCaption());
	}

	public function testSetCaption()
	{
		$control = new TFigure();
		$control->setCaption('A beautiful sunrise');
		$this->assertEquals('A beautiful sunrise', $control->getCaption());
	}

	public function testSetCaptionEmpty()
	{
		$control = new TFigure();
		$control->setCaption('Some caption');
		$control->setCaption('');
		$this->assertEquals('', $control->getCaption());
	}

	public function testCaptionOrderDefaultNull()
	{
		$control = new TFigure();
		$this->assertNull($control->getCaptionOrder());
	}

	public function testSetCaptionOrderFirst()
	{
		$control = new TFigure();
		$control->setCaptionOrder(TFigureCaptionOrder::First);
		$this->assertEquals(TFigureCaptionOrder::First, $control->getCaptionOrder());
	}

	public function testSetCaptionOrderLast()
	{
		$control = new TFigure();
		$control->setCaptionOrder(TFigureCaptionOrder::Last);
		$this->assertEquals(TFigureCaptionOrder::Last, $control->getCaptionOrder());
	}

	public function testSetCaptionOrderNull()
	{
		$control = new TFigure();
		$control->setCaptionOrder(TFigureCaptionOrder::First);
		$control->setCaptionOrder(null);
		$this->assertNull($control->getCaptionOrder());
	}

	public function testSetCaptionOrderEmptyString()
	{
		$control = new TFigure();
		$control->setCaptionOrder(TFigureCaptionOrder::First);
		$control->setCaptionOrder('');
		$this->assertNull($control->getCaptionOrder());
	}

	public function testSetCaptionOrderNone()
	{
		$control = new TFigure();
		$control->setCaptionOrder(TFigureCaptionOrder::None);
		$this->assertEquals(TFigureCaptionOrder::None, $control->getCaptionOrder());
	}

	public function testEncodeDefaultFalse()
	{
		$control = new TFigure();
		$this->assertFalse($control->getEncode());
	}

	public function testSetEncode()
	{
		$control = new TFigure();
		$control->setEncode(true);
		$this->assertTrue($control->getEncode());
		$control->setEncode('false');
		$this->assertFalse($control->getEncode());
	}

	public function testCaptionEncodedWhenEncodeTrue()
	{
		$control = new TFigure();
		$control->setCaption('Tom & Jerry <b>bold</b>');
		$control->setEncode(true);
		$output = $this->render($control);
		// THttpUtility::htmlEncode() translates <, >, and " only.
		$this->assertStringContainsString('Tom & Jerry &lt;b&gt;bold&lt;/b&gt;', $output);
		$this->assertStringNotContainsString('<b>bold</b>', $output);
	}

	public function testCaptionNotEncodedByDefault()
	{
		$control = new TFigure();
		$control->setCaption('Tom & Jerry <b>bold</b>');
		$output = $this->render($control);
		$this->assertStringContainsString('Tom & Jerry <b>bold</b>', $output);
	}

	// --- renderContents: auto-generated figcaption ---

	public function testNoCaptionPropertyNoFigcaption()
	{
		$control = new TFigure();
		$output = $this->render($control);
		$this->assertStringNotContainsString('<figcaption', $output);
	}

	public function testCaptionPropertyRenderedLast()
	{
		// Default: caption goes after content (Last / null)
		$control = new TFigure();
		$control->setCaption('My Caption');
		$output = $this->render($control);
		$this->assertStringContainsString('<figcaption>', $output);
		$this->assertStringContainsString('My Caption', $output);
		$this->assertStringContainsString('</figcaption>', $output);
	}

	public function testCaptionPropertyWithOrderLastAppearsAfterContent()
	{
		$control = new TFigure();
		$control->setCaption('End Caption');
		$control->setCaptionOrder(TFigureCaptionOrder::Last);

		$img = new \Prado\Web\UI\WebControls\TImage();
		$img->setImageUrl('photo.jpg');
		$control->getControls()->add($img);

		$output = $this->render($control);

		$figPos = strpos($output, '<figcaption>');
		$imgPos = strpos($output, '<img');
		$this->assertNotFalse($figPos);
		$this->assertNotFalse($imgPos);
		// figcaption should appear AFTER <img>
		$this->assertGreaterThan($imgPos, $figPos);
	}

	public function testCaptionPropertyWithOrderFirstAppearsBeforeContent()
	{
		$control = new TFigure();
		$control->setCaption('Top Caption');
		$control->setCaptionOrder(TFigureCaptionOrder::First);

		$img = new \Prado\Web\UI\WebControls\TImage();
		$img->setImageUrl('photo.jpg');
		$control->getControls()->add($img);

		$output = $this->render($control);

		$figPos = strpos($output, '<figcaption>');
		$imgPos = strpos($output, '<img');
		$this->assertNotFalse($figPos);
		$this->assertNotFalse($imgPos);
		// figcaption should appear BEFORE <img>
		$this->assertLessThan($imgPos, $figPos);
	}

	// --- renderContents: explicit TFigureCaption children ---

	public function testExplicitTFigureCaptionChildRendered()
	{
		$figure = new TFigure();
		$caption = new TFigureCaption();
		$figure->getControls()->add($caption);

		$output = $this->render($figure);
		$this->assertStringContainsString('<figcaption', $output);
		$this->assertStringContainsString('</figcaption>', $output);
	}

	public function testExplicitTFigureCaptionSuppressesCaptionProperty()
	{
		// When TFigureCaption children exist, the Caption property must NOT produce a second figcaption
		$figure = new TFigure();
		$figure->setCaption('Should not appear');

		$caption = new TFigureCaption();
		$figure->getControls()->add($caption);

		$output = $this->render($figure);
		$this->assertStringNotContainsString('Should not appear', $output);
		// Only one figcaption from the child
		$this->assertEquals(1, substr_count($output, '<figcaption'));
	}

	public function testExplicitTFigureCaptionChildWithText()
	{
		$figure = new TFigure();
		$caption = new TFigureCaption();
		$caption->getControls()->add('Explicit caption text');
		$figure->getControls()->add($caption);

		$output = $this->render($figure);
		$this->assertStringContainsString('Explicit caption text', $output);
	}

	public function testMultipleTFigureCaptionChildrenAllRendered()
	{
		$figure = new TFigure();

		$caption1 = new TFigureCaption();
		$caption1->getControls()->add('First');
		$figure->getControls()->add($caption1);

		$caption2 = new TFigureCaption();
		$caption2->getControls()->add('Second');
		$figure->getControls()->add($caption2);

		$output = $this->render($figure);
		$this->assertEquals(2, substr_count($output, '<figcaption'));
		$this->assertStringContainsString('First', $output);
		$this->assertStringContainsString('Second', $output);
	}

	public function testCaptionOrderNoneSuppressesAutoCaption()
	{
		$control = new TFigure();
		$control->setCaption('Should be suppressed');
		$control->setCaptionOrder(TFigureCaptionOrder::None);
		$output = $this->render($control);
		$this->assertStringNotContainsString('<figcaption', $output);
		$this->assertStringNotContainsString('Should be suppressed', $output);
	}

	public function testCaptionOrderNoneRendersOtherChildren()
	{
		$control = new TFigure();
		$control->setCaption('Suppressed caption');
		$control->setCaptionOrder(TFigureCaptionOrder::None);

		$header2 = new \Prado\Web\UI\WebControls\THeader2();
		$header2->setId('figContent');
		$control->getControls()->add($header2);

		$output = $this->render($control);
		$this->assertStringContainsString('<h2', $output);
		$this->assertStringNotContainsString('<figcaption', $output);
	}

	public function testSetCaptionOrderInvalidThrows()
	{
		$control = new TFigure();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setCaptionOrder('Middle');
	}

	public function testStringChildRenderedWithAutoCaption()
	{
		$control = new TFigure();
		$control->setCaption('Auto caption');
		$control->getControls()->add('plain text content');

		$output = $this->render($control);
		$this->assertStringContainsString('plain text content', $output);
		$this->assertStringContainsString('<figcaption>Auto caption</figcaption>', $output);
		// Default order is Last: caption appears after the text content
		$this->assertGreaterThan(strpos($output, 'plain text content'), strpos($output, '<figcaption>'));
	}

	public function testStringChildRenderedWithCaptionOrderNone()
	{
		$control = new TFigure();
		$control->setCaption('Suppressed');
		$control->setCaptionOrder(TFigureCaptionOrder::None);
		$control->getControls()->add('plain text content');

		$output = $this->render($control);
		$this->assertStringContainsString('plain text content', $output);
		$this->assertStringNotContainsString('<figcaption', $output);
	}

	public function testInvisibleChildNotRenderedWithAutoCaption()
	{
		$control = new TFigure();
		$control->setCaption('Auto caption');

		$img = new \Prado\Web\UI\WebControls\TImage();
		$img->setImageUrl('photo.jpg');
		$img->setVisible(false);
		$control->getControls()->add($img);

		$output = $this->render($control);
		$this->assertStringNotContainsString('<img', $output);
		$this->assertStringContainsString('<figcaption>', $output);
	}

	public function testNonCaptionChildrenRenderedAlongsideCaptionProperty()
	{
		$figure = new TFigure();
		$figure->setCaption('Auto caption');

		$header3 = new \Prado\Web\UI\WebControls\THeader3();
		$header3->setId('innerHeader3');
		$figure->getControls()->add($header3);

		$output = $this->render($figure);
		$this->assertStringContainsString('<h3', $output);
		$this->assertStringContainsString('<figcaption>', $output);
	}
}
