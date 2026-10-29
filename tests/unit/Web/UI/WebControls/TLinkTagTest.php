<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TFetchPriority;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TLinkTag;
use Prado\Web\UI\WebControls\TLinkTagCollection;
use Prado\Web\UI\WebControls\TMetaTag;
use Prado\Web\UI\WebControls\TReferrerPolicy;

class TLinkTagTest extends TestCase
{
	private $_service;
	private $_nonce;

	protected function setUp(): void
	{
		// the page's client script manager comes from the page service
		$app = \Prado\Prado::getApplication();
		$this->_service = $app->getService();
		$app->setService(new \Prado\Web\Services\TPageService());
		$this->_nonce = TJavaScript::getScriptNonce();
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setService($this->_service);
		TJavaScript::setScriptNonce($this->_nonce);
	}

	private function renderTag(TLinkTag $tag): string
	{
		$tw = new TTextWriter();
		$tag->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testDefaults()
	{
		$tag = new TLinkTag();
		foreach (['ID', 'Rel', 'Href', 'HrefLang', 'Type', 'Media', 'Sizes', 'As', 'ImageSrcset', 'ImageSizes', 'CrossOrigin', 'Integrity', 'Title'] as $property) {
			$this->assertSame('', $tag->{'get' . $property}(), $property);
		}
		$this->assertSame(TFetchPriority::NotSet, $tag->getFetchPriority());
		$this->assertSame(TReferrerPolicy::NotSet, $tag->getReferrerPolicy());
		$this->assertSame('<link />', $this->renderTag($tag));
	}

	public function testRendersAttributesInOrder()
	{
		$tag = new TLinkTag();
		$tag->setID('font');
		$tag->setRel('preload');
		$tag->setHref('fonts/body.woff2');
		$tag->setAs('Font');
		$tag->setType('font/woff2');
		$tag->setCrossOrigin('Anonymous');
		$tag->setFetchPriority('High');
		$this->assertSame(
			'<link id="font" rel="preload" href="fonts/body.woff2" type="font/woff2" as="font" crossorigin="anonymous" fetchpriority="high" />',
			$this->renderTag($tag)
		);
	}

	public function testRelAndSizesCollapseWhitespace()
	{
		$tag = new TLinkTag();
		$tag->setRel("  alternate \n stylesheet ");
		$tag->setSizes(' 32x32   64x64 ');
		$this->assertSame('alternate stylesheet', $tag->getRel());
		$this->assertSame('32x32 64x64', $tag->getSizes());
	}

	public function testAlternateLanguage()
	{
		$tag = new TLinkTag();
		$tag->setRel('alternate');
		$tag->setHrefLang('fr');
		$tag->setHref('https://example.com/fr/?a=1&b=2');
		$this->assertSame('<link rel="alternate" href="https://example.com/fr/?a=1&b=2" hreflang="fr" />', $this->renderTag($tag));
	}

	public function testResponsiveImagePreloadAndPolicy()
	{
		$tag = new TLinkTag();
		$tag->setRel('preload');
		$tag->setAs('image');
		$tag->setImageSrcset('a-800.jpg 800w, a-1600.jpg 1600w');
		$tag->setImageSizes('100vw');
		$tag->setReferrerPolicy('NoReferrer');
		$tag->setMedia('(min-width: 40em)');
		$output = $this->renderTag($tag);
		$this->assertStringContainsString('imagesrcset="a-800.jpg 800w, a-1600.jpg 1600w" imagesizes="100vw"', $output);
		$this->assertStringContainsString('referrerpolicy="no-referrer"', $output);
		$this->assertStringContainsString('media="(min-width: 40em)"', $output);
	}

	public function testInvalidCrossOriginThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TLinkTag())->setCrossOrigin('yes');
	}

	public function testInvalidFetchPriorityThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TLinkTag())->setFetchPriority('urgent');
	}

	public function testNonceOnlyForGovernedRels()
	{
		TJavaScript::setScriptNonce('abc123');
		$tag = new TLinkTag();
		foreach (['stylesheet' => true, 'Preload' => true, 'modulepreload' => true, 'alternate stylesheet' => true, 'canonical' => false, 'icon' => false, 'preconnect' => false] as $rel => $expected) {
			$tag->setRel($rel);
			$output = $this->renderTag($tag);
			$this->assertSame($expected, str_contains($output, 'nonce="abc123"'), $rel);
		}
		TJavaScript::setScriptNonce(null);
		$tag->setRel('stylesheet');
		$this->assertStringNotContainsString('nonce', $this->renderTag($tag));
	}

	public function testCollectionAcceptsOnlyLinkTags()
	{
		$collection = new TLinkTagCollection();
		$tag = new TLinkTag();
		$tag->setID('canonical');
		$collection->add($tag);
		$this->assertSame(0, $collection->findIndexByID('canonical'));
		$this->assertSame($tag, $collection->findLinkTagByID('canonical'));
		$this->assertSame(-1, $collection->findIndexByID('none'));
		$this->assertNull($collection->findLinkTagByID('none'));
		$this->expectException(TInvalidDataTypeException::class);
		$collection->add(new TMetaTag());
	}

	public function testHeadCollectsParsedLinkTags()
	{
		$head = new THead();
		$tag = new TLinkTag();
		$head->addParsedObject($tag);
		$this->assertSame([$tag], $head->getLinkTags()->toArray());
		$this->assertSame(0, $head->getControls()->getCount());
	}

	public function testHeadRendersLinkTagsAfterMetaTags()
	{
		$page = new TPage();
		$head = new THead();
		$page->getControls()->add($head);
		$meta = new TMetaTag();
		$meta->setName('description');
		$meta->setContent('Products');
		$head->getMetaTags()->add($meta);
		$link = new TLinkTag();
		$link->setRel('canonical');
		$link->setHref('https://example.com/products');
		$head->getLinkTags()->add($link);
		$tw = new TTextWriter();
		$head->render(new THtmlWriter($tw));
		$output = $tw->flush();
		$metaAt = strpos($output, '<meta name="description"');
		$linkAt = strpos($output, '<link rel="canonical" href="https://example.com/products" />');
		$this->assertNotFalse($metaAt);
		$this->assertNotFalse($linkAt);
		$this->assertGreaterThan($metaAt, $linkAt);
	}
}
