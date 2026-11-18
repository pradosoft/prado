<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Caching\ICache;
use Prado\Exceptions\TIOException;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TBehavior;
use Prado\Web\UI\TTheme;

/**
 * A cache held in an array.
 */
class TThemeCacheTestCache extends TComponent implements ICache
{
	public array $items = [];

	public function get($id)
	{
		return $this->items[$id] ?? false;
	}

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->items[$id] = $value;
		return true;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return $this->set($id, $value);
	}

	public function delete($id)
	{
		unset($this->items[$id]);
		return true;
	}

	public function flush()
	{
		$this->items = [];
		return true;
	}

	public static function getIsAvailable(): bool
	{
		return true;
	}
}

/**
 * Counts the themes it processes.
 */
class TThemeCacheTestProcessBehavior extends TBehavior
{
	public static int $processed = 0;

	public function dyThemeProcess($chain)
	{
		self::$processed++;
		return $chain->dyThemeProcess();
	}
}

class TThemeCacheTest extends \PHPUnit\Framework\TestCase
{
	use TTempDirectoryTrait;

	private TThemeCacheTestCache $_cache;
	private array $_saved = [];
	private string $_themePath;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->_saved = [
			'cache' => PradoUnit::getProp($app, '_cache'),
			'globalization' => PradoUnit::getProp($app, '_globalization'),
			'mode' => $app->getMode(),
		];
		$app->setCache($this->_cache = new TThemeCacheTestCache());
		$app->setMode(TApplicationMode::Normal);
		$this->_themePath = $this->makeTempDirectory('prado_theme_cache_');
		foreach (['b.css', 'a.css', 'a.rtl.css', 'z.js', 'y.js'] as $file) {
			file_put_contents($this->_themePath . DIRECTORY_SEPARATOR . $file, '');
		}
		$this->writeSkin('Plain');
		TThemeCacheTestProcessBehavior::$processed = 0;
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		PradoUnit::setProp($app, '_cache', $this->_saved['cache']);
		PradoUnit::setProp($app, '_globalization', $this->_saved['globalization']);
		$app->setMode($this->_saved['mode']);
		$this->removeTempDirectories();
	}

	/**
	 * Writes the skin file of the theme, giving a TLabel skin the text.
	 * @param string $text the text of the label skin
	 */
	private function writeSkin(string $text): void
	{
		file_put_contents($this->_themePath . DIRECTORY_SEPARATOR . 'label.skin', '<com:Prado\Web\UI\WebControls\TLabel Text="' . $text . '" />');
	}

	/**
	 * Sets the application culture.
	 * @param string $culture the culture, 'ar' is right-to-left
	 */
	private function useCulture(string $culture): void
	{
		$globalization = new TGlobalization();
		$globalization->setCulture($culture);
		Prado::getApplication()->setGlobalization($globalization);
	}

	/**
	 * @return TTheme a new theme of the temp theme directory
	 */
	private function newTheme(): TTheme
	{
		return new TTheme($this->_themePath, '/themes/t');
	}

	public function testCachedThemeListsTheFilesOfTheCurrentCulture(): void
	{
		$ltr = ['/themes/t/a.css', '/themes/t/b.css'];
		$rtl = ['/themes/t/a.css', '/themes/t/b.css', '/themes/t/a.rtl.css'];
		$this->useCulture('en');
		$this->assertSame($ltr, array_values($this->newTheme()->getStyleSheetFiles()));
		$this->assertNotEmpty($this->_cache->items);

		$this->useCulture('ar');
		$theme = $this->newTheme();
		$this->assertSame($rtl, array_values($theme->getStyleSheetFiles()));
		$this->assertSame(['/themes/t/y.js', '/themes/t/z.js'], $theme->getJavaScriptFiles());

		$this->useCulture('en');
		$this->assertSame($ltr, array_values($this->newTheme()->getStyleSheetFiles()));
	}

	public function testPerformanceModeCachesTheFilesOfEveryCulture(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$this->useCulture('ar');
		$this->assertSame(['/themes/t/a.css', '/themes/t/b.css', '/themes/t/a.rtl.css'], array_values($this->newTheme()->getStyleSheetFiles()));

		$this->useCulture('en');
		$theme = $this->newTheme();
		$this->assertSame(['/themes/t/a.css', '/themes/t/b.css'], array_values($theme->getStyleSheetFiles()));
		$this->assertSame(['/themes/t/y.js', '/themes/t/z.js'], $theme->getJavaScriptFiles());
		$this->assertSame('Plain', $theme->getSkins()['Prado\Web\UI\WebControls\TLabel'][0]['text'][2]);
	}

	public function testEveryThemeIsProcessed(): void
	{
		TComponent::attachClassBehavior('themeProcess', TThemeCacheTestProcessBehavior::class, TTheme::class);
		try {
			$this->newTheme();
			$this->newTheme();
			Prado::getApplication()->setMode(TApplicationMode::Performance);
			$this->newTheme();
		} finally {
			TComponent::detachClassBehavior('themeProcess', TTheme::class);
		}
		$this->assertSame(3, TThemeCacheTestProcessBehavior::$processed);
	}

	public function testNewerSkinFileReplacesTheCachedSkins(): void
	{
		$this->assertSame('Plain', $this->newTheme()->getSkins()['Prado\Web\UI\WebControls\TLabel'][0]['text'][2]);
		$this->writeSkin('Changed');
		touch($this->_themePath . DIRECTORY_SEPARATOR . 'label.skin', time() + 10);
		$this->assertSame('Changed', $this->newTheme()->getSkins()['Prado\Web\UI\WebControls\TLabel'][0]['text'][2]);
	}

	public function testMissingThemeDirectoryThrows(): void
	{
		$missing = $this->_themePath . DIRECTORY_SEPARATOR . 'missing';
		foreach ([false, true] as $cached) {
			if ($cached) {
				$this->_cache->set(TTheme::THEME_CACHE_PREFIX . $missing, [[], [], [], 0]);
			}
			try {
				new TTheme($missing, '/themes/missing');
				$this->fail('a missing theme directory was read');
			} catch (TIOException $e) {
				$this->assertSame('theme_path_inexistent', $e->getErrorCode());
			}
		}
	}
}
