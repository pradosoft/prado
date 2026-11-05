<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Clock\TMockClock;
use Prado\Web\UI\WebControls\TCaptcha;

/**
 * TCaptcha subclass exposing protected seams and isolating the private-key file from the
 * asset manager, so token generation and the expiry logic can be exercised without a request,
 * asset publishing, or the GD extension.
 */
class TestCaptcha extends TCaptcha
{
	/** When set, {@see getToken()} returns this instead of computing a real token. */
	public ?string $fixedToken = null;

	/** Content written to the stand-in private-key file. */
	public string $keyFileContent = "<?php\n\$privateKey='testprivatekey';\n?>";

	private ?string $_keyFile = null;

	public function pubSetTokenGenerated(int $timestamp): void
	{
		$this->setViewState('TokenGenerated', $timestamp);
	}

	public function pubSetViewState(string $key, mixed $value): void
	{
		$this->setViewState($key, $value);
	}

	public function pubGetViewState(string $key, mixed $default = null): mixed
	{
		return $this->getViewState($key, $default);
	}

	public function pubGetTokenLength(): int
	{
		return $this->getTokenLength();
	}

	public function pubGenerateToken(string $publicKey, string $privateKey, string $alphabet, int $tokenLength, bool $caseSensitive): string
	{
		return $this->generateToken($publicKey, $privateKey, $alphabet, $tokenLength, $caseSensitive);
	}

	public function pubHash2string(string $hex, string $alphabet = ''): string
	{
		return $this->hash2string($hex, $alphabet);
	}

	public function pubGetTokenImageOptions(): string
	{
		return $this->getTokenImageOptions();
	}

	public function pubGetCaptchaScriptFile(): string
	{
		return $this->getCaptchaScriptFile();
	}

	public function pubGetFontFile(): string
	{
		return $this->getFontFile();
	}

	public function getToken()
	{
		return $this->fixedToken ?? parent::getToken();
	}

	protected function generatePrivateKeyFile()
	{
		if ($this->_keyFile === null) {
			$this->_keyFile = tempnam(sys_get_temp_dir(), 'pradocaptchakey');
			file_put_contents($this->_keyFile, $this->keyFileContent);
		}
		return $this->_keyFile;
	}

	public function cleanupKeyFile(): void
	{
		if ($this->_keyFile !== null && is_file($this->_keyFile)) {
			@unlink($this->_keyFile);
			$this->_keyFile = null;
		}
	}
}

class TCaptchaTest extends \PHPUnit\Framework\TestCase
{
	/** @var TestCaptcha[] captchas whose temp key files must be removed */
	private array $_toClean = [];

	/** The application's cache before the test. */
	private mixed $_appCache = null;

	protected function tearDown(): void
	{
		foreach ($this->_toClean as $captcha) {
			$captcha->cleanupKeyFile();
		}
		$this->_toClean = [];
		PradoUnit::setProp(Prado::getApplication(), '_cache', $this->_appCache);
	}

	protected function setUp(): void
	{
		$this->_appCache = PradoUnit::getProp(Prado::getApplication(), '_cache');
	}

	/** Installs an in-memory primary cache for the test. */
	private function installCache(): TTestArrayCache
	{
		$cache = new TTestArrayCache();
		PradoUnit::setProp(Prado::getApplication(), '_cache', $cache);
		return $cache;
	}

	private function newCaptcha(): TestCaptcha
	{
		$captcha = new TestCaptcha();
		$this->_toClean[] = $captcha;
		return $captcha;
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testConstants(): void
	{
		self::assertSame(2, TCaptcha::MIN_TOKEN_LENGTH);
		self::assertSame(40, TCaptcha::MAX_TOKEN_LENGTH);
	}

	public function testIsAWebControl(): void
	{
		self::assertInstanceOf(\Prado\Web\UI\WebControls\TImage::class, $this->newCaptcha());
	}

	// -----------------------------------------------------------------------
	// TokenImageTheme
	// -----------------------------------------------------------------------

	public function testTokenImageThemeDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame(0, $captcha->getTokenImageTheme());
		$captcha->setTokenImageTheme(63);
		self::assertSame(63, $captcha->getTokenImageTheme());
		$captcha->setTokenImageTheme('7');
		self::assertSame(7, $captcha->getTokenImageTheme());
	}

	public function testTokenImageThemeBelowRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setTokenImageTheme(-1);
	}

	public function testTokenImageThemeAboveRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setTokenImageTheme(64);
	}

	// -----------------------------------------------------------------------
	// TokenFontSize
	// -----------------------------------------------------------------------

	public function testTokenFontSizeDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame(30, $captcha->getTokenFontSize());
		$captcha->setTokenFontSize(20);
		self::assertSame(20, $captcha->getTokenFontSize());
		$captcha->setTokenFontSize(100);
		self::assertSame(100, $captcha->getTokenFontSize());
	}

	public function testTokenFontSizeBelowRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setTokenFontSize(19);
	}

	public function testTokenFontSizeAboveRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setTokenFontSize(101);
	}

	// -----------------------------------------------------------------------
	// Min / Max token length
	// -----------------------------------------------------------------------

	public function testMinTokenLengthDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame(4, $captcha->getMinTokenLength());
		$captcha->setMinTokenLength(2);
		self::assertSame(2, $captcha->getMinTokenLength());
		$captcha->setMinTokenLength(40);
		self::assertSame(40, $captcha->getMinTokenLength());
	}

	public function testMinTokenLengthBelowRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setMinTokenLength(1);
	}

	public function testMinTokenLengthAboveRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setMinTokenLength(41);
	}

	public function testMaxTokenLengthDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame(6, $captcha->getMaxTokenLength());
		$captcha->setMaxTokenLength(2);
		self::assertSame(2, $captcha->getMaxTokenLength());
		$captcha->setMaxTokenLength(40);
		self::assertSame(40, $captcha->getMaxTokenLength());
	}

	public function testMaxTokenLengthBelowRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setMaxTokenLength(1);
	}

	public function testMaxTokenLengthAboveRangeThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setMaxTokenLength(41);
	}

	// -----------------------------------------------------------------------
	// CaseSensitive / TokenAlphabet / TokenExpiry / ChangingTokenBackground / TestLimit
	// -----------------------------------------------------------------------

	public function testCaseSensitiveDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertTrue($captcha->getCaseSensitive());
		$captcha->setCaseSensitive(false);
		self::assertFalse($captcha->getCaseSensitive());
	}

	public function testTokenAlphabetDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame('234578adefhijmnrtABDEFGHJLMNRT', $captcha->getTokenAlphabet());
		$captcha->setTokenAlphabet('ABCDEF');
		self::assertSame('ABCDEF', $captcha->getTokenAlphabet());
	}

	public function testTokenAlphabetTooShortThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->newCaptcha()->setTokenAlphabet('A');
	}

	public function testTokenExpiryDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame(600, $captcha->getTokenExpiry());
		$captcha->setTokenExpiry(120);
		self::assertSame(120, $captcha->getTokenExpiry());
	}

	public function testChangingTokenBackgroundDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertFalse($captcha->getChangingTokenBackground());
		$captcha->setChangingTokenBackground(true);
		self::assertTrue($captcha->getChangingTokenBackground());
	}

	public function testTestLimitDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame(5, $captcha->getTestLimit());
		$captcha->setTestLimit(0);
		self::assertSame(0, $captcha->getTestLimit());
	}

	// -----------------------------------------------------------------------
	// PublicKey
	// -----------------------------------------------------------------------

	public function testPublicKeyGeneratedIsHexAndPersists(): void
	{
		$captcha = $this->newCaptcha();
		$key = $captcha->getPublicKey();
		self::assertSame(32, strlen($key));
		self::assertTrue(ctype_xdigit($key));
		// Reading again returns the same generated key.
		self::assertSame($key, $captcha->getPublicKey());
	}

	public function testPublicKeyDiffersBetweenInstances(): void
	{
		self::assertNotSame($this->newCaptcha()->getPublicKey(), $this->newCaptcha()->getPublicKey());
	}

	public function testPublicKeyExplicitSet(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		self::assertSame('fixedpublic', $captcha->getPublicKey());
	}

	// -----------------------------------------------------------------------
	// getIsTokenExpired (with injected clock)
	// -----------------------------------------------------------------------

	public function testIsTokenExpiredComparedAgainstInjectedClock(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(300);
		$captcha->pubSetTokenGenerated(1_000_000);

		$clock = new TMockClock();
		$captcha->setClock($clock);

		$clock->setTime(1_000_000 + 299);
		self::assertFalse($captcha->getIsTokenExpired(), 'The token is valid before its expiry relative to the clock.');

		$clock->setTime(1_000_000 + 300);
		self::assertFalse($captcha->getIsTokenExpired(), 'The token is valid exactly at expiry + start.');

		$clock->setTime(1_000_000 + 301);
		self::assertTrue($captcha->getIsTokenExpired(), 'The token expires once the clock passes expiry + start.');
	}

	public function testIsTokenNotExpiredWhenNeverGenerated(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(300);
		$captcha->setClock(new TMockClock());

		self::assertFalse($captcha->getIsTokenExpired(), 'An ungenerated token never reports as expired.');
	}

	public function testIsTokenNotExpiredWhenExpiryDisabled(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->pubSetTokenGenerated(1);

		$clock = new TMockClock();
		$clock->setTime(9_999_999_999);
		$captcha->setClock($clock);

		self::assertFalse($captcha->getIsTokenExpired(), 'A non-positive expiry disables expiration.');
	}

	// -----------------------------------------------------------------------
	// getTokenLength
	// -----------------------------------------------------------------------

	public function testTokenLengthEqualsWhenMinEqualsMax(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setMinTokenLength(5);
		$captcha->setMaxTokenLength(5);
		self::assertSame(5, $captcha->pubGetTokenLength());
	}

	public function testTokenLengthWithinRangeWhenMinLessThanMax(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setMinTokenLength(4);
		$captcha->setMaxTokenLength(8);
		$length = $captcha->pubGetTokenLength();
		self::assertGreaterThanOrEqual(4, $length);
		self::assertLessThanOrEqual(8, $length);
	}

	public function testTokenLengthWithinRangeWhenMinGreaterThanMax(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setMinTokenLength(9);
		$captcha->setMaxTokenLength(5);
		$length = $captcha->pubGetTokenLength();
		self::assertGreaterThanOrEqual(5, $length);
		self::assertLessThanOrEqual(9, $length);
	}

	public function testTokenLengthIsCached(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setMinTokenLength(2);
		$captcha->setMaxTokenLength(40);
		$first = $captcha->pubGetTokenLength();
		self::assertSame($first, $captcha->pubGetTokenLength());
	}

	// -----------------------------------------------------------------------
	// generateToken / hash2string
	// -----------------------------------------------------------------------

	public function testGenerateTokenIsDeterministicAndCorrectLength(): void
	{
		$captcha = $this->newCaptcha();
		$alphabet = '234578adefhijmnrtABDEFGHJLMNRT';
		$a = $captcha->pubGenerateToken('pub', 'priv', $alphabet, 6, true);
		$b = $captcha->pubGenerateToken('pub', 'priv', $alphabet, 6, true);
		self::assertSame($a, $b, 'Token generation is deterministic for the same inputs.');
		self::assertSame(6, strlen($a));
		self::assertSame(1, preg_match('/^[' . preg_quote($alphabet, '/') . ']+$/', $a));
	}

	public function testGenerateTokenUppercasesWhenNotCaseSensitive(): void
	{
		$captcha = $this->newCaptcha();
		$alphabet = 'abcdefABCDEF';
		$token = $captcha->pubGenerateToken('pub', 'priv', $alphabet, 8, false);
		self::assertSame(strtoupper($token), $token);
	}

	public function testHash2stringUsesDefaultAlphabetWhenTooShort(): void
	{
		$captcha = $this->newCaptcha();
		$result = $captcha->pubHash2string(md5('anything'), 'A');
		self::assertNotSame('', $result);
		self::assertSame(1, preg_match('/^[234578adefhijmnrtABDEFGHJLMNQRT]+$/', $result));
	}

	public function testHash2stringUsesGivenAlphabet(): void
	{
		$captcha = $this->newCaptcha();
		$result = $captcha->pubHash2string(md5('anything'), 'XY');
		self::assertSame(1, preg_match('/^[XY]+$/', $result));
		// Deterministic for the same hex + alphabet.
		self::assertSame($result, $captcha->pubHash2string(md5('anything'), 'XY'));
	}

	// -----------------------------------------------------------------------
	// getPrivateKey / getToken / getTokenImageOptions (isolated key file)
	// -----------------------------------------------------------------------

	public function testGetPrivateKeyReadsKeyFromFile(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame('testprivatekey', $captcha->getPrivateKey());
	}

	public function testGetPrivateKeyThrowsWhenFileHasNoKey(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->keyFileContent = "<?php // no key here ?>";
		$this->expectException(TConfigurationException::class);
		$captcha->getPrivateKey();
	}

	public function testGetTokenIsDeterministicForFixedKeys(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		$captcha->setMinTokenLength(6);
		$captcha->setMaxTokenLength(6);

		$token = $captcha->getToken();
		self::assertSame(6, strlen($token));
		self::assertSame($token, $captcha->getToken(), 'The token is stable across reads for fixed keys.');
	}

	public function testGetTokenImageOptionsEncodesOptions(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		$captcha->setTokenImageTheme(5);
		$captcha->setTokenFontSize(40);

		$encoded = $captcha->pubGetTokenImageOptions();
		$decoded = base64_decode($encoded, true);
		self::assertNotFalse($decoded);
		// Payload is a 64-char HMAC-SHA256 signature followed by the serialized options.
		$str = substr($decoded, 64);
		self::assertSame(hash_hmac('sha256', $str, 'testprivatekey'), substr($decoded, 0, 64), 'The options are signed with the private key.');
		$options = unserialize($str);
		self::assertSame('fixedpublic', $options['publicKey']);
		self::assertSame(5, $options['theme']);
		self::assertSame(40, $options['fontSize']);
		self::assertTrue($options['caseSensitive']);
		// A random seed is generated and stored for reuse across postbacks.
		self::assertNotSame(0, $captcha->pubGetViewState('RandomSeed', 0));
		self::assertSame($captcha->pubGetViewState('RandomSeed', 0), $options['randomSeed']);
	}

	public function testGetTokenImageOptionsUsesZeroSeedWhenChangingBackground(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		$captcha->setChangingTokenBackground(true);

		$decoded = base64_decode($captcha->pubGetTokenImageOptions(), true);
		$options = unserialize(substr($decoded, 64));
		self::assertSame(0, $options['randomSeed'], 'A changing background sends seed 0 so the image varies.');
	}

	// -----------------------------------------------------------------------
	// validate
	// -----------------------------------------------------------------------

	public function testValidateAcceptsCorrectToken(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->fixedToken = 'aBcD';
		self::assertTrue($captcha->validate('aBcD'));
	}

	public function testValidateRejectsWrongToken(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->fixedToken = 'aBcD';
		self::assertFalse($captcha->validate('wrong'));
	}

	public function testValidateIsCaseInsensitiveWhenConfigured(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->setCaseSensitive(false);
		$captcha->fixedToken = 'ABCD';
		self::assertTrue($captcha->validate('abcd'), 'Case-insensitive validation upper-cases the input.');
	}

	public function testValidateIncrementsTestNumberOncePerRequest(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->fixedToken = 'aBcD';

		$captcha->validate('nope');
		$captcha->validate('nope again');
		self::assertSame(1, $captcha->pubGetViewState('TestNumber', 0), 'The test counter advances once per request.');
	}

	public function testValidateOverTestLimitRegeneratesAndFails(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->setTestLimit(2);
		$captcha->fixedToken = 'aBcD';
		$captcha->setPublicKey('fixedpublic');
		$captcha->pubSetViewState('TestNumber', 5);

		self::assertFalse($captcha->validate('aBcD'), 'Exceeding the test limit fails even a correct token.');
		self::assertSame('', $captcha->pubGetViewState('PublicKey', ''), 'The token is regenerated on limit overflow.');
		self::assertSame(0, $captcha->pubGetViewState('TestNumber', 0));
	}

	public function testValidateExpiredRegeneratesAndFails(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(300);
		$captcha->pubSetTokenGenerated(1_000);
		$captcha->fixedToken = 'aBcD';
		$captcha->setPublicKey('fixedpublic');

		$clock = new TMockClock();
		$clock->setTime(1_000 + 400);
		$captcha->setClock($clock);

		self::assertFalse($captcha->validate('aBcD'), 'An expired token fails validation.');
		self::assertSame(0, $captcha->pubGetViewState('TokenGenerated', 0), 'Expiry regenerates the token.');
	}

	public function testGenerateTokenUsesHmacSha256(): void
	{
		$captcha = $this->newCaptcha();
		$alphabet = '234578adefhijmnrtABDEFGHJLMNRT';
		$expected = substr($captcha->pubHash2string(hash_hmac('sha256', 'pub', 'priv'), $alphabet), 0, 8);
		self::assertSame($expected, $captcha->pubGenerateToken('pub', 'priv', $alphabet, 8, true));
	}

	// -----------------------------------------------------------------------
	// SingleUse
	// -----------------------------------------------------------------------

	public function testSingleUseDefaultAndSet(): void
	{
		$captcha = $this->newCaptcha();
		self::assertTrue($captcha->getSingleUse());
		$captcha->setSingleUse('false');
		self::assertFalse($captcha->getSingleUse());
	}

	public function testValidateClaimsTokenSoReplayFails(): void
	{
		$cache = $this->installCache();
		$first = $this->newCaptcha();
		$first->setTokenExpiry(0);
		$first->setMinTokenLength(6);
		$first->setMaxTokenLength(6);
		$first->setPublicKey('replayedpublic');
		$token = $first->getToken();
		self::assertTrue($first->validate($token));
		self::assertTrue($first->validate($token), 'Repeated validation in the same request keeps the result.');
		self::assertArrayHasKey(TCaptcha::CACHE_KEY_PREFIX . 'replayedpublic', $cache->values);

		// A replayed page state carries the same public key in a later request.
		$replay = $this->newCaptcha();
		$replay->setTokenExpiry(0);
		$replay->setMinTokenLength(6);
		$replay->setMaxTokenLength(6);
		$replay->setPublicKey('replayedpublic');
		self::assertFalse($replay->validate($token), 'A claimed token fails in a later request.');
		self::assertNotSame('replayedpublic', $replay->getPublicKey(), 'A replayed token is regenerated.');
	}

	public function testValidateWithoutSingleUseAllowsReplay(): void
	{
		$cache = $this->installCache();
		foreach ([1, 2] as $request) {
			$captcha = $this->newCaptcha();
			$captcha->setTokenExpiry(0);
			$captcha->setSingleUse(false);
			$captcha->setPublicKey('reusedpublic');
			self::assertTrue($captcha->validate($captcha->getToken()), "Request $request passes.");
		}
		self::assertSame([], $cache->values, 'Nothing is claimed.');
	}

	public function testValidateWithoutCachePassesAndClaimsNothing(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		self::assertTrue($captcha->validate($captcha->getToken()), 'Without a cache, a solved token passes as before.');
	}

	public function testWrongTokenClaimsNothing(): void
	{
		$cache = $this->installCache();
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		$captcha->fixedToken = 'aBcD';
		self::assertFalse($captcha->validate('wrong'));
		self::assertSame([], $cache->values);
	}

	public function testClaimLastsUntilTokenExpiry(): void
	{
		$cache = $this->installCache();
		$clock = new TMockClock();
		$clock->setTime(10_000);
		$captcha = $this->newCaptcha();
		$captcha->setClock($clock);
		$captcha->setTokenExpiry(600);
		$captcha->pubSetTokenGenerated(10_000 - 100);
		$captcha->setPublicKey('expiringpublic');
		self::assertTrue($captcha->validate($captcha->getToken()));
		self::assertSame(500, $cache->expires[TCaptcha::CACHE_KEY_PREFIX . 'expiringpublic']);
	}

	public function testClaimLifetimeWithoutExpiry(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setTokenExpiry(0);
		self::assertSame(TCaptcha::SINGLE_USE_TTL, PradoUnit::invoke($captcha, 'getClaimLifetime'));
	}

	// -----------------------------------------------------------------------
	// AlternateText
	// -----------------------------------------------------------------------

	public function testAlternateTextDefaultsToDescription(): void
	{
		$captcha = $this->newCaptcha();
		self::assertSame('CAPTCHA image: type the characters shown', $captcha->getAlternateText());
		$captcha->setAlternateText('Security code');
		self::assertSame('Security code', $captcha->getAlternateText());
	}

	// -----------------------------------------------------------------------
	// regenerateToken
	// -----------------------------------------------------------------------

	public function testRegenerateTokenClearsState(): void
	{
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		$captcha->pubSetTokenGenerated(1_000);
		$captcha->pubSetViewState('TokenLength', 7);
		$captcha->pubSetViewState('RandomSeed', 42);
		$captcha->pubSetViewState('TestNumber', 3);

		$captcha->regenerateToken();

		self::assertSame('', $captcha->pubGetViewState('PublicKey', ''));
		self::assertSame(0, $captcha->pubGetViewState('TokenGenerated', 0));
		self::assertSame(0, $captcha->pubGetViewState('RandomSeed', 0));
		self::assertSame(0, $captcha->pubGetViewState('TestNumber', 0));
		self::assertNull($captcha->pubGetViewState('TokenLength'));
	}

	// -----------------------------------------------------------------------
	// checkRequirements
	// -----------------------------------------------------------------------

	public function testCheckRequirementsReturnsBool(): void
	{
		self::assertIsBool(TCaptcha::checkRequirements());
	}

	// -----------------------------------------------------------------------
	// Asset file paths
	// -----------------------------------------------------------------------

	public function testCaptchaScriptFileExists(): void
	{
		$path = $this->newCaptcha()->pubGetCaptchaScriptFile();
		self::assertStringEndsWith('captcha.php', $path);
		self::assertFileExists($path);
	}

	public function testFontFileExists(): void
	{
		$path = $this->newCaptcha()->pubGetFontFile();
		self::assertStringEndsWith('verase.ttf', $path);
		self::assertFileExists($path);
	}

	// -----------------------------------------------------------------------
	// Standalone captcha.php script
	// -----------------------------------------------------------------------

	public function testCaptchaScriptReferencesNoFrameworkClass(): void
	{
		$tokens = token_get_all(file_get_contents($this->newCaptcha()->pubGetCaptchaScriptFile()));
		$inNamespace = false;
		foreach ($tokens as $token) {
			if (!is_array($token)) {
				if ($token === ';') {
					$inNamespace = false;
				}
				continue;
			}
			[$id, $text, $line] = $token;
			if ($id === T_NAMESPACE) {
				$inNamespace = true;
				continue;
			}
			self::assertNotSame(T_USE, $id, "captcha.php imports a class on line $line; no autoloader runs for it.");
			self::assertNotSame(T_DOUBLE_COLON, $id, "captcha.php references a class member on line $line.");
			if (!$inNamespace) {
				self::assertNotContains($id, [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], "captcha.php references '$text' on line $line.");
			}
		}
	}

	public static function provideCaptchaScriptThemes(): array
	{
		return [
			'plain' => [0],
			'scribble' => [0x0008],
			'all features' => [63],
		];
	}

	/**
	 * Runs a copy of captcha.php in a PHP process without the Prado autoloader, as a browser request does.
	 * The runner turns every error, warning, and deprecation into stderr output and exit code 1.
	 * @param TestCaptcha $captcha the captcha whose script, font, and key file are copied.
	 * @param string $runnerBody the PHP code that sets $_GET and requires the script.
	 * @param array $args the command line arguments of the runner.
	 * @return array{0: string, 1: string, 2: int} stdout, stderr, and the exit code.
	 */
	private function runCaptchaScript(TestCaptcha $captcha, string $runnerBody, array $args): array
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pradocaptcha' . bin2hex(random_bytes(6));
		mkdir($dir);
		$files = [
			'captcha.php' => file_get_contents($captcha->pubGetCaptchaScriptFile()),
			'verase.ttf' => file_get_contents($captcha->pubGetFontFile()),
			'captcha_key.php' => $captcha->keyFileContent,
			'runner.php' => '<?php
set_error_handler(function ($no, $msg, $file, $line) {
	fwrite(STDERR, "$msg at $file:$line");
	exit(1);
});
' . $runnerBody,
		];
		foreach ($files as $name => $content) {
			file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $content);
		}
		try {
			$command = array_merge([PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=stderr', $dir . DIRECTORY_SEPARATOR . 'runner.php'], $args);
			$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			$exitCode = proc_close($process);
		} finally {
			foreach (array_keys($files) as $name) {
				@unlink($dir . DIRECTORY_SEPARATOR . $name);
			}
			@rmdir($dir);
		}
		return [$stdout, $stderr, $exitCode];
	}

	private function requireScriptRunner(): void
	{
		if (!TCaptcha::checkRequirements() || !function_exists('proc_open')) {
			self::markTestSkipped('Requires GD with FreeType and proc_open.');
		}
	}

	/**
	 * @dataProvider provideCaptchaScriptThemes
	 * @param int $theme
	 */
	public function testCaptchaScriptRendersPngStandalone(int $theme): void
	{
		$this->requireScriptRunner();
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		$captcha->setTokenImageTheme($theme);
		[$stdout, $stderr, $exitCode] = $this->runCaptchaScript($captcha, '$_GET["options"] = $argv[1];
require __DIR__ . "/captcha.php";
', [$captcha->pubGetTokenImageOptions()]);

		self::assertSame('', $stderr, 'captcha.php raises no error, warning, or deprecation.');
		self::assertSame(0, $exitCode);
		self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $stdout, 'captcha.php writes a PNG image.');
	}

	public function testCaptchaScriptDerivesTheControlsToken(): void
	{
		$this->requireScriptRunner();
		$captcha = $this->newCaptcha();
		$captcha->setPublicKey('fixedpublic');
		$captcha->setMinTokenLength(12);
		$captcha->setMaxTokenLength(12);
		[$stdout, $stderr, $exitCode] = $this->runCaptchaScript($captcha, 'ob_start();
require __DIR__ . "/captcha.php";
ob_end_clean();
echo \\Prado\\Web\\UI\\WebControls\\assets\\generateToken($argv[1], $privateKey, $argv[2], 12, true);
', ['fixedpublic', $captcha->getTokenAlphabet()]);

		self::assertSame('', $stderr);
		self::assertSame(0, $exitCode);
		self::assertSame($captcha->getToken(), $stdout, 'captcha.php draws the token TCaptcha validates.');
	}

	public function testCaptchaScriptRejectsAlteredOptions(): void
	{
		$this->requireScriptRunner();
		$captcha = $this->newCaptcha();
		$decoded = base64_decode($captcha->pubGetTokenImageOptions());
		$altered = base64_encode(str_repeat('0', 64) . substr($decoded, 64));
		[$stdout, $stderr, $exitCode] = $this->runCaptchaScript($captcha, '$_GET["options"] = $argv[1];
require __DIR__ . "/captcha.php";
echo "\n", $token;
', [$altered]);

		self::assertSame('', $stderr);
		self::assertSame(0, $exitCode);
		self::assertStringEndsWith("\nerror", $stdout, 'An altered signature draws the error token.');
	}

	public function testCaptchaScriptHidesErrors(): void
	{
		$this->requireScriptRunner();
		$captcha = $this->newCaptcha();
		// An array option makes base64_decode() throw a TypeError.
		[$stdout, $stderr, $exitCode] = $this->runCaptchaScript($captcha, 'restore_error_handler();
$_GET["options"] = ["x"];
require __DIR__ . "/captcha.php";
', []);

		self::assertSame('', $stdout, 'No error text or image is written.');
		self::assertSame('', $stderr, 'No error is displayed.');
		self::assertSame(1, $exitCode);
	}
}
