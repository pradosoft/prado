<?php

/**
 * TSoapServerTest
 *
 * Unit tests for {@see \Prado\Web\Services\TSoapServer}.
 *
 * Covers the {@see TSoapServer::setWsdlStyle WsdlStyle} property: its default,
 * the values it accepts, the values it refuses, and that a generated WSDL
 * follows it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Services\TSoapServer;

/**
 * Provider reflected on by the generation tests.
 */
class TTestSoapQuoteProvider
{
	/**
	 * Returns a quote.
	 * @param string $symbol the symbol to quote
	 * @return float the quote
	 * @soapmethod
	 */
	public function getQuote($symbol)
	{
		return 0.0;
	}
}

/**
 * Testable server: the URI and encoding are fixed, so generation needs no
 * request, and the provider class is resolved without configuration.
 */
class TTestSoapServer extends TSoapServer
{
	public function getUri()
	{
		return 'http://example.com/soap';
	}

	public function getEncoding()
	{
		return 'UTF-8';
	}

	/** Exposes the capability check, so a test can skip where the style is absent. */
	public function hasGeneratorStyle(): bool
	{
		return $this->getGeneratorHasStyle();
	}
}

/**
 * Server whose generator predates the style, as an installed 1.1 would be.
 */
class TTestSoapLegacyServer extends TTestSoapServer
{
	protected function getGeneratorHasStyle()
	{
		return false;
	}
}

class TSoapServerTest extends PHPUnit\Framework\TestCase
{
	protected function newServer(): TTestSoapServer
	{
		$server = new TTestSoapServer();
		$server->setProvider(TTestSoapQuoteProvider::class);
		return $server;
	}

	public function testWsdlStyleDefaultsToRpc()
	{
		$this->assertSame(TSoapServer::WSDL_STYLE_RPC, $this->newServer()->getWsdlStyle());
		$this->assertSame('rpc', $this->newServer()->getWsdlStyle());
	}

	public function testWsdlStyleAcceptsBothStyles()
	{
		$server = $this->newServer();

		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);
		$this->assertSame('document', $server->getWsdlStyle());

		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_RPC);
		$this->assertSame('rpc', $server->getWsdlStyle());
	}

	/**
	 * @dataProvider invalidStyleProvider
	 * @param mixed $style the style to set
	 */
	public function testWsdlStyleRefusesAnythingElse($style)
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->newServer()->setWsdlStyle($style);
	}

	public static function invalidStyleProvider(): array
	{
		return [
			'literal' => ['literal'],
			'encoded' => ['encoded'],
			'wrong case' => ['RPC'],
			'empty' => [''],
		];
	}

	public function testTheDefaultStyleGeneratesRpcAndSoapEncoding()
	{
		$wsdl = $this->newServer()->getWsdl();

		$this->assertStringContainsString('style="rpc"', $wsdl);
		$this->assertStringContainsString('use="encoded"', $wsdl);
	}

	public function testTheDocumentStyleGeneratesDocumentAndLiteral()
	{
		$server = $this->newServer();
		if (!$server->hasGeneratorStyle()) {
			$this->markTestSkipped('the installed prado-wsdlgenerator predates the document style');
		}
		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);
		$wsdl = $server->getWsdl();

		$this->assertStringContainsString('style="document"', $wsdl);
		$this->assertStringContainsString('use="literal"', $wsdl);
		$this->assertStringNotContainsString('encodingStyle', $wsdl);
	}

	/**
	 * PHP discards an argument a function does not declare, so a generator that
	 * predates the style would answer rpc without complaint. The style is looked
	 * for rather than assumed, and refused where it is missing.
	 */
	public function testADocumentStyleIsRefusedWhereTheGeneratorLacksIt()
	{
		$server = new TTestSoapLegacyServer();
		$server->setProvider(TTestSoapQuoteProvider::class);
		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);

		$this->expectException(TConfigurationException::class);
		$server->getWsdl();
	}

	/**
	 * The default style is passed the way it always was, so a generator that
	 * predates the style still serves it.
	 */
	public function testTheDefaultStyleGeneratesWhereTheGeneratorLacksTheStyle()
	{
		$server = new TTestSoapLegacyServer();
		$server->setProvider(TTestSoapQuoteProvider::class);

		$this->assertStringContainsString('style="rpc"', $server->getWsdl());
	}

	public function testTheStyleConstantsAreTheDocumentedValues()
	{
		$this->assertSame('rpc', TSoapServer::WSDL_STYLE_RPC);
		$this->assertSame('document', TSoapServer::WSDL_STYLE_DOCUMENT);
	}

	public function testAServedWsdlIgnoresTheStyle()
	{
		$file = tempnam(sys_get_temp_dir(), 'wsdl');
		file_put_contents($file, '<definitions/>');

		try {
			$server = $this->newServer();
			$server->setWsdlUri($file);
			$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);

			$this->assertSame('<definitions/>', $server->getWsdl());
		} finally {
			unlink($file);
		}
	}
}
