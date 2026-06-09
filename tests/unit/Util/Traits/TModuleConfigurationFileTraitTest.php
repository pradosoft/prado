<?php

/**
 * TModuleConfigurationFileTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\TApplication;
use Prado\Util\Traits\TModuleConfigurationFileTrait;
use Prado\Xml\TXmlDocument;

/**
 * Consumer exposing the trait's protected reader for testing.
 */
class TModuleConfigurationFileTraitConsumer
{
	use TModuleConfigurationFileTrait;

	public function read(?string $type, string $fname): null|array|TXmlDocument
	{
		return $this->readConfigurationFile($type, $fname);
	}
}

/**
 * Unit tests for {@see \Prado\Util\Traits\TModuleConfigurationFileTrait}.
 */
class TModuleConfigurationFileTraitTest extends PHPUnit\Framework\TestCase
{
	private TModuleConfigurationFileTraitConsumer $consumer;

	/** Temp file paths created during a test, removed in tearDown. */
	private array $_tempFiles = [];

	protected function setUp(): void
	{
		$this->consumer = new TModuleConfigurationFileTraitConsumer();
	}

	protected function tearDown(): void
	{
		foreach ($this->_tempFiles as $path) {
			if (is_file($path)) {
				@unlink($path);
			}
		}
		$this->_tempFiles = [];
	}

	/** Writes $content to a uniquely named temp file with the given extension. */
	private function tempFile(string $extension, string $content): string
	{
		$path = sys_get_temp_dir() . '/prado_modcfg_' . uniqid('', true) . '.' . $extension;
		file_put_contents($path, $content);
		$this->_tempFiles[] = $path;
		return $path;
	}

	// ── Auto-detection by extension (type === null) ────────────────────────

	public function testAutoDetect_phpExtensionIsIncludedAsArray(): void
	{
		$path = $this->tempFile('php', "<?php return ['a' => 1, 'b' => 2];");
		self::assertSame(['a' => 1, 'b' => 2], $this->consumer->read(null, $path));
	}

	public function testAutoDetect_xmlExtensionLoadsXmlDocument(): void
	{
		$path = $this->tempFile('xml', '<config><item id="x"/></config>');
		$result = $this->consumer->read(null, $path);
		self::assertInstanceOf(TXmlDocument::class, $result);
		self::assertSame('config', $result->getTagName());
	}

	public function testAutoDetect_unknownExtensionFallsBackToXml(): void
	{
		// Any non-`.php` extension is treated as XML.
		$path = $this->tempFile('conf', '<root/>');
		self::assertInstanceOf(TXmlDocument::class, $this->consumer->read(null, $path));
	}

	public function testAutoDetect_phpExtensionCaseInsensitive(): void
	{
		$path = $this->tempFile('PHP', "<?php return ['k' => 'v'];");
		self::assertSame(['k' => 'v'], $this->consumer->read(null, $path));
	}

	// ── Explicit type overrides the extension ──────────────────────────────

	public function testExplicitPhpType_overridesXmlExtension(): void
	{
		// A file named `.xml` but holding PHP is included when the type says PHP.
		$path = $this->tempFile('xml', "<?php return ['forced' => true];");
		self::assertSame(['forced' => true], $this->consumer->read(TApplication::CONFIG_TYPE_PHP, $path));
	}

	public function testExplicitXmlType_overridesPhpExtension(): void
	{
		$path = $this->tempFile('php', '<data><x/></data>');
		$result = $this->consumer->read(TApplication::CONFIG_TYPE_XML, $path);
		self::assertInstanceOf(TXmlDocument::class, $result);
		self::assertSame('data', $result->getTagName());
	}

	// ── PHP include contract ───────────────────────────────────────────────

	public function testPhpInclude_nonArrayReturnYieldsNull(): void
	{
		// The include returns a scalar, so the reader yields null.
		$path = $this->tempFile('php', "<?php return 'a scalar';");
		self::assertNull($this->consumer->read(TApplication::CONFIG_TYPE_PHP, $path));
	}

	public function testPhpInclude_noReturnYieldsNull(): void
	{
		// A PHP file with no `return` evaluates to 1 (non-array) → null.
		$path = $this->tempFile('php', '<?php $x = 1;');
		self::assertNull($this->consumer->read(TApplication::CONFIG_TYPE_PHP, $path));
	}

	public function testPhpInclude_emptyArrayReturnsEmptyArray(): void
	{
		$path = $this->tempFile('php', '<?php return [];');
		self::assertSame([], $this->consumer->read(TApplication::CONFIG_TYPE_PHP, $path));
	}

	public function testPhpInclude_nestedArrayPreserved(): void
	{
		$path = $this->tempFile('php', "<?php return ['users' => [['name' => 'Joe']]];");
		self::assertSame(['users' => [['name' => 'Joe']]], $this->consumer->read(TApplication::CONFIG_TYPE_PHP, $path));
	}

	// ── XML load contract ──────────────────────────────────────────────────

	public function testXml_parsesElementsAndAttributes(): void
	{
		$path = $this->tempFile('xml', '<users><user name="Joe"/><user name="John"/></users>');
		$result = $this->consumer->read(TApplication::CONFIG_TYPE_XML, $path);
		self::assertInstanceOf(TXmlDocument::class, $result);
		self::assertSame('users', $result->getTagName());
		self::assertCount(2, $result->getElementsByTagName('user'));
	}
}
