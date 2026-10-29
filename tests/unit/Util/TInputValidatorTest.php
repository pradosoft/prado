<?php

namespace Prado\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Util\TInputValidationException;
use Prado\Util\TInputValidator;

class TInputValidatorTest extends TestCase
{
	private const STORE_RULES = [
		'name' => 'required|string|max:10',
		'age' => 'required|integer|min:0|max:150',
		'email' => 'required|email',
	];

	private function validator(): TInputValidator
	{
		return new TInputValidator();
	}

	// ── Validation ─────────────────────────────────────────────────────────────

	public function testValidatePassesWithCorrectData(): void
	{
		$result = $this->validator()->validate([
			'name' => 'Alice',
			'age' => 30,
			'email' => 'alice@example.com',
		], self::STORE_RULES);
		$this->assertSame('Alice', $result['name']);
		$this->assertSame(30, $result['age']);
	}

	public function testValidateThrowsOnMissingRequired(): void
	{
		try {
			$this->validator()->validate(['name' => 'Bob'], self::STORE_RULES);
			$this->fail('Expected TInputValidationException');
		} catch (TInputValidationException $e) {
			$errors = $e->getErrors();
			$this->assertArrayHasKey('age', $errors);
			$this->assertArrayHasKey('email', $errors);
		}
	}

	public function testValidateThrowsOnInvalidEmail(): void
	{
		try {
			$this->validator()->validate([
				'name' => 'Bob',
				'age' => 25,
				'email' => 'not-an-email',
			], self::STORE_RULES);
			$this->fail('Expected TInputValidationException');
		} catch (TInputValidationException $e) {
			$this->assertArrayHasKey('email', $e->getErrors());
		}
	}

	public function testValidateThrowsOnStringTooLong(): void
	{
		try {
			$this->validator()->validate([
				'name' => str_repeat('a', 11), // max:10
				'age' => 25,
				'email' => 'a@b.com',
			], self::STORE_RULES);
			$this->fail('Expected TInputValidationException');
		} catch (TInputValidationException $e) {
			$this->assertArrayHasKey('name', $e->getErrors());
		}
	}

	public function testValidateThrowsOnIntegerOutOfRange(): void
	{
		try {
			$this->validator()->validate([
				'name' => 'Bob',
				'age' => 200, // max:150
				'email' => 'a@b.com',
			], self::STORE_RULES);
			$this->fail('Expected TInputValidationException');
		} catch (TInputValidationException $e) {
			$this->assertArrayHasKey('age', $e->getErrors());
		}
	}

	public function testValidateCoercesNumericStringToInteger(): void
	{
		$result = $this->validator()->validate(['count' => '7'], ['count' => 'integer']);
		$this->assertSame(7, $result['count']);
	}

	public function testValidateNullableFieldAcceptsNull(): void
	{
		$result = $this->validator()->validate(['bio' => null], ['bio' => 'nullable|string']);
		$this->assertNull($result['bio']);
	}

	public function testValidateInRuleAcceptsValidValue(): void
	{
		$result = $this->validator()->validate(['status' => 'active'], ['status' => 'in:active,inactive,pending']);
		$this->assertSame('active', $result['status']);
	}

	public function testValidateInRuleRejectsInvalidValue(): void
	{
		try {
			$this->validator()->validate(['status' => 'deleted'], ['status' => 'in:active,inactive']);
			$this->fail('Expected TInputValidationException');
		} catch (TInputValidationException $e) {
			$this->assertSame(['status' => ['The status field must be one of: active, inactive.']], $e->getErrors());
		}
	}

	public function testValidateBooleanRulePasses(): void
	{
		$v = $this->validator();
		foreach ([true, false, 1, 0, '1', '0', 'true', 'false'] as $val) {
			$result = $v->validate(['flag' => $val], ['flag' => 'boolean']);
			$this->assertIsBool($result['flag']);
		}
	}

