<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Clock\TMockClock;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IValidatable;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TProofOfWork;
use Prado\Web\UI\WebControls\TProofOfWorkStartMode;
use Prado\Web\UI\WebControls\TProofOfWorkValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TProofOfWorkTest extends \PHPUnit\Framework\TestCase
{
	use TWebControlRenderTrait;

	private const START = 1_000_000;

	private mixed $_appCache = null;

	private TMockClock $_clock;

	private TTestArrayCache $_cache;

	protected function setUp(): void
	{
		$this->_appCache = PradoUnit::getProp(Prado::getApplication(), '_cache');
		$this->_cache = new TTestArrayCache();
		PradoUnit::setProp(Prado::getApplication(), '_cache', $this->_cache);
		$this->_clock = new TMockClock();
		$this->_clock->setTime(self::START);
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_cache', $this->_appCache);
	}

	private function makeWork(string $id = 'Work'): TProofOfWork
	{
		$page = new TPage();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$work = new TProofOfWork();
		$work->setID($id);
		$work->setClock($this->_clock);
		$work->setComplexity(TProofOfWork::MIN_COMPLEXITY);
		$form->getControls()->add($work);
		return $work;
	}

	/** Solves a challenge the way the client does. */
	private function solve(array $challenge): int
	{
		for ($n = 0; $n <= $challenge['maxnumber']; $n++) {
			if (hash('sha256', $challenge['salt'] . $n) === $challenge['challenge']) {
				return $n;
			}
		}
		self::fail('The challenge has no solution.');
	}

	/** Returns the JSON solution the client posts. */
	private function solution(array $challenge, ?int $number = null): string
	{
		return json_encode([
			'challenge' => $challenge['challenge'],
			'salt' => $challenge['salt'],
			'signature' => $challenge['signature'],
			'number' => $number ?? $this->solve($challenge),
		]);
	}

	/** Posts a solution to a fresh control in a new request and validates it. */
	private function post(string $solution, string $id = 'Work'): bool
	{
		$work = $this->makeWork($id);
		$work->loadPostData($work->getUniqueID(), [$work->getUniqueID() => $solution]);
		return $work->validate();
	}

	private function challenge(?TProofOfWork $work = null): array
	{
		return PradoUnit::invoke($work ?? $this->makeWork(), 'createChallenge');
	}

	// -----------------------------------------------------------------------
	// Properties
	// -----------------------------------------------------------------------

	public function testImplementsPostDataAndValidatable(): void
	{
		$work = new TProofOfWork();
		self::assertInstanceOf(IPostBackDataHandler::class, $work);
		self::assertInstanceOf(IValidatable::class, $work);
		self::assertTrue($work->getIsValid());
		self::assertFalse($work->getDataChanged());
	}

	public function testDefaultsAndSetters(): void
	{
		$work = new TProofOfWork();
		self::assertSame(500000, $work->getComplexity());
		self::assertSame(1800, $work->getChallengeExpiry());
		self::assertSame(TProofOfWorkStartMode::Focus, $work->getStartMode());
		self::assertSame('Verifying…', $work->getVerifyingText());
		self::assertSame('Verified', $work->getVerifiedText());
		self::assertNotSame('', $work->getFailedText());
		self::assertNotSame('', $work->getNoScriptText());

		$work->setComplexity('20000');
		$work->setChallengeExpiry(10);
		$work->setStartMode('Load');
		$work->setVerifyingText('Checking');
		self::assertSame(20000, $work->getComplexity());
		self::assertSame(60, $work->getChallengeExpiry(), 'The expiry is at least 60 seconds.');
		self::assertSame(TProofOfWorkStartMode::Load, $work->getStartMode());
		self::assertSame('Checking', $work->getVerifyingText());
	}

	public static function provideInvalidComplexity(): array
	{
		return [
			'below' => [TProofOfWork::MIN_COMPLEXITY - 1],
			'above' => [TProofOfWork::MAX_COMPLEXITY + 1],
		];
	}

	/**
	 * @dataProvider provideInvalidComplexity
	 * @param int $value
	 */
	public function testComplexityOutOfRangeThrows(int $value): void
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TProofOfWork())->setComplexity($value);
	}

	public function testStartModeRejectsUnknownValue(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		(new TProofOfWork())->setStartMode('Never');
	}

	// -----------------------------------------------------------------------
	// Challenge
	// -----------------------------------------------------------------------

	public function testChallengeIsSignedAndSolvable(): void
	{
		$work = $this->makeWork();
		$challenge = $this->challenge($work);

		self::assertSame(TProofOfWork::ALGORITHM, $challenge['algorithm']);
		self::assertSame(TProofOfWork::MIN_COMPLEXITY, $challenge['maxnumber']);
		self::assertMatchesRegularExpression('/^[0-9a-f]{24}\?expires=' . (self::START + 1800) . '$/', $challenge['salt']);
		self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $challenge['challenge']);
		$key = Prado::getApplication()->getSecurityManager()->getValidationKey();
		self::assertSame(hash_hmac('sha256', $challenge['challenge'] . '|' . $work->getUniqueID(), $key), $challenge['signature']);
		self::assertLessThanOrEqual(TProofOfWork::MIN_COMPLEXITY, $this->solve($challenge));
	}

	public function testEachChallengeIsNew(): void
	{
		$work = $this->makeWork();
		self::assertNotSame($this->challenge($work)['salt'], $this->challenge($work)['salt']);
	}

	// -----------------------------------------------------------------------
	// Validation
	// -----------------------------------------------------------------------

	public function testValidSolutionPassesOnce(): void
	{
		$challenge = $this->challenge();
		$solution = $this->solution($challenge);

		self::assertTrue($this->post($solution));
		self::assertSame(1800, $this->_cache->expires[TProofOfWork::CACHE_KEY_PREFIX . $challenge['challenge']], 'The claim lasts until the challenge expires.');
		self::assertFalse($this->post($solution), 'A replayed solution fails.');
	}

	public function testValidateReturnsTheFirstResultInARequest(): void
	{
		$work = $this->makeWork();
		$challenge = $this->challenge($work);
		$work->loadPostData($work->getUniqueID(), [$work->getUniqueID() => $this->solution($challenge)]);
		self::assertTrue($work->validate());
		self::assertTrue($work->validate(), 'The claim from the first call stands.');
	}

	public function testWrongNumberFails(): void
	{
		$challenge = $this->challenge();
		$number = $this->solve($challenge);
		self::assertFalse($this->post($this->solution($challenge, $number + 1)));
		self::assertSame([], $this->_cache->values, 'A failed solution claims nothing.');
	}

	public function testAlteredSignatureFails(): void
	{
		$challenge = $this->challenge();
		$challenge['signature'] = str_repeat('0', 64);
		self::assertFalse($this->post($this->solution($challenge)));
	}

	public function testAlteredExpiryFails(): void
	{
		$challenge = $this->challenge();
		$number = $this->solve($challenge);
		$challenge['salt'] = preg_replace('/expires=\d+/', 'expires=' . (self::START + 999999), $challenge['salt']);
		self::assertFalse($this->post($this->solution($challenge, $number)), 'The salt is bound to the challenge hash.');
	}

	public function testExpiredChallengeFails(): void
	{
		$challenge = $this->challenge();
		$solution = $this->solution($challenge);
		$this->_clock->setTime(self::START + 1801);
		self::assertFalse($this->post($solution));
	}

	public function testSolutionForAnotherControlFails(): void
	{
		$challenge = $this->challenge($this->makeWork('Work'));
		self::assertFalse($this->post($this->solution($challenge), 'OtherWork'));
	}

	public static function provideMalformedSolutions(): array
	{
		return [
			'empty' => [''],
			'not json' => ['{'],
			'not an object' => ['42'],
			'missing number' => ['{"challenge":"a","salt":"b","signature":"c"}'],
			'string number' => ['{"challenge":"a","salt":"b","signature":"c","number":"1"}'],
			'negative number' => ['{"challenge":"a","salt":"b","signature":"c","number":-1}'],
			'array challenge' => ['{"challenge":[],"salt":"b","signature":"c","number":1}'],
		];
	}

	/**
	 * @dataProvider provideMalformedSolutions
	 * @param string $solution
	 */
	public function testMalformedSolutionFails(string $solution): void
	{
		self::assertFalse($this->post($solution));
	}

	public function testValidationWithoutCacheThrows(): void
	{
		$challenge = $this->challenge();
		$solution = $this->solution($challenge);
		PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		$this->expectException(TConfigurationException::class);
		$this->post($solution);
	}

	public function testPreRenderWithoutCacheThrows(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		$work = $this->makeWork();
		$this->expectException(TConfigurationException::class);
		$work->onPreRender(null);
	}

	// -----------------------------------------------------------------------
	// Rendering
	// -----------------------------------------------------------------------

	public function testRendersLiveRegionWithSolutionField(): void
	{
		$work = $this->makeWork();
		$work->setNoScriptText('Enable <JavaScript>');
		$html = $this->render($work);

		self::assertStringStartsWith('<div id="' . $work->getClientID() . '" role="status" aria-busy="false">', $html);
		self::assertStringContainsString('<span class="pow-status">&nbsp;</span>', $html);
		self::assertStringContainsString('<input type="hidden" id="' . $work->getClientID() . '_solution" name="' . $work->getUniqueID() . '" value="" />', $html);
		self::assertStringContainsString('<noscript>Enable &lt;JavaScript&gt;</noscript>', $html);
	}

	// -----------------------------------------------------------------------
	// TProofOfWorkValidator
	// -----------------------------------------------------------------------

	private function makeValidator(TProofOfWork $work, string $target = 'Work'): TProofOfWorkValidator
	{
		$validator = new TProofOfWorkValidator();
		$validator->setControlToValidate($target);
		$work->getParent()->getControls()->add($validator);
		return $validator;
	}

	public function testValidatorIsServerOnly(): void
	{
		self::assertFalse((new TProofOfWorkValidator())->getEnableClientScript());
	}

	public function testValidatorPassesAValidSolution(): void
	{
		$work = $this->makeWork();
		$work->loadPostData($work->getUniqueID(), [$work->getUniqueID() => $this->solution($this->challenge($work))]);
		self::assertTrue($this->makeValidator($work)->validate());
	}

	public function testValidatorFailsAndMarksTheControl(): void
	{
		$work = $this->makeWork();
		$work->loadPostData($work->getUniqueID(), [$work->getUniqueID() => '']);
		self::assertFalse($this->makeValidator($work)->validate());
		self::assertFalse($work->getIsValid());
	}

	public function testValidatorRejectsOtherControls(): void
	{
		$work = $this->makeWork();
		$box = new TTextBox();
		$box->setID('Box');
		$work->getParent()->getControls()->add($box);
		$this->expectException(TConfigurationException::class);
		$this->makeValidator($work, 'Box')->validate();
	}
}
