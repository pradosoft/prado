<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TTemplate;
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

	private function jsonOf(TJsonLd $control): string
	{
		return preg_replace('/^<script[^>]*>|<\/script>$/', '', $this->renderControl($control));
	}

	private function instantiate(string $template): TPage
	{
		$page = new TPage();
		$page->setID('TestPage');
		(new TTemplate($template, sys_get_temp_dir(), null, 0, true))->instantiateIn($page);
		return $page;
	}

	public function testDefaultsRenderNothing()
	{
		$control = new TJsonLd();
		$this->assertNull($control->getStructuredData());
		$this->assertSame(TJsonLd::DEFAULT_CONTEXT, $control->getContext());
		$this->assertSame('', $this->renderControl($control));
		$control->setStructuredData([]);
		$this->assertSame('', $this->renderControl($control));
	}

	public function testRendersDataBlockWithContext()
	{
		$control = new TJsonLd();
		$control->setStructuredData(['@type' => 'Organization', 'name' => 'Acme', 'url' => 'https://example.com/']);
		$this->assertSame(
			'<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"Acme","url":"https://example.com/"}</script>',
			$this->renderControl($control)
		);
	}

	public function testExistingContextIsKept()
	{
		$control = new TJsonLd();
		$control->setStructuredData(['@context' => 'https://example.org/vocab', '@type' => 'Thing']);
		$this->assertSame('{"@context":"https://example.org/vocab","@type":"Thing"}', $this->jsonOf($control));
	}

	public function testContextSkippedWhenEmptyOrList()
	{
		$control = new TJsonLd();
		$control->setContext('');
		$control->setStructuredData(['@type' => 'Thing']);
		$this->assertSame('{"@type":"Thing"}', $this->jsonOf($control));

		$control->setContext('https://schema.org');
		$control->setStructuredData([['@type' => 'Thing'], ['@type' => 'Place']]);
		$this->assertSame('[{"@type":"Thing"},{"@type":"Place"}]', $this->jsonOf($control));
	}

	public function testJsonTextIsDecoded()
	{
		$control = new TJsonLd();
		$control->setStructuredData(' {"@type": "Product", "name": "Widget"} ');
		$this->assertSame(['@type' => 'Product', 'name' => 'Widget'], $control->getStructuredData());
		$control->setStructuredData('  ');
		$this->assertNull($control->getStructuredData());
	}

	public function testInvalidJsonTextThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TJsonLd())->setStructuredData('{"@type": ');
	}

	public function testValuesCannotCloseTheScript()
	{
		$control = new TJsonLd();
		$control->setStructuredData(['name' => '</script><script>alert(1)</script><!--', 'text' => '12" Café & Co']);
		$json = $this->jsonOf($control);
		$this->assertStringNotContainsString('<', $json);
		$this->assertStringNotContainsString('>', $json);
		$this->assertStringContainsString('12\" Caf' . '\\' . 'u00e9 & Co', $json);
		$this->assertSame('</script><script>alert(1)</script><!--', json_decode($json, true)['name']);
	}

	public function testJsonSerializableObject()
	{
		$control = new TJsonLd();
		$control->setStructuredData(new TJsonLdTestThing());
		$this->assertSame('{"@type":"Thing","name":"Widget"}', $this->jsonOf($control));
	}

	public function testNonce()
	{
		TJavaScript::setScriptNonce('abc123');
		$control = new TJsonLd();
		$control->setStructuredData(['@type' => 'Thing']);
		$this->assertStringStartsWith('<script type="application/ld+json" nonce="abc123">', $this->renderControl($control));
	}

	public function testBodyIsTheDocument()
	{
		$control = new TJsonLd();
		$control->addParsedObject("\n  {\"@type\": \"Organization\",\n   \"name\": \"</script>\"}\n");
		$this->assertSame('{"@context":"https://schema.org","@type":"Organization","name":"' . '\\' . 'u003C/script' . '\\' . 'u003E"}', $this->jsonOf($control));
	}

	public function testWhitespaceBodyRendersNothing()
	{
		$control = new TJsonLd();
		$control->addParsedObject("\n  \n");
		$this->assertSame('', $this->renderControl($control));
	}

	public function testStructuredDataTakesPrecedenceOverBody()
	{
		$control = new TJsonLd();
		$control->addParsedObject('{"@type": "Body"}');
		$control->setStructuredData(['@type' => 'Code']);
		$this->assertSame('{"@context":"https://schema.org","@type":"Code"}', $this->jsonOf($control));
	}

	public function testInvalidBodyThrowsOnRender()
	{
		$control = new TJsonLd();
		$control->addParsedObject('{"@type": ');
		$this->expectException(TInvalidDataValueException::class);
		$this->renderControl($control);
	}

	public function testBodyRestoresWriterOutput()
	{
		$control = new TJsonLd();
		$control->addParsedObject('{"@type": "Thing"}');
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$writer->write('<p>');
		$control->render($writer);
		$this->assertSame($tw, $writer->getWriter());
		$this->assertSame('<p><script type="application/ld+json">{"@context":"https://schema.org","@type":"Thing"}</script>', $tw->flush());
	}

	public function testTemplateBodyWithExpression()
	{
		$page = $this->instantiate('<com:TJsonLd ID="Ld">{"@type": "Organization", "name": "<%= \'Ac\' . \'me\' %>"}</com:TJsonLd>');
		// an expression evaluates in the PreRender step
		PradoUnit::invoke($page, 'preRenderRecursive');
		$this->assertSame('{"@context":"https://schema.org","@type":"Organization","name":"Acme"}', $this->jsonOf($page->findControl('Ld')));
	}

	public function testRendersInHead()
	{
		$page = new TPage();
		$head = new THead();
		$page->getControls()->add($head);
		$control = new TJsonLd();
		$control->setStructuredData(['@type' => 'Thing']);
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
