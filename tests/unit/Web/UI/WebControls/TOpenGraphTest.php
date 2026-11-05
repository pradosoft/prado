<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TLinkTag;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TMetaTag;
use Prado\Web\UI\WebControls\TOpenGraph;

class TOpenGraphTest extends TestCase
{
	private $_service;

	protected function setUp(): void
	{
		// the page's client script manager comes from the page service
		$app = \Prado\Prado::getApplication();
		$this->_service = $app->getService();
		$app->setService(new \Prado\Web\Services\TPageService());
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setService($this->_service);
	}

	private function renderControl(TOpenGraph $control): string
	{
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testDefaults()
	{
		$control = new TOpenGraph();
		foreach (['Title', 'Url', 'Description', 'SiteName', 'Locale', 'Image', 'ImageAlt', 'TwitterCard', 'TwitterSite', 'TwitterCreator'] as $property) {
			$this->assertSame('', $control->{'get' . $property}(), $property);
		}
		$this->assertSame(TOpenGraph::DEFAULT_TYPE, $control->getType());
		$this->assertSame(0, $control->getImageWidth());
		$this->assertSame(0, $control->getImageHeight());
		$this->assertSame(0, $control->getMetaTags()->getCount());
		$this->assertSame("<meta property=\"og:type\" content=\"website\" />\n", $this->renderControl($control));
		$control->setType('');
		$this->assertSame('', $this->renderControl($control));
	}

	public function testRendersTagsInOrder()
	{
		$control = new TOpenGraph();
		$control->setTwitterCreator('@ann');
		$control->setTwitterSite('@acme');
		$control->setTwitterCard('summary_large_image');
		$control->setImageHeight('630');
		$control->setImageWidth(1200);
		$control->setImageAlt('A blue widget');
		$control->setImage('https://example.com/img/blue.png');
		$control->setLocale('en_US');
		$control->setSiteName('Acme');
		$control->setDescription('A widget, in blue.');
		$control->setUrl('https://example.com/widgets/blue');
		$control->setType('product');
		$control->setTitle('Blue Widget');
		$this->assertSame(
			"<meta property=\"og:title\" content=\"Blue Widget\" />\n"
			. "<meta property=\"og:type\" content=\"product\" />\n"
			. "<meta property=\"og:url\" content=\"https://example.com/widgets/blue\" />\n"
			. "<meta property=\"og:description\" content=\"A widget, in blue.\" />\n"
			. "<meta property=\"og:site_name\" content=\"Acme\" />\n"
			. "<meta property=\"og:locale\" content=\"en_US\" />\n"
			. "<meta property=\"og:image\" content=\"https://example.com/img/blue.png\" />\n"
			. "<meta property=\"og:image:alt\" content=\"A blue widget\" />\n"
			. "<meta property=\"og:image:width\" content=\"1200\" />\n"
			. "<meta property=\"og:image:height\" content=\"630\" />\n"
			. "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n"
			. "<meta name=\"twitter:site\" content=\"@acme\" />\n"
			. "<meta name=\"twitter:creator\" content=\"@ann\" />\n",
			$this->renderControl($control)
		);
	}

	public function testContentIsEncoded()
	{
		$control = new TOpenGraph();
		$control->setType('');
		$control->setTitle('"Quoted" <b>');
		$this->assertSame("<meta property=\"og:title\" content=\"&quot;Quoted&quot; &lt;b&gt;\" />\n", $this->renderControl($control));
	}

	public function testParsedMetaTagsRenderAfterProperties()
	{
		$control = new TOpenGraph();
		$meta = new TMetaTag();
		$meta->setProperty('article:published_time');
		$meta->setContent('2026-11-04');
		$control->addParsedObject($meta);
		$this->assertSame([$meta], $control->getMetaTags()->toArray());
		$this->assertSame(0, $control->getControls()->getCount());
		$this->assertSame(
			"<meta property=\"og:type\" content=\"website\" />\n<meta property=\"article:published_time\" content=\"2026-11-04\" />\n",
			$this->renderControl($control)
		);
	}

	public function testBodyContentRendersAfterMetaTags()
	{
		$control = new TOpenGraph();
		$meta = new TMetaTag();
		$meta->setProperty('og:image');
		$meta->setContent('https://example.com/b.png');
		$literal = new TLiteral();
		$literal->setText('<!-- social -->');
		$control->addParsedObject("\n");
		$control->addParsedObject($literal);
		$control->addParsedObject($meta);
		$this->assertSame(["\n", $literal], $control->getControls()->toArray());
		$this->assertSame([$meta], $control->getMetaTags()->toArray());
		$this->assertSame(
			"<meta property=\"og:type\" content=\"website\" />\n<meta property=\"og:image\" content=\"https://example.com/b.png\" />\n\n<!-- social -->",
			$this->renderControl($control)
		);
	}

	public function testMetaTagsAcceptOnlyMetaTags()
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TOpenGraph())->getMetaTags()->add(new TLinkTag());
	}

	public function testMetaTagProperty()
	{
		$meta = new TMetaTag();
		$this->assertSame('', $meta->getProperty());
		$meta->setID('ogt');
		$meta->setName('title');
		$meta->setProperty('og:title');
		$meta->setContent('Widget');
		$tw = new TTextWriter();
		$meta->render(new THtmlWriter($tw));
		$this->assertSame('<meta id="ogt" name="title" property="og:title" content="Widget" />', $tw->flush());
	}

	public function testRendersInHead()
	{
		$page = new TPage();
		$head = new THead();
		$page->getControls()->add($head);
		$control = new TOpenGraph();
		$control->setTitle('Widget');
		$head->addParsedObject($control);
		$this->assertSame($control, $head->getControls()->itemAt(0));
		$tw = new TTextWriter();
		$head->render(new THtmlWriter($tw));
		$this->assertStringContainsString('<meta property="og:title" content="Widget" />', $tw->flush());
	}
}
