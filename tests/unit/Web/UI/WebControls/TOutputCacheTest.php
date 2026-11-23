<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Clock\TMockClock;
use Prado\Web\THttpSession;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TOutputCache;
use Prado\Web\UI\WebControls\TOutputCacheCalculateKeyEventParameter;
use Prado\Web\UI\WebControls\TOutputCacheCheckDependencyEventParameter;
use Prado\Web\UI\WebControls\TOutputCacheTextWriterMulti;

/**
 * A page whose postback state a test sets, with a client script manager that records calls.
 */
class TOutputCacheTestPage extends TPage
{
	public bool $postBack = false;
	public array $titles = [];

	public function getIsPostBack()
	{
		return $this->postBack;
	}

	public function setTitle($value)
	{
		$this->titles[] = $value;
	}
}

/**
 * A child control that records the caching stack depth in each phase.
 */
class TOutputCacheTestChild extends TControl
{
	public array $depths = [];
	public array $calls = [];

	public function onInit($param)
	{
		$this->depths['init'] = $this->getPage()->getCachingStack()->getCount();
		parent::onInit($param);
	}

	public function onLoad($param)
	{
		$this->depths['load'] = $this->getPage()->getCachingStack()->getCount();
		parent::onLoad($param);
	}

	public function onPreRender($param)
	{
		$this->depths['prerender'] = $this->getPage()->getCachingStack()->getCount();
		parent::onPreRender($param);
	}

	public function render($writer)
	{
		$this->depths['render'] = $this->getPage()->getCachingStack()->getCount();
		$writer->write('child');
	}

	public function record(...$args)
	{
		$this->calls[] = $args;
	}
}

/**
 * A cache module that keeps its values in an array.
 */
class TOutputCacheTestModule extends \Prado\TModule implements \Prado\Caching\ICache
{
	public array $values = [];

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function get($id)
	{
		return $this->values[$id] ?? false;
	}

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->values[$id] = $value;
		return true;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return $this->set($id, $value);
	}

	public function delete($id)
	{
		unset($this->values[$id]);
		return true;
	}

	public function flush()
	{
		$this->values = [];
		return true;
	}
}

class TOutputCacheTest extends TestCase
{
	use \Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;

	private mixed $_savedCache;
	private mixed $_savedSession;
	private array $_savedModules;
	private TTestArrayCache $_cache;

	protected function setUp(): void
	{
		parent::setUp();
		$this->saveOutputBufferLevel();
		$app = Prado::getApplication();
		$this->_savedCache = PradoUnit::getProp($app, '_cache');
		$this->_savedSession = PradoUnit::getProp($app, '_session');
		$this->_savedModules = PradoUnit::getProp($app, '_modules');
		$this->_cache = new TTestArrayCache();
		PradoUnit::setProp($app, '_cache', $this->_cache);
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		PradoUnit::setProp($app, '_cache', $this->_savedCache);
		PradoUnit::setProp($app, '_session', $this->_savedSession);
		PradoUnit::setProp($app, '_modules', $this->_savedModules);
		$this->restoreOutputBufferLevel();
		parent::tearDown();
	}

	/**
	 * Creates a page with an output cache; $configure runs before the cache checks its
	 * cacheability, which the first child control triggers.
	 */
	private function createCache(?\Closure $configure = null, bool $withChild = true): array
	{
		$page = new TOutputCacheTestPage();
		$page->setPagePath('Home');
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerEndScript'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$cache = new TOutputCache();
		$cache->setID('oc');
		$page->getControls()->add($cache);
		if ($configure !== null) {
			$configure($cache, $page);
		}
		$child = new TOutputCacheTestChild();
		if ($withChild && $cache->getAllowChildControls()) {
			$cache->getControls()->add($child);
		}
		return [$cache, $page, $child, $cs];
	}

	private function loadState(TOutputCache $cache): void
	{
		$method = new \ReflectionMethod($cache, 'loadStateRecursive');
		$state = null;
		$args = [&$state, true];
		$method->invokeArgs($cache, $args);
	}

	private function key(): string
	{
		return TOutputCache::CACHE_ID_PREFIX . 'Homeoc';
	}

