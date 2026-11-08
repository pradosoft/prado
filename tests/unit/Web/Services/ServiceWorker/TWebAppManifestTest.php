<?php

namespace Prado\Test\Unit\Web\Services\ServiceWorker;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Services\ServiceWorker\TWebAppManifest;

class TWebAppManifestTest extends TestCase
{
	private array $_files = [];

	protected function tearDown(): void
	{
		foreach ($this->_files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
	}

	private function createFile(string $json): string
	{
		$file = tempnam(sys_get_temp_dir(), 'manifest');
		file_put_contents($file, $json);
		return $this->_files[] = $file;
	}

	public function testDefaultsAreEmpty()
	{
		$manifest = new TWebAppManifest();
		$this->assertTrue($manifest->getIsEmpty());
		$this->assertSame([], $manifest->toArray());
		$this->assertSame('', $manifest->getName());
		$this->assertSame([], $manifest->getIcons());
		$this->assertSame([], $manifest->getDisplayOverride());
	}

	public function testPropertiesBecomeMembers()
	{
		$manifest = new TWebAppManifest();
		$manifest->setId('/app');
		$manifest->setName('Orders');
		$manifest->setShortName('Ord');
		$manifest->setDescription('Order desk');
		$manifest->setStartUrl('/index.php');
		$manifest->setScope('/');
		$manifest->setDisplay('Standalone');
		$manifest->setDisplayOverride('window-controls-overlay, minimal-ui');
		$manifest->setOrientation('portrait-primary');
		$manifest->setThemeColor('#2b5797');
		$manifest->setBackgroundColor('#ffffff');
		$manifest->setLang('en-US');
		$manifest->setDir('ltr');
		$manifest->setCategories('business, productivity');
		$manifest->setIcons('[{"src": "/i/192.png", "sizes": "192x192"}]');
		$manifest->setScreenshots([['src' => '/s/1.png', 'form_factor' => 'wide']]);
		$manifest->setShortcuts([['name' => 'New', 'url' => '/new']]);
		$this->assertFalse($manifest->getIsEmpty());
		$this->assertSame([
			'id' => '/app',
			'name' => 'Orders',
			'short_name' => 'Ord',
			'description' => 'Order desk',
			'start_url' => '/index.php',
			'scope' => '/',
			'display' => 'standalone',
			'display_override' => ['window-controls-overlay', 'minimal-ui'],
			'orientation' => 'portrait-primary',
			'theme_color' => '#2b5797',
			'background_color' => '#ffffff',
			'lang' => 'en-US',
			'dir' => 'ltr',
			'categories' => ['business', 'productivity'],
			'icons' => [['src' => '/i/192.png', 'sizes' => '192x192']],
			'screenshots' => [['src' => '/s/1.png', 'form_factor' => 'wide']],
			'shortcuts' => [['name' => 'New', 'url' => '/new']],
		], $manifest->toArray());
	}

	public function testEmptyPropertyRemovesTheMember()
	{
		$manifest = new TWebAppManifest();
		$manifest->setName('Orders');
		$manifest->setIcons([['src' => '/a.png']]);
		$manifest->setName('');
		$manifest->setIcons('');
		$this->assertSame([], $manifest->toArray());
		$this->assertTrue($manifest->getIsEmpty());
	}

	public function testInvalidTokensThrow()
	{
		$manifest = new TWebAppManifest();
		foreach (['setDisplay' => 'window', 'setOrientation' => 'sideways', 'setDir' => 'up', 'setDisplayOverride' => 'standalone, nope'] as $setter => $value) {
			try {
				$manifest->$setter($value);
				$this->fail("$setter accepted '$value'");
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('webappmanifest_value_invalid', $e->getErrorCode());
			}
		}
	}

	public function testListPropertiesRequireAList()
	{
		$manifest = new TWebAppManifest();
		foreach (['{"src": "/a.png"}', 'not json'] as $value) {
			try {
				$manifest->setIcons($value);
				$this->fail("Icons accepted '$value'");
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('webappmanifest_json_invalid', $e->getErrorCode());
			}
		}
	}

	public function testMembersAreMergedUnderTheProperties()
	{
		$manifest = new TWebAppManifest();
		$manifest->setMembers('{"name": "Members", "prefer_related_applications": false}');
		$manifest->setName('Property');
		$this->assertSame(['name' => 'Property', 'prefer_related_applications' => false], $manifest->toArray());
		$this->assertSame(['name' => 'Members', 'prefer_related_applications' => false], $manifest->getMembers());

		$this->expectException(TInvalidDataValueException::class);
		$manifest->setMembers('[1, 2]');
	}

	public function testFileIsTheBase()
	{
		$file = $this->createFile('{"name": "File", "short_name": "F", "icons": [{"src": "/f.png"}]}');
		$manifest = new TWebAppManifest();
		$manifest->setFile($file);
		$manifest->setMembers(['short_name' => 'M']);
		$manifest->setName('Property');
		$this->assertSame($file, $manifest->getFile());
		$this->assertFalse($manifest->getIsEmpty());
		$this->assertSame(['name' => 'Property', 'short_name' => 'M', 'icons' => [['src' => '/f.png']]], $manifest->toArray());
	}

	public function testUnreadableOrInvalidFileThrows()
	{
		foreach ([sys_get_temp_dir() . '/no-such-manifest.json', $this->createFile('[1]'), $this->createFile('not json')] as $file) {
			$manifest = new TWebAppManifest();
			$manifest->setFile($file);
			try {
				$manifest->toArray();
				$this->fail("File '$file' was accepted");
			} catch (TConfigurationException $e) {
				$this->assertSame('webappmanifest_file_invalid', $e->getErrorCode());
			}
		}
	}
}
