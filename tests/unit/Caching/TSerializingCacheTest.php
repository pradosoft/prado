<?php

/**
 * TSerializingCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TSerializingCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\TSecurityManager;

/**
 * Tests for TSerializingCache serialization, encryption, encoding, and security-manager
 * resolution. Uses the {@see TTestSerializingCache} harness (an array-backed concrete
 * TSerializingCache that records raw payloads via {@see TTestSerializingCache::onlyStored()}).
 */
class TSerializingCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestSerializingCache
	{
		$cache = new TTestSerializingCache();
		$cache->setPrimaryCache(false); // do not register as the application cache
		return $cache;
	}

	// ── Format props frozen after init ──────────────────────────────────────────

	/**
	 * @dataProvider frozenSetterProvider
	 */
	public function testFormatPropertiesCannotChangeAfterInit(string $setter, mixed $value): void
	{
		$cache = $this->newCache();
		$cache->init(null);
		$this->expectException(TInvalidOperationException::class);
		$cache->$setter($value);
	}

	public static function frozenSetterProvider(): array
	{
		return [
			'SerializationType' => ['setSerializationType', TSerializingCache::SERIALIZATION_JSON],
			'Encrypt'           => ['setEncrypt', true],
			'Encoding'          => ['setEncoding', TSerializingCache::ENCODING_BASE64],
			'SecurityManager'   => ['setSecurityManager', 'someModule'],
		];
	}

	public function testFormatPropertiesAreSettableBeforeInit(): void
	{
		$cache = $this->newCache();
		$cache->setSerializationType(TSerializingCache::SERIALIZATION_JSON);
		$cache->setEncoding(TSerializingCache::ENCODING_HEX);
		$cache->init(null); // must not throw
		$this->assertSame(TSerializingCache::SERIALIZATION_JSON, $cache->getSerializationType());
		$this->assertSame(TSerializingCache::ENCODING_HEX, $cache->getEncoding());
	}

	// ── Defaults ──────────────────────────────────────────────────────────────

	public function testDefaults(): void
	{
		$cache = $this->newCache();
		$this->assertFalse($cache->getEncrypt());
		$this->assertSame(TSerializingCache::ENCODING_NONE, $cache->getEncoding());
	}

	// ── Encoding round-trips (no encryption) ───────────────────────────────────

	public function testEncodingNoneRoundTrip(): void
	{
		$cache = $this->newCache();
		$cache->init(null);
		$cache->set('k', ['a' => 1, 'b' => 'two']);
		$this->assertSame(['a' => 1, 'b' => 'two'], $cache->get('k'));
	}

	public function testEncodingBase64RoundTripAndStoredForm(): void
	{
		$cache = $this->newCache();
		$cache->setEncoding(TSerializingCache::ENCODING_BASE64);
		$cache->init(null);
		$cache->set('k', 'hello-world');
		$stored = $cache->onlyStored();
		// The stored payload is valid base64 and decodes back to a serialize() string.
		$this->assertNotFalse(base64_decode($stored, true));
		$this->assertStringContainsString('hello-world', (string) base64_decode($stored, true));
		$this->assertSame('hello-world', $cache->get('k'));
	}

	public function testEncodingHexRoundTrip(): void
	{
		$cache = $this->newCache();
		$cache->setEncoding(TSerializingCache::ENCODING_HEX);
		$cache->init(null);
		$cache->set('k', 'hex-value');
		$this->assertTrue(ctype_xdigit($cache->onlyStored()));
		$this->assertSame('hex-value', $cache->get('k'));
	}

	public function testCorruptHexPayloadIsAMiss(): void
	{
		$cache = $this->newCache();
		$cache->setEncoding(TSerializingCache::ENCODING_HEX);
		$cache->init(null);
		$cache->set('k', 'v');
		// Replace the stored payload with non-hex data → decode() fails → miss.
		$key = array_key_first($cache->store);
		$cache->store[$key] = 'not-hex!';
		$this->assertFalse($cache->get('k'));
	}

	// ── setEncoding validation ─────────────────────────────────────────────────

	public function testSetEncodingRejectsUnknownValue(): void
	{
		$cache = $this->newCache();
		$this->expectException(TInvalidDataValueException::class);
		$cache->setEncoding('Rot13');
	}

	// ── Encryption ─────────────────────────────────────────────────────────────

	public function testEncryptRoundTripHidesPlaintext(): void
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('openssl extension required for encryption.');
		}
		$cache = $this->newCache();
		$cache->setEncrypt(true);
		$cache->setEncoding(TSerializingCache::ENCODING_BASE64);
		$cache->init(null);

		$secret = 'super-secret-value-1234567890';
		$cache->set('k', $secret);

		$stored = $cache->onlyStored();
		$this->assertStringNotContainsString($secret, $stored, 'Stored payload must not contain the plaintext.');
		$this->assertStringNotContainsString($secret, (string) base64_decode($stored, true), 'Decoded ciphertext must not contain the plaintext.');
		$this->assertSame($secret, $cache->get('k'), 'Decryption must restore the original value.');
	}

	public function testTamperedCiphertextIsAMiss(): void
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('openssl extension required for encryption.');
		}
		$cache = $this->newCache();
		$cache->setEncrypt(true); // Encoding=None → raw ciphertext stored
		$cache->init(null);
		$cache->set('k', 'value');

		// Overwrite the ciphertext with garbage → decrypt() returns false → miss.
		$key = array_key_first($cache->store);
		$cache->store[$key] = 'not a valid ciphertext';
		$this->assertFalse($cache->get('k'));
	}

	// ── SecurityManager property ───────────────────────────────────────────────

	public function testSecurityManagerDefaultsToApplicationSecurityManager(): void
	{
		$cache = $this->newCache();
		$this->assertInstanceOf(TSecurityManager::class, $cache->getSecurityManager());
	}

	public function testSetSecurityManagerRejectsInvalidType(): void
	{
		$cache = $this->newCache();
		$this->expectException(TConfigurationException::class);
		$cache->setSecurityManager(123);
	}

	public function testGetSecurityManagerRejectsUnknownModuleId(): void
	{
		$cache = $this->newCache();
		$cache->setSecurityManager('no_such_security_manager_module');
		$this->expectException(TConfigurationException::class);
		$cache->getSecurityManager();
	}

	public function testInitFailsFastWhenEncryptWithBadSecurityManagerId(): void
	{
		$cache = $this->newCache();
		$cache->setEncrypt(true);
		$cache->setSecurityManager('no_such_security_manager_module');
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	// ── SerializationType ───────────────────────────────────────────────────────

	public function testSerializationTypeDefaultsToPhp(): void
	{
		$this->assertSame(TSerializingCache::SERIALIZATION_PHP, $this->newCache()->getSerializationType());
	}

	public function testJsonSerializationRoundTrips(): void
	{
		$cache = $this->newCache();
		$cache->setSerializationType(TSerializingCache::SERIALIZATION_JSON);
		$cache->init(null);
		$cache->set('k', ['a' => 1, 'b' => ['c' => 2]]);
		// JSON decodes to arrays; the dependency wrapper is preserved structurally.
		$this->assertSame(['a' => 1, 'b' => ['c' => 2]], $cache->get('k'));
		// The persisted payload is JSON, not PHP-serialized.
		$this->assertStringStartsWith('[', $cache->onlyStored());
	}

	public function testSetSerializationTypeRejectsUnknownValue(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->newCache()->setSerializationType('YAML');
	}

	public function testJsonEncodeFailureThrowsRatherThanStoringFalse(): void
	{
		// An invalid-UTF-8 string cannot be JSON-encoded; serializeValue() must throw
		// instead of silently storing the string "false" (which would read back as a miss).
		$cache = $this->newCache();
		$cache->setSerializationType(TSerializingCache::SERIALIZATION_JSON);
		$this->expectException(TInvalidDataValueException::class);
		$cache->pubSerializeValue("\xB1\x31");
	}

	// ── Property coercion / encoding defaults ───────────────────────────────────

	public function testSetEncryptCoercesStrings(): void
	{
		$cache = $this->newCache();
		$cache->setEncrypt('true');
		$this->assertTrue($cache->getEncrypt());
		$cache->setEncrypt('false');
		$this->assertFalse($cache->getEncrypt());
	}

	public function testEncodingDefaultsToNone(): void
	{
		$this->assertSame(TSerializingCache::ENCODING_NONE, $this->newCache()->getEncoding());
	}

	public function testEncryptedThenBase64CorruptionIsAMiss(): void
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('openssl extension required for encryption.');
		}
		$cache = $this->newCache();
		$cache->setEncrypt(true);
		$cache->setEncoding(TSerializingCache::ENCODING_BASE64);
		$cache->init(null);
		$cache->set('k', 'value');

		// Valid base64 that decrypts to garbage → decrypt() returns false → miss.
		$key = array_key_first($cache->store);
		$cache->store[$key] = base64_encode('totally bogus ciphertext bytes');
		$this->assertFalse($cache->get('k'));
	}
}