	public function testValidateBooleanRuleRejectsNonBoolean(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['flag' => 'yes'], ['flag' => 'boolean']);
	}

	public function testValidateArrayRulePassesAndRejects(): void
	{
		$v = $this->validator();
		$this->assertSame(['tags' => ['a', 'b']], $v->validate(['tags' => ['a', 'b']], ['tags' => 'array']));

		$this->expectException(TInputValidationException::class);
		$v->validate(['tags' => 'not-array'], ['tags' => 'array']);
	}

	public function testValidateUrlRulePassesAndRejects(): void
	{
		$v = $this->validator();
		$this->assertSame(['site' => 'https://example.com'], $v->validate(['site' => 'https://example.com'], ['site' => 'url']));

		$this->expectException(TInputValidationException::class);
		$v->validate(['site' => 'not a url'], ['site' => 'url']);
	}

	public function testValidateFloatAndNumericCoerce(): void
	{
		$v = $this->validator();
		$result = $v->validate(['p' => '3.14'], ['p' => 'numeric']);
		$this->assertSame(3.14, $result['p']);
		$result = $v->validate(['p' => '2'], ['p' => 'float']);
		$this->assertSame(2.0, $result['p']);
	}

	public function testValidateNumericRejectsNonNumeric(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['p' => 'abc'], ['p' => 'numeric']);
	}

	public function testValidateMinAndMaxOnNumber(): void
	{
		$v = $this->validator();
		// min on number
		$result = $v->validate(['n' => 10], ['n' => 'integer|min:5']);
		$this->assertSame(10, $result['n']);
		try {
			$v->validate(['n' => 1], ['n' => 'integer|min:5']);
			$this->fail('expected min violation');
		} catch (TInputValidationException $e) {
			$this->assertSame(['n' => ['The n field must be at least 5.']], $e->getErrors());
		}
		// min on string length
		$result = $v->validate(['s' => 'hello'], ['s' => 'string|min:3']);
		$this->assertSame('hello', $result['s']);
		try {
			$v->validate(['s' => 'hi'], ['s' => 'string|min:3']);
			$this->fail('expected string min violation');
		} catch (TInputValidationException $e) {
			$this->assertSame(['s' => ['The s field must be at least 3 characters.']], $e->getErrors());
		}
	}

	public function testValidateNullableSkipsTypeRulesWhenNull(): void
	{
		// nullable + integer with null should not error
		$result = $this->validator()->validate(['n' => null], ['n' => 'nullable|integer']);
		$this->assertNull($result['n']);
	}

	public function testValidateRequiredAndNullableTogether(): void
	{
		// When both are present and value is null, required wins (error).
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['x' => null], ['x' => 'required|nullable|string']);
	}

	public function testValidateOmitsUndeclaredFields(): void
	{
		$result = $this->validator()->validate(
			['name' => 'Alice', 'secret' => 'leak'],
			['name' => 'string']
		);
		$this->assertSame(['name' => 'Alice'], $result);
	}

	public function testValidateUnknownRuleThrowsConfigurationException(): void
	{
		// A typo in a rule name is a developer error, not a data error.
		$this->expectException(TConfigurationException::class);
		$this->validator()->validate(['name' => 'Alice'], ['name' => 'requried|string']);
	}

	// ── Validation edge cases (regressions + per-rule coverage) ────────────────

	public function testValidateIntegerRejectsDoubleMinus(): void
	{
		// Regression: '--5' previously passed ctype_digit(ltrim($v, '-')) and
		// got cast to 0. filter_var-based check rejects it cleanly.
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['n' => '--5'], ['n' => 'integer']);
	}

	public function testValidateIntegerRejectsTrailingNoise(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['n' => '5abc'], ['n' => 'integer']);
	}

	public function testValidateIntegerRejectsFloatString(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['n' => '1.5'], ['n' => 'integer']);
	}

	public function testValidateIntegerRejectsScientificNotation(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['n' => '5e2'], ['n' => 'integer']);
	}

	public function testValidateIntegerAcceptsNegativeAndZero(): void
	{
		$v = $this->validator();
		$this->assertSame(-7, $v->validate(['n' => '-7'], ['n' => 'integer'])['n']);
		$this->assertSame(0, $v->validate(['n' => '0'], ['n' => 'integer'])['n']);
		$this->assertSame(42, $v->validate(['n' => 42], ['n' => 'integer'])['n']);
	}

	/** @dataProvider booleanLikeValues */
	public function testValidateBooleanAcceptsEachLikeValue(mixed $value, bool $expected): void
	{
		$result = $this->validator()->validate(['flag' => $value], ['flag' => 'boolean']);
		$this->assertSame($expected, $result['flag']);
	}

	public static function booleanLikeValues(): array
	{
		return [
			'bool true' => [true, true],
			'bool false' => [false, false],
			'int 1' => [1, true],
			'int 0' => [0, false],
			'string "1"' => ['1', true],
			'string "0"' => ['0', false],
			'string "true"' => ['true', true],
			'string "false"' => ['false', false],
		];
	}

	public function testValidateBoolAliasMatchesBoolean(): void
	{
		$result = $this->validator()->validate(['flag' => '1'], ['flag' => 'bool']);
		$this->assertTrue($result['flag']);
	}

	public function testValidateMaxOnNumberRejectsAboveLimit(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['n' => 200], ['n' => 'integer|max:150']);
	}

	public function testValidateMaxOnNumberAcceptsBoundary(): void
	{
		$result = $this->validator()->validate(['n' => 150], ['n' => 'integer|max:150']);
		$this->assertSame(150, $result['n']);
	}

	public function testValidateMinOnNumberAcceptsBoundary(): void
	{
		$result = $this->validator()->validate(['n' => 5], ['n' => 'integer|min:5']);
		$this->assertSame(5, $result['n']);
	}

	public function testValidateMaxOnStringUsesMultibyteLength(): void
	{
		// mb_strlen counts characters, not bytes; the five-character string
		// "héllo" should be rejected by max:4.
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['s' => 'héllo'], ['s' => 'string|max:4']);
	}

	public function testValidateMinOnStringUsesMultibyteLength(): void
	{
		// "héllo" is 5 mb chars; min:5 must pass.
		$result = $this->validator()->validate(['s' => 'héllo'], ['s' => 'string|min:5']);
		$this->assertSame('héllo', $result['s']);
	}

	public function testValidateInRuleTrimsWhitespace(): void
	{
		$result = $this->validator()->validate(
			['status' => 'active'],
			['status' => 'in: active , inactive , pending ']
		);
		$this->assertSame('active', $result['status']);
	}

	public function testValidateEmailRejectsMissingTld(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['e' => 'user@'], ['e' => 'email']);
	}

	public function testValidateUrlRejectsBareHostname(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['site' => 'example.com'], ['site' => 'url']);
	}

	public function testValidateRequiredMissingField(): void
	{
		try {
			$this->validator()->validate([], ['name' => 'required|string']);
			$this->fail('expected exception');
		} catch (TInputValidationException $e) {
			$this->assertSame(['name' => ['The name field is required.']], $e->getErrors());
		}
	}

	public function testValidateMultipleFieldErrorsCollected(): void
	{
		try {
			$this->validator()->validate(
				['email' => 'bad', 'age' => 'x'],
				['email' => 'email', 'age' => 'integer']
			);
			$this->fail('expected exception');
		} catch (TInputValidationException $e) {
			$errors = $e->getErrors();
			$this->assertArrayHasKey('email', $errors);
			$this->assertArrayHasKey('age', $errors);
		}
	}

	// ── in: rule scalar guard (bug regression) ─────────────────────────────────

	public function testValidateInRuleRejectsArrayValueWithoutWarning(): void
	{
		// An array value must fail 'in' validation rather than emitting an
		// "Array to string conversion" warning.
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['role' => ['admin']], ['role' => 'in:admin,editor']);
	}

	public function testValidateInRuleAcceptsValidScalar(): void
	{
		$out = $this->validator()->validate(['role' => 'admin'], ['role' => 'in:admin,editor']);
		$this->assertSame(['role' => 'admin'], $out);
	}

	// ── validate() rule-as-array and presence/null branches ────────────────────

	public function testValidateRuleSuppliedAsArray(): void
	{
		$out = $this->validator()->validate(['n' => '5'], ['n' => ['required', 'integer']]);
		$this->assertSame(['n' => 5], $out);
	}

	public function testValidateRequiredPresentButNullFails(): void
	{
		$this->expectException(TInputValidationException::class);
		$this->validator()->validate(['name' => null], ['name' => 'required|string']);
	}

	public function testValidateNullableAbsentOmitsField(): void
	{
		$out = $this->validator()->validate([], ['nickname' => 'nullable|string']);
		$this->assertSame([], $out);
	}

	public function testValidateNullablePresentNullKeepsNull(): void
	{
		$out = $this->validator()->validate(['nickname' => null], ['nickname' => 'nullable|string']);
		$this->assertArrayHasKey('nickname', $out);
		$this->assertNull($out['nickname']);
	}

	public function testValidateBooleanRuleCoercesValue(): void
	{
		$out = $this->validator()->validate(['flag' => 'true'], ['flag' => 'boolean']);
		$this->assertTrue($out['flag']);
		$out2 = $this->validator()->validate(['flag' => '0'], ['flag' => 'boolean']);
		$this->assertFalse($out2['flag']);
	}

	public function testValidateOptionalAbsentFieldOmitted(): void
	{
		$out = $this->validator()->validate(['a' => 1], ['a' => 'integer', 'b' => 'integer']);
		$this->assertSame(['a' => 1], $out);
	}

	public function testValidateInvalidFieldNotInValidated(): void
	{
		$v = $this->validator();
		$this->assertFalse($v->isValid(['a' => 'x', 'b' => '2'], ['a' => 'integer', 'b' => 'integer']));
		$this->assertSame(['b' => 2], $v->getValidated());
		$this->assertSame(['a' => ['The a field must be an integer.']], $v->getErrors());
	}

	public function testValidateCollectsEveryFailingRuleOfAField(): void
	{
		try {
			$this->validator()->validate(['s' => 5], ['s' => 'string|max:2']);
			$this->fail('expected exception');
		} catch (TInputValidationException $e) {
			$this->assertSame(['s' => [
				'The s field must be a string.',
				'The s field must not exceed 2.',
			]], $e->getErrors());
		}
	}

	// ── isValid() / getErrors() / getValidated() ───────────────────────────────

	public function testIsValidTrueStoresValidatedAndNoErrors(): void
	{
		$v = $this->validator();
		$this->assertSame([], $v->getErrors());
		$this->assertSame([], $v->getValidated());
		$this->assertTrue($v->isValid(['n' => '7', 'extra' => 1], ['n' => 'required|integer']));
		$this->assertSame([], $v->getErrors());
		$this->assertSame(['n' => 7], $v->getValidated());
	}

	public function testIsValidFalseStoresErrorsWithoutThrowing(): void
	{
		$v = $this->validator();
		$this->assertFalse($v->isValid(['e' => 'bad'], ['e' => 'email', 'name' => 'required']));
		$this->assertSame([
			'e' => ['The e field must be a valid email address.'],
			'name' => ['The name field is required.'],
		], $v->getErrors());
		$this->assertSame([], $v->getValidated());
	}

	public function testIsValidResetsPreviousOutcome(): void
	{
		$v = $this->validator();
		$this->assertFalse($v->isValid([], ['name' => 'required']));
		$this->assertTrue($v->isValid(['name' => 'Al'], ['name' => 'required']));
		$this->assertSame([], $v->getErrors());
		$this->assertSame(['name' => 'Al'], $v->getValidated());
	}

	public function testValidateStoresOutcomeOnSuccessAndFailure(): void
	{
		$v = $this->validator();
		$v->validate(['n' => '1'], ['n' => 'integer']);
		$this->assertSame(['n' => 1], $v->getValidated());
		try {
			$v->validate(['n' => 'x'], ['n' => 'integer']);
			$this->fail('expected exception');
		} catch (TInputValidationException $e) {
			$this->assertSame($e->getErrors(), $v->getErrors());
			$this->assertSame([], $v->getValidated());
		}
	}

	// ── addRule() / hasRule() ──────────────────────────────────────────────────

	public function testHasRuleForBuiltInAndUnknownRules(): void
	{
		$v = $this->validator();
		foreach (['required', 'nullable', 'string', 'integer', 'int', 'float', 'numeric', 'boolean', 'bool', 'array', 'email', 'url', 'min', 'max', 'in'] as $name) {
			$this->assertTrue($v->hasRule($name), $name);
		}
		$this->assertFalse($v->hasRule('requried'));
		$this->assertFalse($v->hasRule('upper'));
	}

	public function testAddRuleRegistersCustomRuleThatCoerces(): void
	{
		$v = $this->validator();
		$v->addRule('upper', function (mixed &$value, ?string $parameter, string $field): ?string {
			if (!is_string($value)) {
				return "The {$field} field must be text.";
			}
			$value = strtoupper($value);
			return null;
		});
		$this->assertTrue($v->hasRule('upper'));
		$this->assertSame(['s' => 'HELLO'], $v->validate(['s' => 'hello'], ['s' => 'required|upper|max:5']));
		$this->assertFalse($v->isValid(['s' => 5], ['s' => 'upper']));
		$this->assertSame(['s' => ['The s field must be text.']], $v->getErrors());
	}

	public function testAddRuleReceivesParameterAndFieldName(): void
	{
		$v = $this->validator();
		$seen = [];
		$v->addRule('startswith', function (mixed &$value, ?string $parameter, string $field) use (&$seen): ?string {
			$seen = [$parameter, $field];
			return str_starts_with((string) $value, (string) $parameter) ? null : "The {$field} field must start with {$parameter}.";
		});
		$this->assertSame(['code' => 'AB-1'], $v->validate(['code' => 'AB-1'], ['code' => 'startswith:AB-']));
		$this->assertSame(['AB-', 'code'], $seen);
		$this->assertFalse($v->isValid(['code' => 'XY'], ['code' => 'startswith:AB-']));
		$this->assertSame(['code' => ['The code field must start with AB-.']], $v->getErrors());
	}

	public function testAddRuleReplacesBuiltInRule(): void
	{
		$v = $this->validator();
		$v->addRule('string', function (mixed &$value, ?string $parameter, string $field): ?string {
			$value = trim((string) $value);
			return null;
		});
		$this->assertSame(['s' => 'x'], $v->validate(['s' => '  x '], ['s' => 'string']));
	}

	public function testAddRuleIsPerInstance(): void
	{
		$a = $this->validator();
		$a->addRule('custom', fn (mixed &$value, ?string $parameter, string $field): ?string => null);
		$this->assertTrue($a->hasRule('custom'));
		$this->assertFalse($this->validator()->hasRule('custom'));
	}

	// ── TInputValidationException ──────────────────────────────────────────────

	public function testExceptionExposesErrorsAndCode(): void
	{
		$errors = ['a' => ['The a field is required.'], 'b' => ['x', 'y']];
		$e = new TInputValidationException($errors);
		$this->assertSame($errors, $e->getErrors());
		$this->assertSame('inputvalidator_invalid', $e->getErrorCode());
		$this->assertInstanceOf(\Prado\Exceptions\TInvalidDataValueException::class, $e);
		$this->assertNull($e->getPrevious());

		$previous = new \RuntimeException('inner');
		$e = new TInputValidationException([], $previous);
		$this->assertSame([], $e->getErrors());
		$this->assertSame($previous, $e->getPrevious());
	}
}
