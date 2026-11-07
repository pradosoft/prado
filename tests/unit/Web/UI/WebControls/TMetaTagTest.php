<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TMetaTag;

class TMetaTagTest extends TestCase
{
	private function renderTag(TMetaTag $tag): string
	{
		$tw = new TTextWriter();
		$tag->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testDefaults()
	{
		$tag = new TMetaTag();
		foreach (['ID', 'HttpEquiv', 'Name', 'Content', 'Scheme', 'Charset'] as $property) {
			$this->assertSame('', $tag->{'get' . $property}(), $property);
		}
		$this->assertSame('<meta content="" />', $this->renderTag($tag));
	}

	public function testRendersAttributesInOrder()
	{
		$tag = new TMetaTag();
		$tag->setContent('no-cache');
		$tag->setScheme('s');
		$tag->setHttpEquiv('Pragma');
		$tag->setName('n');
		$tag->setID('m');
		$this->assertSame('<meta id="m" name="n" http-equiv="Pragma" scheme="s" content="no-cache" />', $this->renderTag($tag));
	}

	public function testCharsetOmitsContent()
	{
		$tag = new TMetaTag();
		$tag->setCharset('utf-8');
		$tag->setContent('ignored');
		$this->assertSame('<meta charset="utf-8" />', $this->renderTag($tag));
	}

	public function testSettersEnsureString()
	{
		$tag = new TMetaTag();
		$tag->setName(null);
		$tag->setContent(5);
		$this->assertSame('', $tag->getName());
		$this->assertSame('5', $tag->getContent());
	}

	public function testRenderUsesGetters()
	{
		$tag = new TMetaTagTestOverride();
		$tag->setName('description');
		$this->assertSame('<meta name="description" content="from getter" />', $this->renderTag($tag));
	}
}

class TMetaTagTestOverride extends TMetaTag
{
	public function getContent()
	{
		return 'from getter';
	}
}
