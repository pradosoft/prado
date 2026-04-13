<?php

use Prado\Web\UI\TTheme;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;

class TThemeTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$themePath = __DIR__ . '/data/testtheme';
		if (!is_dir($themePath)) {
			mkdir($themePath, 0755, true);
		}
		$themeUrl = '/themes/testtheme';
		$theme = new TTheme($themePath, $themeUrl);
		// Constructor extracts theme name, resolves base path, and stores base URL
		$this->assertEquals('testtheme', $theme->getName());
		$this->assertEquals(realpath($themePath), $theme->getBasePath());
		$this->assertEquals($themeUrl, $theme->getBaseUrl());
	}

	public function testGetName()
	{
		$themePath = __DIR__ . '/data/testtheme';
		if (!is_dir($themePath)) {
			mkdir($themePath, 0755, true);
		}
		$themeUrl = '/themes/testtheme';
		$theme = new TTheme($themePath, $themeUrl);
		$this->assertEquals('testtheme', $theme->getName());
	}

	public function testGetBaseUrl()
	{
		$themePath = __DIR__ . '/data/testtheme';
		if (!is_dir($themePath)) {
			mkdir($themePath, 0755, true);
		}
		$themeUrl = '/themes/testtheme';
		$theme = new TTheme($themePath, $themeUrl);
		$this->assertEquals($themeUrl, $theme->getBaseUrl());
	}

	public function testGetBasePath()
	{
		$themePath = __DIR__ . '/data/testtheme';
		if (!is_dir($themePath)) {
			mkdir($themePath, 0755, true);
		}
		$themeUrl = '/themes/testtheme';
		$theme = new TTheme($themePath, $themeUrl);
		$this->assertEquals(realpath($themePath), $theme->getBasePath());
	}

	public function testApplySkin()
	{
		$themePath = __DIR__ . '/data/testtheme';
		if (!is_dir($themePath)) {
			mkdir($themePath, 0755, true);
		}
		$theme = new TTheme($themePath, '/themes/testtheme');

		// Inject skin data so we can test actual property application
		$ref = new ReflectionObject($theme);
		$prop = $ref->getProperty('_skins');
		$prop->setAccessible(true);
		$prop->setValue($theme, [
			\Prado\Web\UI\WebControls\TLabel::class => [0 => ['Text' => 'Skin Text']],
		]);

		$label = new TLabel();
		$this->assertTrue($theme->applySkin($label));
		$this->assertEquals('Skin Text', $label->getText());

		// No matching skin for TControl → returns false
		$this->assertFalse($theme->applySkin(new \Prado\Web\UI\TControl()));
	}

	public function testGetStyleSheetFiles()
	{
		$themePath = __DIR__ . '/data/cssthemetest';
		@mkdir($themePath, 0755, true);
		$themeUrl = '/themes/cssthemetest';

		file_put_contents($themePath . '/style.css', 'body {}');
		file_put_contents($themePath . '/extra.css', 'h1 {}');

		try {
			$theme = new TTheme($themePath, $themeUrl);
			$files = $theme->getStyleSheetFiles();
			$this->assertContains($themeUrl . DIRECTORY_SEPARATOR . 'style.css', $files);
			$this->assertContains($themeUrl . DIRECTORY_SEPARATOR . 'extra.css', $files);
		} finally {
			@unlink($themePath . '/style.css');
			@unlink($themePath . '/extra.css');
			@rmdir($themePath);
		}
	}

	public function testGetJavaScriptFiles()
	{
		$themePath = __DIR__ . '/data/jsthemetest';
		@mkdir($themePath, 0755, true);
		$themeUrl = '/themes/jsthemetest';

		file_put_contents($themePath . '/app.js', 'var x = 1;');
		file_put_contents($themePath . '/util.js', 'var y = 2;');

		try {
			$theme = new TTheme($themePath, $themeUrl);
			$files = $theme->getJavaScriptFiles();
			$this->assertContains($themeUrl . DIRECTORY_SEPARATOR . 'app.js', $files);
			$this->assertContains($themeUrl . DIRECTORY_SEPARATOR . 'util.js', $files);
		} finally {
			@unlink($themePath . '/app.js');
			@unlink($themePath . '/util.js');
			@rmdir($themePath);
		}
	}
}
