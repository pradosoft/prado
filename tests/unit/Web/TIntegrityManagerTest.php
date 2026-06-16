<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\TIntegrityManager;
use Prado\Xml\TXmlDocument;

class TIntegrityManagerTest extends PHPUnit\Framework\TestCase
{
	private const REMOTE = 'https://cdn.example.com/lib.js';
	private const HASH = 'sha384-AAAA';

	protected ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/../Security/app');
		Prado::setPathOfAlias('App', __DIR__);
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		TJavaScript::setRequireScriptIntegrity(false);
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		TJavaScript::setRequireScriptIntegrity(false);
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	private function config(string $inner): TXmlDocument
	{
		$doc = new TXmlDocument('1.0', 'utf8');
		$doc->loadFromString('<integrity>' . $inner . '</integrity>');
		return $doc;
	}

	public function testInitRegistersIntegrity()
	{
		$manager = new TIntegrityManager();
		$manager->init($this->config('<integrity url="' . self::REMOTE . '" hash="' . self::HASH . '" />'));
		self::assertEquals(self::HASH, $manager->getIntegrity(self::REMOTE));
		self::assertEquals(self::HASH, TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testBareDigestGetsMethodPrefix()
	{
		$manager = new TIntegrityManager();
		$manager->init($this->config('<integrity url="' . self::REMOTE . '" hash="BBBB" method="sha512" />'));
		self::assertEquals('sha512-BBBB', $manager->getIntegrity(self::REMOTE));
	}

	public function testEmptyHashThrows()
	{
		$manager = new TIntegrityManager();
		self::expectException(TConfigurationException::class);
		$manager->init($this->config('<integrity url="' . self::REMOTE . '" hash="" />'));
	}

	public function testRequireIntegrityEnforcedAtRender()
	{
		$manager = new TIntegrityManager();
		$manager->setRequireIntegrity(true);
		$manager->init($this->config(''));
		self::assertTrue(TJavaScript::getRequireScriptIntegrity());
		self::expectException(TConfigurationException::class);
		TJavaScript::renderScriptFile('https://cdn.example.com/unpinned.js');
	}

	public function testRequireIntegrityAllowsPinnedRemote()
	{
		$manager = new TIntegrityManager();
		$manager->setRequireIntegrity(true);
		$manager->init($this->config('<integrity url="' . self::REMOTE . '" hash="' . self::HASH . '" />'));
		$html = TJavaScript::renderScriptFile(self::REMOTE);
		self::assertStringContainsString('integrity="' . self::HASH . '"', $html);
		self::assertStringContainsString('crossorigin="anonymous"', $html);
	}

	public function testInitFromXmlFile()
	{
		if (!is_writable(__DIR__)) {
			self::markTestSkipped(__DIR__ . ' is not writable');
		}
		$file = __DIR__ . '/integrity.xml';
		file_put_contents($file, '<integrities>'
			. '<integrity url="' . self::REMOTE . '" hash="' . self::HASH . '" />'
			. '<integrity url="https://cdn.example.com/lib2.js" hash="CCCC" method="sha512" />'
			. '</integrities>');
		try {
			$manager = new TIntegrityManager();
			$manager->setIntegrityFile('App.integrity');
			self::assertEquals(realpath($file), realpath($manager->getIntegrityFile()));
			$manager->init($this->config(''));
			self::assertEquals(self::HASH, $manager->getIntegrity(self::REMOTE));
			self::assertEquals('sha512-CCCC', $manager->getIntegrity('https://cdn.example.com/lib2.js'));
		} finally {
			unlink($file);
			TJavaScript::setScriptIntegrity('https://cdn.example.com/lib2.js', null);
		}
	}

	public function testInitFromPhpFile()
	{
		if (!is_writable(__DIR__)) {
			self::markTestSkipped(__DIR__ . ' is not writable');
		}
		$priorConfigType = $this->app->getConfigurationType();
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$file = __DIR__ . '/integrity.php';
		file_put_contents($file, "<?php\nreturn ['integrities' => ["
			. "['url' => '" . self::REMOTE . "', 'hash' => '" . self::HASH . "'],"
			. "['url' => 'https://cdn.example.com/lib2.js', 'hash' => 'CCCC', 'method' => 'sha512'],"
			. "]];\n");
		try {
			$manager = new TIntegrityManager();
			$manager->setIntegrityFile('App.integrity');
			$manager->init([]);
			self::assertEquals(self::HASH, $manager->getIntegrity(self::REMOTE));
			self::assertEquals('sha512-CCCC', $manager->getIntegrity('https://cdn.example.com/lib2.js'));
		} finally {
			$this->app->setConfigurationType($priorConfigType);
			unlink($file);
			TJavaScript::setScriptIntegrity('https://cdn.example.com/lib2.js', null);
		}
	}

	public function testBareDigestDefaultMethodWhenMethodOmitted()
	{
		$manager = new TIntegrityManager();
		$manager->init($this->config('<integrity url="' . self::REMOTE . '" hash="ZZZZ" />'));
		self::assertEquals('sha384-ZZZZ', $manager->getIntegrity(self::REMOTE));
	}

	public function testLoadFromPhpArray()
	{
		$manager = new TIntegrityManager();
		$config = [
			'integrities' => [
				['url' => self::REMOTE, 'hash' => self::HASH],
				['url' => 'https://cdn.example.com/lib2.js', 'hash' => 'CCCC', 'method' => 'sha512'],
			],
		];
		PradoUnit::invoke($manager, 'loadIntegrityData', $config);
		self::assertEquals(self::HASH, $manager->getIntegrity(self::REMOTE));
		self::assertEquals('sha512-CCCC', $manager->getIntegrity('https://cdn.example.com/lib2.js'));
		TJavaScript::setScriptIntegrity('https://cdn.example.com/lib2.js', null);
	}

	public function testAddIntegrityFullSriStoredAsIs()
	{
		$manager = new TIntegrityManager();
		$manager->addIntegrity(self::REMOTE, 'sha256-DDDD', 'sha512');
		self::assertEquals('sha256-DDDD', $manager->getIntegrity(self::REMOTE));
	}

	public function testAddIntegrityEmptyUrlThrows()
	{
		$manager = new TIntegrityManager();
		self::expectException(TConfigurationException::class);
		$manager->addIntegrity('   ', self::HASH);
	}

	public function testAddIntegrityEmptyHashThrows()
	{
		$manager = new TIntegrityManager();
		self::expectException(TConfigurationException::class);
		$manager->addIntegrity(self::REMOTE, '   ');
	}

	public function testGetIntegrityUnknownReturnsNull()
	{
		$manager = new TIntegrityManager();
		self::assertNull($manager->getIntegrity('https://cdn.example.com/missing.js'));
	}

	public function testGetIntegritiesReturnsFullMap()
	{
		$manager = new TIntegrityManager();
		$manager->addIntegrity(self::REMOTE, self::HASH);
		$map = $manager->getIntegrities();
		self::assertCount(1, $map);
		self::assertContains(self::HASH, $map);
	}

	public function testGetIntegrityNormalizesUrl()
	{
		$manager = new TIntegrityManager();
		$manager->addIntegrity('//CDN.example.com/lib.js', self::HASH);
		// Protocol-relative + mixed case resolves to the same normalized key.
		self::assertEquals(self::HASH, $manager->getIntegrity('https://cdn.example.com/lib.js'));
	}

	public function testRequireIntegrityStringCoercedToBool()
	{
		$manager = new TIntegrityManager();
		$manager->setRequireIntegrity('true');
		self::assertTrue($manager->getRequireIntegrity());
	}

	public function testRequireIntegrityDefaultsToFalse()
	{
		$manager = new TIntegrityManager();
		$manager->init($this->config(''));
		self::assertFalse($manager->getRequireIntegrity());
		self::assertFalse(TJavaScript::getRequireScriptIntegrity());
	}

	public function testRequireIntegrityChangeAfterInitPropagates()
	{
		$manager = new TIntegrityManager();
		$manager->init($this->config(''));
		self::assertFalse(TJavaScript::getRequireScriptIntegrity());
		$manager->setRequireIntegrity(true);
		self::assertTrue(TJavaScript::getRequireScriptIntegrity());
	}

	public function testNonArrayConfigLoadsNothing()
	{
		$manager = new TIntegrityManager();
		PradoUnit::invoke($manager, 'loadIntegrityData', PradoUnit::invoke($manager, 'normalizeConfig', null));
		self::assertSame([], $manager->getIntegrities());
	}

	public function testCalculateIntegrityDefaultSha384()
	{
		$expected = 'sha384-' . base64_encode(hash('sha384', 'content', true));
		self::assertEquals($expected, TIntegrityManager::calculateIntegrity('content'));
	}

	public function testCalculateIntegritySha256()
	{
		$expected = 'sha256-' . base64_encode(hash('sha256', 'content', true));
		self::assertEquals($expected, TIntegrityManager::calculateIntegrity('content', 'sha256'));
	}

	public function testCalculateIntegritySha512()
	{
		$expected = 'sha512-' . base64_encode(hash('sha512', 'content', true));
		self::assertEquals($expected, TIntegrityManager::calculateIntegrity('content', 'sha512'));
	}

	public function testCalculateIntegrityNormalizesMethodCase()
	{
		self::assertEquals(
			TIntegrityManager::calculateIntegrity('content', 'sha512'),
			TIntegrityManager::calculateIntegrity('content', ' SHA512 ')
		);
	}

	public function testCalculateIntegrityRendersValidSriForRemote()
	{
		$hash = TIntegrityManager::calculateIntegrity('alert(1)');
		$manager = new TIntegrityManager();
		$manager->addIntegrity(self::REMOTE, $hash);
		self::assertStringContainsString('integrity="' . $hash . '"', TJavaScript::renderScriptFile(self::REMOTE));
	}

	public function testCalculateIntegrityInvalidAlgorithmThrows()
	{
		self::expectException(TConfigurationException::class);
		TIntegrityManager::calculateIntegrity('content', 'md5');
	}

	public function testCalculateIntegrityForFileMatchesContent()
	{
		if (!is_writable(__DIR__)) {
			self::markTestSkipped(__DIR__ . ' is not writable');
		}
		$file = __DIR__ . '/asset.js';
		file_put_contents($file, 'alert(1)');
		try {
			self::assertEquals(
				TIntegrityManager::calculateIntegrity('alert(1)', 'sha512'),
				TIntegrityManager::calculateIntegrityForFile($file, 'sha512')
			);
		} finally {
			unlink($file);
		}
	}

	public function testCalculateIntegrityForFileMissingThrows()
	{
		self::expectException(TConfigurationException::class);
		TIntegrityManager::calculateIntegrityForFile(__DIR__ . '/does_not_exist.js');
	}

	public function testIntegrityFileInvalidThrows()
	{
		$manager = new TIntegrityManager();
		self::expectException(TConfigurationException::class);
		$manager->setIntegrityFile('App.does_not_exist');
	}

	public function testIntegrityFileUnchangeableAfterInit()
	{
		$manager = new TIntegrityManager();
		$manager->init($this->config(''));
		self::expectException(TInvalidOperationException::class);
		$manager->setIntegrityFile('App.integrity');
	}
}
