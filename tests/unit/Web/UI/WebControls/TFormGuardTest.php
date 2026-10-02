<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Clock\TMockClock;
use Prado\IO\TTextWriter;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TFormGuard;
use Prado\Web\UI\WebControls\TFormGuardFailure;

class TFormGuardTest extends \PHPUnit\Framework\TestCase
{
	private const START = 1_000_000;

	private mixed $_appCache = null;

	private TMockClock $_clock;

	protected function setUp(): void
	{
		$this->_appCache = PradoUnit::getProp(Prado::getApplication(), '_cache');
		$this->_clock = new TMockClock();
		$this->_clock->setTime(self::START);
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_cache', $this->_appCache);
	}

	private function installCache(): TTestArrayCache
	{
		$cache = new TTestArrayCache();
		PradoUnit::setProp(Prado::getApplication(), '_cache', $cache);
		return $cache;
	}

	private function makeGuard(): TFormGuard
	{
		$page = new TPage();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$guard = new TFormGuard();
		$guard->setID('Guard');
		$guard->setClock($this->_clock);
		$form->getControls()->add($guard);
		return $guard;
	}

	/** Returns a stamp signed for $guard at $time. */
	private function stamp(TFormGuard $guard, int $time, ?string $uniqueID = null): string
	{
		return Prado::getApplication()->getSecurityManager()->hashData($time . ':' . ($uniqueID ?? $guard->getUniqueID()));
	}

	/** Posts the honeypot and stamp fields, then evaluates the guard once. */
	private function submit(TFormGuard $guard, string $honeypot, ?string $stamp): bool
	{
		$key = $guard->getUniqueID();
		$values = [$key . '$' . $guard->getHoneypotName() => $honeypot];
		if ($stamp !== null) {
			$values[$key . TFormGuard::STAMP_SUFFIX] = $stamp;
		}
		$guard->loadPostData($key, $values);
		return PradoUnit::invoke($guard, 'evaluateIsValid');
	}

	// -----------------------------------------------------------------------
	// Properties
	// -----------------------------------------------------------------------

	public function testIsAServerOnlyValidatorWithoutTarget(): void
	{
		$guard = new TFormGuard();
		self::assertInstanceOf(TBaseValidator::class, $guard);
		self::assertFalse($guard->getEnableClientScript());
		self::assertNull($guard->getValidationTarget());
	}

	public function testDefaultsAndSetters(): void
	{
		$guard = new TFormGuard();
		self::assertSame('website', $guard->getHoneypotName());
		self::assertSame(3, $guard->getMinFillTime());
		self::assertSame(0, $guard->getMaxFillTime());
		self::assertSame(0, $guard->getRateLimit());
		self::assertSame(60, $guard->getRateWindow());
		self::assertSame('', $guard->getCacheModuleID());
		self::assertNull($guard->getFailureReason());

		$guard->setHoneypotName('homepage_url');
		$guard->setMinFillTime('5');
		$guard->setMaxFillTime(3600);
		$guard->setRateLimit(-2);
		$guard->setRateWindow(0);
		self::assertSame('homepage_url', $guard->getHoneypotName());
		self::assertSame(5, $guard->getMinFillTime());
		self::assertSame(3600, $guard->getMaxFillTime());
		self::assertSame(0, $guard->getRateLimit(), 'A negative limit disables the check.');
		self::assertSame(1, $guard->getRateWindow(), 'The window is at least one second.');
	}