	private function renderCache(TOutputCache $cache): string
	{
		$tw = new TTextWriter();
		$cache->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testPropertyDefaultsAndSetters()
	{
		$cache = new TOutputCache();
		$this->assertSame('', $cache->getCacheModuleID());
		$this->assertSame(60, $cache->getDuration());
		$this->assertSame('', $cache->getVaryByParam());
		$this->assertFalse($cache->getVaryBySession());
		$this->assertFalse($cache->getCachingPostBack());
		$this->assertSame(0, $cache->getCacheTime());
		$this->assertFalse($cache->getContentCached());
		$this->assertNull(PradoUnit::invoke($cache, 'getCacheDependency'));
		$cache->setCacheModuleID('mycache');
		$cache->setDuration('30');
		$cache->setVaryByParam(' a, b ');
		$cache->setVaryBySession('true');
		$cache->setCachingPostBack('true');
		$this->assertSame('mycache', $cache->getCacheModuleID());
		$this->assertSame(30, $cache->getDuration());
		$this->assertSame('a, b', $cache->getVaryByParam());
		$this->assertTrue($cache->getVaryBySession());
		$this->assertTrue($cache->getCachingPostBack());
	}

	public function testNegativeDurationThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TOutputCache())->setDuration(-1);
	}

	public function testEventParameters()
	{
		$key = new TOutputCacheCalculateKeyEventParameter();
		$this->assertSame('', $key->getCacheKey());
		$key->setCacheKey(5);
		$this->assertSame('5', $key->getCacheKey());
		$check = new TOutputCacheCheckDependencyEventParameter();
		$this->assertTrue($check->getIsValid());
		$this->assertSame(0, $check->getCacheTime());
		$check->setIsValid('false');
		$check->setCacheTime('99');
		$this->assertFalse($check->getIsValid());
		$this->assertSame(99, $check->getCacheTime());
	}

	public function testTextWriterMultiWritesToEveryWriter()
	{
		$a = new TTextWriter();
		$b = new TTextWriter();
		$multi = new TOutputCacheTextWriterMulti([$a, $b]);
		$multi->write('x');
		$this->assertSame('x', $multi->flush());
		$this->assertSame('', $a->flush());
	}

	// --- Cacheability ---

	public function testAMissCachesTheRenderedOutput()
	{
		$clock = new TMockClock();
		$clock->setTime(500);
		[$cache, $page, $child] = $this->createCache(function ($cache) use ($clock) {
			$cache->setClock($clock);
			$cache->setDuration(30);
		});
		$this->assertTrue($cache->getAllowChildControls());
		$this->assertFalse($cache->getContentCached());
		$cache->registerAction('Page', 'setTitle', ['T']);
		$state = PradoUnit::invoke($cache, 'saveStateRecursive', true);
		$this->assertSame('child', $this->renderCache($cache));
		$this->assertSame(1, $child->depths['render']);
		$this->assertSame(['child', $state, [['Page', 'setTitle', ['T']]], 500], $this->_cache->values[$this->key()]);
		$this->assertSame(30, $this->_cache->expires[$this->key()]);
	}

	public function testAHitRendersTheCachedOutputAndReplaysTheActions()
	{
		$this->_cache->values[$this->key()] = ['cached', ['s' => 1], [
			['Page.ClientScript', 'registerEndScript', ['k', 'code']],
			['Page', 'setTitle', ['Title']],
			['Parent', 'record', ['a', 'b']],
		], 400];
		$times = [];
		[$cache, $page, , $cs] = $this->createCache(function ($cache) use (&$times) {
			$cache->attachEventHandler('OnCheckDependency', function ($sender, $param) use (&$times) {
				$times[] = $param->getCacheTime();
			});
		});
		$cs->expects($this->once())->method('registerEndScript')->with('k', 'code');
		$this->assertFalse($cache->getAllowChildControls());
		$this->assertTrue($cache->getContentCached());
		$this->assertSame(400, $cache->getCacheTime());
		$this->assertSame([400], $times);
		$recorder = new TOutputCacheTestChild();
		$page->getControls()->remove($cache);
		$page->getControls()->add($recorder);
		$recorder->getControls()->add($cache);
		PradoUnit::invoke($cache, 'loadRecursive');
		$this->assertSame(['Title'], $page->titles);
		$this->assertSame([['a', 'b']], $recorder->calls);
		$this->assertSame(['s' => 1], PradoUnit::invoke($cache, 'saveStateRecursive', true));
		$this->assertSame('cached', $this->renderCache($cache));
	}

	public function testAnEntryWithoutACacheTimeIsAHit()
	{
		$this->_cache->values[$this->key()] = ['cached', null, []];
		[$cache] = $this->createCache();
		$this->assertTrue($cache->getContentCached());
		$this->assertSame(0, $cache->getCacheTime());
		$this->assertSame('cached', $this->renderCache($cache));
	}

	public function testAnInvalidDependencyIsAMiss()
	{
		$this->_cache->values[$this->key()] = ['cached', null, [], 400];
		[$cache] = $this->createCache(fn ($cache) => $cache->attachEventHandler('OnCheckDependency', fn ($sender, $param) => $param->setIsValid(false)));
		$this->assertFalse($cache->getContentCached());
		$this->assertSame('child', $this->renderCache($cache));
	}

	public static function notCacheableProvider(): array
	{
		return [
			'no duration' => [0, false, false, true],
			'postback' => [60, true, false, true],
			'no cache' => [60, false, false, false],
		];
	}

	/**
	 * @dataProvider notCacheableProvider
	 */
	public function testNotCacheableRendersTheChildren(int $duration, bool $postBack, bool $cachingPostBack, bool $hasCache)
	{
		if (!$hasCache) {
			PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		}
		$this->_cache->values[$this->key()] = ['cached', null, [], 1];
		[$cache, $page, $child] = $this->createCache(function ($cache, $page) use ($duration, $postBack, $cachingPostBack) {
			$cache->setDuration($duration);
			$page->postBack = $postBack;
			$cache->setCachingPostBack($cachingPostBack);
		});
		$this->assertTrue($cache->getAllowChildControls());
		$this->assertSame('child', $this->renderCache($cache));
		$this->assertSame(0, $child->depths['render']);
		PradoUnit::invoke($cache, 'initRecursive', null);
		PradoUnit::invoke($cache, 'loadRecursive');
		PradoUnit::invoke($cache, 'preRenderRecursive');
		$this->assertEquals(['init' => 0, 'load' => 0, 'prerender' => 0, 'render' => 0], $child->depths);
	}

	public function testCachingPostBackReadsTheCache()
	{
		$this->_cache->values[$this->key()] = ['cached', null, [], 1];
		[$cache] = $this->createCache(function ($cache, $page) {
			$page->postBack = true;
			$cache->setCachingPostBack(true);
		});
		$this->assertTrue($cache->getContentCached());
	}

	public function testAMissRunsTheChildLifecycleOnTheCachingStack()
	{
		[$cache, , $child] = $this->createCache();
		$this->assertTrue($cache->getAllowChildControls());
		PradoUnit::invoke($cache, 'initRecursive', null);
		PradoUnit::invoke($cache, 'loadRecursive');
		PradoUnit::invoke($cache, 'preRenderRecursive');
		$this->assertSame(['init' => 1, 'load' => 1, 'prerender' => 1], $child->depths);
		$this->loadState($cache);
	}

	public function testCacheModuleID()
	{
		$module = new TOutputCacheTestModule();
		Prado::getApplication()->setModule('ocache', $module);
		$module->values[$this->key()] = ['module', null, [], 1];
		[$cache] = $this->createCache(fn ($cache) => $cache->setCacheModuleID('ocache'));
		$this->assertTrue($cache->getContentCached());
		$this->assertSame('module', $this->renderCache($cache));
	}

	public function testAnInvalidCacheModuleIDThrows()
	{
		try {
			$this->createCache(fn ($cache) => $cache->setCacheModuleID('missing'));
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('outputcache_cachemoduleid_invalid', $e->getErrorCode());
		}
	}

	// --- Cache key ---

	public function testCacheKeyVariesByPrefixSessionParamsAndHandler()
	{
		$session = $this->getMockBuilder(THttpSession::class)->onlyMethods(['getSessionID'])->getMock();
		$session->method('getSessionID')->willReturn('S1');
		PradoUnit::setProp(Prado::getApplication(), '_session', $session);
		$request = Prado::getApplication()->getRequest();
		$saved = $request->itemAt('page');
		try {
			$request->add('page', '2');
			[$cache] = $this->createCache(function ($cache) {
				$cache->setCacheKeyPrefix('p:');
				$cache->setVaryBySession(true);
				$cache->setVaryByParam('page, missing');
				$cache->attachEventHandler('OnCalculateKey', fn ($sender, $param) => $param->setCacheKey('+h'));
			});
			$this->assertSame(TOutputCache::CACHE_ID_PREFIX . 'p:HomeocS1' . serialize(['page' => '2', 'missing' => null]) . '+h', $cache->getCacheKey());
			$request->add('page', '3');
			$this->assertStringContainsString('"2"', $cache->getCacheKey());
		} finally {
			$saved === null ? $request->remove('page') : $request->add('page', $saved);
		}
	}

	// --- Paths ---

	public function testAHitRunsTheChildLifecycleOffTheCachingStack()
	{
		$this->_cache->values[$this->key()] = ['cached', null, [], 1];
		[$cache, $page] = $this->createCache();
		$this->assertTrue($cache->getContentCached());
		PradoUnit::invoke($cache, 'initRecursive', null);
		PradoUnit::invoke($cache, 'loadRecursive');
		PradoUnit::invoke($cache, 'preRenderRecursive');
		$this->assertSame(0, $page->getCachingStack()->getCount());
	}

	public static function oneActionProvider(): array
	{
		return [
			'client script' => ['Page.ClientScript'],
			'page' => ['Page'],
			'sub-property' => ['Parent'],
			'none' => [null],
		];
	}

	/**
	 * @dataProvider oneActionProvider
	 */
	public function testAHitReplaysOneAction(?string $context)
	{
		$actions = match ($context) {
			'Page.ClientScript' => [['Page.ClientScript', 'registerEndScript', ['k', 'code']]],
			'Page' => [['Page', 'setTitle', ['Title']]],
			'Parent' => [['Parent', 'record', ['a']]],
			null => [],
		};
		$this->_cache->values[$this->key()] = ['cached', null, $actions, 1];
		$recorder = new TOutputCacheTestChild();
		[$cache, $page, , $cs] = $this->createCache(function ($cache, $page) use ($recorder) {
			$page->getControls()->remove($cache);
			$page->getControls()->add($recorder);
			$recorder->getControls()->add($cache);
		});
		$cs->expects($context === 'Page.ClientScript' ? $this->once() : $this->never())->method('registerEndScript');
		PradoUnit::invoke($cache, 'loadRecursive');
		$this->assertSame($context === 'Page' ? ['Title'] : [], $page->titles);
		$this->assertSame($context === 'Parent' ? [['a']] : [], $recorder->calls);
	}

	public function testCacheKeyVariesBySessionOnly()
	{
		$session = $this->getMockBuilder(THttpSession::class)->onlyMethods(['getSessionID'])->getMock();
		$session->method('getSessionID')->willReturn('S1');
		PradoUnit::setProp(Prado::getApplication(), '_session', $session);
		[$cache] = $this->createCache(fn ($cache) => $cache->setVaryBySession(true));
		$this->assertSame($this->key() . 'S1', $cache->getCacheKey());
	}

	public function testCacheKeyVariesByOneParam()
	{
		[$cache] = $this->createCache(fn ($cache) => $cache->setVaryByParam('missing'));
		$this->assertSame($this->key() . serialize(['missing' => null]), $cache->getCacheKey());
	}

	public function testTextWriterMultiWithOneOrNoWriters()
	{
		$one = new TTextWriter();
		$multi = new TOutputCacheTextWriterMulti([$one]);
		$multi->write('x');
		$this->assertSame('x', $multi->flush());
		$none = new TOutputCacheTextWriterMulti([]);
		$none->write('x');
		$this->assertSame('', $none->flush());
	}
}
