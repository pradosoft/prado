<?php

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Services\TPageService;
use Prado\Web\TIntegrityManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Xml\TXmlDocument;

/**
 * Integration test: a TIntegrityManager configured as a module feeds the
 * Subresource Integrity registry that {@see TClientScriptManager} consults when
 * it renders registered script files. Exercises the module → TJavaScript →
 * TClientScriptManager render path together, without a browser.
 */
class TIntegrityManagerIntegrationTest extends PHPUnit\Framework\TestCase
{
	private const REMOTE = 'https://cdn.example.com/lib.js';
	private const HASH = 'sha384-AAAABBBBCCCC';

	private $originalService;

	protected function setUp(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		TJavaScript::setRequireScriptIntegrity(false);
		$app = Prado::getApplication();
		$this->originalService = $app->getService();
		$app->setService(new TPageService());
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		TJavaScript::setRequireScriptIntegrity(false);
		Prado::getApplication()->setService($this->originalService);
	}

	private function configuredManager(): TIntegrityManager
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<integrity><integrity url="' . self::REMOTE . '" hash="' . self::HASH . '" /></integrity>');
		$manager = new TIntegrityManager();
		$manager->init($config);
		return $manager;
	}

	private function renderHeadScriptFiles(TPage $page): string
	{
		$textWriter = new TTextWriter();
		$page->getClientScript()->renderHeadScriptFiles(new THtmlWriter($textWriter));
		return $textWriter->flush();
	}

	public function testConfiguredManagerRendersIntegrityOnRemoteScript()
	{
		$this->configuredManager();
		$page = new TPage();
		$page->getClientScript()->registerHeadScriptFile('lib', self::REMOTE);

		$output = $this->renderHeadScriptFiles($page);

		self::assertStringContainsString('src="' . self::REMOTE . '"', $output);
		self::assertStringContainsString('integrity="' . self::HASH . '"', $output);
		self::assertStringContainsString('crossorigin="anonymous"', $output);
	}

	public function testUnregisteredRemoteScriptRendersWithoutIntegrity()
	{
		$this->configuredManager();
		$page = new TPage();
		$page->getClientScript()->registerHeadScriptFile('other', 'https://cdn.example.com/other.js');

		$output = $this->renderHeadScriptFiles($page);

		self::assertStringContainsString('src="https://cdn.example.com/other.js"', $output);
		self::assertStringNotContainsString('integrity', $output);
	}

	public function testRequireIntegrityThrowsForUnpinnedRemoteScriptAtRender()
	{
		$manager = $this->configuredManager();
		$manager->setRequireIntegrity(true);
		$page = new TPage();
		$page->getClientScript()->registerHeadScriptFile('unpinned', 'https://cdn.example.com/unpinned.js');

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->renderHeadScriptFiles($page);
	}
}