	public function testHoneypotNameRejectsUnsafeNames(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TFormGuard())->setHoneypotName('a"b');
	}

	// -----------------------------------------------------------------------
	// Rendering
	// -----------------------------------------------------------------------

	public function testRendersHiddenHoneypotAndSignedStamp(): void
	{
		$guard = $this->makeGuard();
		$tw = new TTextWriter();
		PradoUnit::invoke($guard, 'renderGuardFields', new THtmlWriter($tw));
		$html = $tw->flush();

		self::assertMatchesRegularExpression('/<span aria-hidden="true" style="[^"]*left:-10000px[^"]*">/', $html);
		self::assertStringContainsString('<label for="' . $guard->getClientID() . '_hp">Leave this field empty</label>', $html);
		self::assertStringContainsString('name="' . $guard->getUniqueID() . '$website"', $html);
		self::assertStringContainsString('tabindex="-1"', $html);
		self::assertStringContainsString('autocomplete="off"', $html);
		self::assertMatchesRegularExpression('/<input type="hidden" name="' . preg_quote($guard->getUniqueID() . '$ts', '/') . '" value="([^"]+)"/', $html);

		preg_match('/name="' . preg_quote($guard->getUniqueID() . '$ts', '/') . '" value="([^"]+)"/', $html, $matches);
		$data = Prado::getApplication()->getSecurityManager()->validateData(html_entity_decode($matches[1]));
		self::assertSame(self::START . ':' . $guard->getUniqueID(), $data);
	}

	public function testRerenderKeepsTheFirstRenderTime(): void
	{
		$guard = $this->makeGuard();
		$this->_clock->setTime(self::START + 1);
		self::assertFalse($this->submit($guard, '', $this->stamp($guard, self::START)));

		$this->_clock->setTime(self::START + 50);
		$stamp = PradoUnit::invoke($guard, 'createStamp');
		self::assertSame(self::START . ':' . $guard->getUniqueID(), Prado::getApplication()->getSecurityManager()->validateData($stamp));
	}

	// -----------------------------------------------------------------------
	// Checks
	// -----------------------------------------------------------------------

	public function testPassesAfterMinFillTime(): void
	{
		$guard = $this->makeGuard();
		$this->_clock->setTime(self::START + 3);
		self::assertTrue($this->submit($guard, '', $this->stamp($guard, self::START)));
		self::assertNull($guard->getFailureReason());
	}

	public function testFilledHoneypotFails(): void
	{
		$guard = $this->makeGuard();
		$this->_clock->setTime(self::START + 10);
		self::assertFalse($this->submit($guard, 'https://spam.example', $this->stamp($guard, self::START)));
		self::assertSame(TFormGuardFailure::Honeypot, $guard->getFailureReason());
	}

	public function testMissingStampFails(): void
	{
		$guard = $this->makeGuard();
		self::assertFalse($this->submit($guard, '', null));
		self::assertSame(TFormGuardFailure::Stamp, $guard->getFailureReason());
	}

	public function testAlteredStampFails(): void
	{
		$guard = $this->makeGuard();
		$stamp = $this->stamp($guard, self::START);
		self::assertFalse($this->submit($guard, '', substr($stamp, 0, -1) . 'X'));
		self::assertSame(TFormGuardFailure::Stamp, $guard->getFailureReason());
	}

	public function testStampOfAnotherGuardFails(): void
	{
		$guard = $this->makeGuard();
		$this->_clock->setTime(self::START + 10);
		self::assertFalse($this->submit($guard, '', $this->stamp($guard, self::START, 'OtherGuard')));
		self::assertSame(TFormGuardFailure::Stamp, $guard->getFailureReason());
	}

	public function testFastSubmissionFails(): void
	{
		$guard = $this->makeGuard();
		$this->_clock->setTime(self::START + 2);
		self::assertFalse($this->submit($guard, '', $this->stamp($guard, self::START)));
		self::assertSame(TFormGuardFailure::TooFast, $guard->getFailureReason());
	}

	public function testZeroMinFillTimeDisablesTheCheck(): void
	{
		$guard = $this->makeGuard();
		$guard->setMinFillTime(0);
		self::assertTrue($this->submit($guard, '', $this->stamp($guard, self::START)));
	}

	public function testLateSubmissionFails(): void
	{
		$guard = $this->makeGuard();
		$guard->setMaxFillTime(600);
		$this->_clock->setTime(self::START + 601);
		self::assertFalse($this->submit($guard, '', $this->stamp($guard, self::START)));
		self::assertSame(TFormGuardFailure::Expired, $guard->getFailureReason());
	}

	public function testEvaluatesOncePerRequest(): void
	{
		$cache = $this->installCache();
		$guard = $this->makeGuard();
		$guard->setRateLimit(5);
		$this->_clock->setTime(self::START + 10);
		self::assertTrue($this->submit($guard, '', $this->stamp($guard, self::START)));
		self::assertTrue(PradoUnit::invoke($guard, 'evaluateIsValid'));
		self::assertSame([1], array_values($cache->values), 'A request counts once.');
	}

	// -----------------------------------------------------------------------
	// Rate limit
	// -----------------------------------------------------------------------

	public function testRateLimitFailsOverTheLimitInTheWindow(): void
	{
		$cache = $this->installCache();
		$this->_clock->setTime(self::START + 10);
		$results = [];
		foreach ([1, 2, 3] as $request) {
			$guard = $this->makeGuard();
			$guard->setRateLimit(2);
			$guard->setRateWindow(60);
			$results[] = $this->submit($guard, '', $this->stamp($guard, self::START));
		}
		self::assertSame([true, true, false], $results);
		self::assertSame(TFormGuardFailure::RateLimited, $guard->getFailureReason());
		$key = array_key_first($cache->values);
		self::assertStringStartsWith(TFormGuard::CACHE_KEY_PREFIX, $key);
		self::assertSame(60 - (self::START + 10) % 60, $cache->expires[$key], 'The count lasts until the window ends.');
	}

	public function testRateLimitCountsFailedSubmissions(): void
	{
		$cache = $this->installCache();
		$this->_clock->setTime(self::START + 10);
		$guard = $this->makeGuard();
		$guard->setRateLimit(2);
		self::assertFalse($this->submit($guard, 'bot', $this->stamp($guard, self::START)));
		self::assertSame([1], array_values($cache->values));
	}

	public function testRateLimitStartsOverInTheNextWindow(): void
	{
		$this->installCache();
		$this->_clock->setTime(self::START + 10);
		foreach ([1, 2] as $request) {
			$guard = $this->makeGuard();
			$guard->setRateLimit(1);
			$this->submit($guard, '', $this->stamp($guard, self::START));
		}
		self::assertSame(TFormGuardFailure::RateLimited, $guard->getFailureReason());

		$this->_clock->setTime(self::START + 70);
		$guard = $this->makeGuard();
		$guard->setRateLimit(1);
		self::assertTrue($this->submit($guard, '', $this->stamp($guard, self::START)));
	}

	public function testRateLimitWithoutCacheThrows(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		$guard = $this->makeGuard();
		$guard->setRateLimit(1);
		$this->expectException(TConfigurationException::class);
		$this->submit($guard, '', $this->stamp($guard, self::START));
	}

	public function testInvalidCacheModuleIDThrows(): void
	{
		$guard = $this->makeGuard();
		$guard->setRateLimit(1);
		$guard->setCacheModuleID('no-such-module');
		$this->expectException(TConfigurationException::class);
		$this->submit($guard, '', $this->stamp($guard, self::START));
	}
}
