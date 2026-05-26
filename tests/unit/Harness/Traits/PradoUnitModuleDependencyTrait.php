<?php

/**
 * PradoUnitModuleDependencyTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

// No namespace — test infrastructure lives outside the Prado\ hierarchy.

use Prado\TPropertyValue;

/**
 * PradoUnitModuleDependencyTrait
 *
 * PHPUnit-style assertions for values returned from
 * {@see \Prado\IModuleDependency::getModuleDependencies()}.
 *
 * The trait declares its assertion as a `public static` method so the standard
 * PHPUnit conventions both work from a `TestCase` subclass:
 *
 * ```php
 * $this->assertModuleDependency('db', $module->getModuleDependencies(false));
 * static::assertModuleDependency('db', $module->getModuleDependencies(false));
 * self::assertModuleDependency('db', $module->getModuleDependencies(false));
 * ```
 *
 * Both `$expected` and `$actual` may use any form the interface contract
 * permits — `null`, a bare string, an indexed array, a key-value array, the
 * verbose `['id' => …, 'required' => …]` form, or any mix of the above. The
 * trait normalizes each side using the same logic
 * {@see \Prado\TApplication::collectModuleDependencies()} applies and compares
 * the resulting `[id => ['id' => string, 'required' => bool]]` maps with
 * {@see \PHPUnit\Framework\Assert::assertSame()}.
 *
 * Invalid entries (null/empty IDs, arrays missing the `'id'` key) are silently
 * skipped, matching the framework's permissive parser — so the assertion never
 * fails on the framework's by-design skipped cases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait PradoUnitModuleDependencyTrait
{
	/**
	 * Asserts that `$actual` is a structurally valid return from
	 * {@see \Prado\IModuleDependency::getModuleDependencies()} and normalizes
	 * to the same dependency map as `$expected`.
	 *
	 * Both arguments accept any of the forms the interface permits:
	 *
	 * | Form                                       | Normalizes to                                     |
	 * |--------------------------------------------|---------------------------------------------------|
	 * | `null` or `[]`                             | no dependencies                                   |
	 * | `'id'`                                     | `['id' => ['id' => 'id', 'required' => true]]`    |
	 * | `['a', 'b']`                               | both required                                     |
	 * | `['a' => true, 'b' => false]`              | values coerced via `TPropertyValue::ensureBoolean`|
	 * | `[['id' => 'a', 'required' => false]]`     | verbose form (`required` defaults to `true`)      |
	 * | any mix of the above                       | merged into a single normalized map               |
	 *
	 * Parameter order follows PHPUnit's `assertSame($expected, $actual)`
	 * convention.
	 *
	 * @param mixed  $expected expected dependency declaration in any valid form
	 * @param mixed  $actual   value returned by `getModuleDependencies()`
	 * @param string $message  optional failure message
	 * @throws \PHPUnit\Framework\AssertionFailedError on shape mismatch or
	 *   non-equivalent normalized maps.
	 * @throws \InvalidArgumentException when either argument is not
	 *   `null|string|array`.
	 * @since 4.4.0
	 */
	public static function assertModuleDependency(
		mixed $expected,
		mixed $actual,
		string $message = ''
	): void {
		// Dep maps are conceptually unordered sets; ksort both sides so
		// declaration order does not affect equality. The normalizer itself
		// preserves insertion order to faithfully mirror the framework.
		$expectedMap = self::normalizeModuleDependencyReturn($expected);
		$actualMap   = self::normalizeModuleDependencyReturn($actual);
		ksort($expectedMap);
		ksort($actualMap);
		\PHPUnit\Framework\Assert::assertSame($expectedMap, $actualMap, $message);
	}

	/**
	 * Normalizes any valid `getModuleDependencies()` return value into the
	 * `[id => ['id' => string, 'required' => bool]]` map produced by
	 * {@see \Prado\TApplication::collectModuleDependencies()}.
	 *
	 * Mirrors the framework's permissive parsing — entries with empty, null,
	 * or non-string IDs are silently skipped. Useful when a test needs to
	 * inspect the normalized form directly rather than compare it.
	 *
	 * @param mixed $value the raw return value from `getModuleDependencies()`,
	 *   or an expected declaration in any valid form.
	 * @return array<string, array{id: string, required: bool}> normalized
	 *   dependency map keyed by module ID.
	 * @throws \InvalidArgumentException when `$value` is not
	 *   `null|string|array`.
	 * @since 4.4.0
	 */
	public static function normalizeModuleDependencyReturn(mixed $value): array
	{
		if ($value !== null && !is_string($value) && !is_array($value)) {
			throw new \InvalidArgumentException(
				'getModuleDependencies() must return null|string|array, got ' . get_debug_type($value)
			);
		}
		$deps = [];
		foreach ((array) ($value ?? []) as $key => $dep) {
			$required = true;
			if (is_string($key) && $key !== '') {
				$required = TPropertyValue::ensureBoolean($dep);
				$dep = $key;
			} elseif (is_string($dep) && $dep !== '') {
				$key = $dep;
			} elseif (is_array($dep) && is_string($dep['id'] ?? null) && $dep['id'] !== '') {
				$required = TPropertyValue::ensureBoolean($dep['required'] ?? true);
				$key = $dep = $dep['id'];
			} else {
				continue; // no valid dep ID resolved — silently skip
			}
			$deps[$key] = ['id' => $dep, 'required' => $required];
		}
		return $deps;
	}
}
