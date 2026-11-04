<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TJsonLd;

class TJsonLdTest extends TestCase
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
		TJavaScript::setScriptNonce(null);
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setService($this->_service);
		TJavaScript::setScriptNonce($this->_nonce);
	}

	private function renderControl(TJsonLd $control): string
	{
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testDefaultsRenderNothing()
	{
		$control = new TJsonLd();
		$this->assertNull($control->getData());
		$this->assertSame(TJsonLd::DEFAULT_CONTEXT, $control->getContext());
		$this->assertSame('', $control->getJson());
		$this->assertSame('', $this->renderControl($control));
		$control->setData([]);
		$this->assertSame('', $this->renderControl($control));
	}

	public function testRendersDataBlockWithContext()
	{
		$control = new TJsonLd();
		$control->setData(['@type' => 'Organization', 'name' => 'Acme', 'url' => 'https://example.com/']);
		$this->assertSame(
			'<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"Acme","url":"https://example.com/"}</script>',
			$this->renderControl($control)
		);
	}

	public function testExistingContextIsKept()
	{
		$control = new TJsonLd();
		$control->setData(['@context' => 'https://example.org/vocab', '@type' => 'Thing']);
		$this->assertSame('{"@context":"https://example.org/vocab","@type":"Thing"}', $control->getJson());
	}

	public function testContextSkippedWhenEmptyOrList()
	{
		$control = new TJsonLd();
		$control->setContext('');
		$control->setData(['@type' => 'Thing']);
		$this->assertSame('{"@type":"Thing"}', $control->getJson());

		$control->setContext('https://schema.org');
		$control->setData([['@type' => 'Thing'], ['@type' => 'Place']]);
		$this->assertSame('[{"@type":"Thing"},{"@type":"Place"}]', $control->getJson());
	}

	public function testJsonTextIsDecoded()
	{
		$control = new TJsonLd();
		$control->setData(' {"@type": "Product", "name": "Widget"} ');
		$this->assertSame(['@type' => 'Product', 'name' => 'Widget'], $control->getData());
		$control->setData('  ');
		$this->assertNull($control->getData());
	}

	public function testInvalidJsonTextThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TJsonLd())->setData('{"@type": ');
	}

	public function testValuesCannotCloseTheScript()
	{
		$control = new TJsonLd();
		$control->setData(['name' => '</script><script>alert(1)</script><!--', 'text' => 'Café & Co']);
		$json = $control->getJson();
		$this->assertStringNotContainsString('<', $json);
		$this->assertStringNotContainsString('>', $json);
		$this->assertStringContainsString('Caf\\u00e9 & Co', $json);
		$this->assertSame('</script><script>alert(1)</script><!--', json_decode($json, true)['name']);
	}

	public function testJsonSerializableObject()
	{
		$control = new TJsonLd();
		$control->setData(new TJsonLdTestThing());
		$this->assertSame('{"@type":"Thing","name":"Widget"}', $control->getJson());
	}

	public function testNonce()
	{
		TJavaScript::setScriptNonce('abc123');
		$control = new TJsonLd();
		$control->setData(['@type' => 'Thing']);
		$this->assertStringStartsWith('<script type="application/ld+json" nonce="abc123">', $this->renderControl($control));
	}

	public function testRendersInHead()
	{
		$page = new TPage();
		$head = new THead();
		$page->getControls()->add($head);
		$control = new TJsonLd();
		$control->setData(['@type' => 'Thing']);
		$head->addParsedObject($control);
		$tw = new TTextWriter();
		$head->render(new THtmlWriter($tw));
		$this->assertStringContainsString('<script type="application/ld+json">{"@context":"https://schema.org","@type":"Thing"}</script>', $tw->flush());
	}
}

class TJsonLdTestThing implements \JsonSerializable
{
	public function jsonSerialize(): mixed
	{
		return ['@type' => 'Thing', 'name' => 'Widget'];
	}
}
