<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
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

	protected function tearDown(): void
	{
		foreach ($this->_toClean as $captcha) {
			$captcha->cleanupKeyFile();
		}
		$this->_toClean = [];
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
		// Payload is a 32-char md5 signature followed by the serialized options.
		$options = unserialize(substr($decoded, 32));
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
		$options = unserialize(substr($decoded, 32));
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
}
