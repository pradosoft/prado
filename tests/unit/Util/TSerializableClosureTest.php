<?php

use Prado\Util\TSerializableClosure;
use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Exceptions\InvalidSignatureException;

class TSerializableClosureTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		// reset global signing/encryption state set by any test
		SerializableClosure::setSecretKey(null);
		TSerializableClosure::setEncryptionUsing(null, null);
	}

	public function testInvokeProxiesToClosure()
	{
		$wrapped = new TSerializableClosure(fn ($x) => $x * 2);
		self::assertEquals(6, $wrapped(3));
	}

	public function testGetClosure()
	{
		$wrapped = new TSerializableClosure(fn () => 'hello');
		$closure = $wrapped->getClosure();
		self::assertInstanceOf(\Closure::class, $closure);
		self::assertEquals('hello', $closure());
	}

	public function testSerializeRoundTrip()
	{
		$wrapped = new TSerializableClosure(fn ($x) => $x + 1);
		$restored = unserialize(serialize($wrapped));
		self::assertInstanceOf(TSerializableClosure::class, $restored);
		self::assertEquals(5, $restored->getClosure()(4));
	}

	public function testCapturedUseVariablesSurvive()
	{
		$y = 10;
		$wrapped = new TSerializableClosure(function ($x) use ($y) {
			return $x + $y;
		});
		$restored = unserialize(serialize($wrapped));
		self::assertEquals(13, $restored->getClosure()(3));
	}

	public function testArrowFunctionCaptureSurvives()
	{
		$base = 100;
		$wrapped = new TSerializableClosure(fn ($x) => $x + $base);
		$restored = unserialize(serialize($wrapped));
		self::assertEquals(105, $restored->getClosure()(5));
	}

	public function testSigningEnablesSignedRoundTrip()
	{
		TSerializableClosure::setSecretKey('test-secret');
		$wrapped = new TSerializableClosure(fn () => 'signed');
		$restored = unserialize(serialize($wrapped));
		self::assertEquals('signed', $restored->getClosure()());
	}

	public function testSigningRejectsUnsignedPayload()
	{
		// Serialize without a signer, then require a signer on unserialize.
		$unsigned = serialize(new TSerializableClosure(fn () => 'x'));

		TSerializableClosure::setSecretKey('test-secret');

		self::expectException(InvalidSignatureException::class);
		unserialize($unsigned);
	}

	public function testSigningRejectsWrongSecret()
	{
		TSerializableClosure::setSecretKey('secret-a');
		$signed = serialize(new TSerializableClosure(fn () => 1));

		TSerializableClosure::setSecretKey('secret-b');
		self::expectException(InvalidSignatureException::class);
		unserialize($signed);
	}

	public function testEncryptionHookMakesPayloadUnreadableAndRoundTrips()
	{
		// Simple reversible transform stands in for the security manager encrypter.
		TSerializableClosure::setEncryptionUsing(
			fn ($s) => base64_encode($s),
			fn ($s) => base64_decode($s),
		);
		$serialized = serialize(new TSerializableClosure(fn () => 'topsecret'));
		self::assertStringContainsString(TSerializableClosure::ENCRYPTED_KEY, $serialized);
		self::assertStringNotContainsString('topsecret', $serialized);
		self::assertEquals('topsecret', unserialize($serialized)->getClosure()());
	}

	public function testEncryptedPayloadWithoutDecrypterThrows()
	{
		TSerializableClosure::setEncryptionUsing(fn ($s) => base64_encode($s), fn ($s) => base64_decode($s));
		$serialized = serialize(new TSerializableClosure(fn () => 1));

		TSerializableClosure::setEncryptionUsing(null, null);
		self::expectException(\Prado\Exceptions\TConfigurationException::class);
		unserialize($serialized);
	}

	public function testSigningAndEncryptionTogetherRoundTripUnreadably()
	{
		TSerializableClosure::setSecretKey('sign-secret');
		TSerializableClosure::setEncryptionUsing(fn ($s) => base64_encode($s), fn ($s) => base64_decode($s));
		$serialized = serialize(new TSerializableClosure(fn () => 'combo'));
		self::assertStringContainsString(TSerializableClosure::ENCRYPTED_KEY, $serialized);
		self::assertStringNotContainsString('combo', $serialized);
		self::assertEquals('combo', unserialize($serialized)->getClosure()());
	}

	public function testFailedDecryptionThrows()
	{
		// The decrypter returns false (a tampered payload or changed key); restoration must fail loudly.
		TSerializableClosure::setEncryptionUsing(fn ($s) => $s, fn ($s) => false);
		$serialized = serialize(new TSerializableClosure(fn () => 1));
		self::expectException(\Prado\Exceptions\TConfigurationException::class);
		unserialize($serialized);
	}

	public function testSignatureIsVerifiedThroughTheEncryptionLayer()
	{
		// Identity encrypter keeps the inner signed payload intact; changing the secret after
		// serialization must still be rejected, proving the HMAC is verified after decryption.
		TSerializableClosure::setEncryptionUsing(fn ($s) => $s, fn ($s) => $s);
		TSerializableClosure::setSecretKey('secret-a');
		$serialized = serialize(new TSerializableClosure(fn () => 1));

		TSerializableClosure::setSecretKey('secret-b');
		self::expectException(InvalidSignatureException::class);
		unserialize($serialized);
	}
}
