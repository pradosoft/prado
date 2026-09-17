<?php

/**
 * PHPStanExtensionsTest
 *
 * Unit tests that verify every PHPStan extension wired into phpstan.neon.dist
 * actually changes PHPStan's output for the corresponding fixture file.
 *
 * Strategy
 * --------
 * Each extension is tested in two passes:
 *   1. WITHOUT the extension active – PHPStan must report at least one error on
 *      the fixture.  This confirms the fixture is a genuine test case and that
 *      the extension is actually doing work, not just silently passing.
 *   2. WITH the extension active (via the project's phpstan.neon.dist) – PHPStan
 *      must report zero errors on the same fixture.
 *
 * PHPStan is invoked as a subprocess so the tests remain independent of the
 * PHPStan phar's internal API and work with any compatible PHPStan version.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */

declare(strict_types=1);

namespace Prado\Test\Unit\PHPStan;

use PHPUnit\Framework\TestCase;

/**
 * @requires function exec
 */
class PHPStanExtensionsTest extends TestCase
{
	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/** Absolute path to the project root (one level above tests/). */
	private string $projectRoot;

	protected function setUp(): void
	{
		$this->projectRoot = dirname(__DIR__, 3);

		$phpstan = $this->projectRoot . '/vendor/bin/phpstan';
		if (!is_file($phpstan)) {
			$this->markTestSkipped('PHPStan binary not found at ' . $phpstan);
		}

		if (!function_exists('exec')) {
			$this->markTestSkipped('exec() function is not available.');
		}
	}

	/**
	 * Run PHPStan on a fixture file and return the JSON-decoded result.
	 *
	 * @param string $fixtureFile  Basename of a file under tests/unit/PHPStan/Fixtures/
	 * @param string|null $config  Path to a neon config (null → phpstan.neon.dist)
	 * @return array{totals: array{errors: int, file_errors: int}, files: array<string,mixed>}
	 */
	private function runPhpStan(string $fixtureFile, ?string $config = null): array
	{
		$fixture = __DIR__ . '/Fixtures/' . $fixtureFile;
		$phpstan = $this->projectRoot . '/vendor/bin/phpstan';
		$configArg = $config !== null
			? '--configuration=' . escapeshellarg($config)
			: '--configuration=' . escapeshellarg($this->projectRoot . '/phpstan.neon.dist');

		$cmd = escapeshellarg($phpstan)
			. ' analyse'
			. ' --no-progress'
			. ' --error-format=json'
			. ' ' . $configArg
			. ' ' . escapeshellarg($fixture)
			. ' 2>/dev/null';

		$output = [];
		exec($cmd, $output);
		$json = implode("\n", $output);
		$decoded = json_decode($json, true);

		if (!is_array($decoded)) {
			// PHPStan sometimes writes non-JSON lines first; strip them.
			foreach ($output as $line) {
				$candidate = json_decode($line, true);
				if (is_array($candidate)) {
					$decoded = $candidate;
					break;
				}
			}
		}

		return $decoded ?? ['totals' => ['errors' => 0, 'file_errors' => 0], 'files' => []];
	}

	/**
	 * Count errors reported for a given fixture file path inside the PHPStan JSON result.
	 *
	 * @param array<string,mixed> $result  Return value of runPhpStan()
	 * @param string $fixtureFile          Basename of the fixture file
	 */
	private function countFileErrors(array $result, string $fixtureFile): int
	{
		$fixturePath = realpath(__DIR__ . '/Fixtures/' . $fixtureFile) ?: (__DIR__ . '/Fixtures/' . $fixtureFile);
		foreach ($result['files'] ?? [] as $path => $data) {
			// PHPStan may use relative or absolute paths; match by realpath or basename.
			$realPath = realpath($path) ?: $path;
			if ($realPath === $fixturePath || basename($path) === $fixtureFile) {
				return (int) ($data['errors'] ?? 0);
			}
		}
		return 0;
	}

	/**
	 * Build a human-readable list of PHPStan errors for a fixture file.
	 *
	 * Appended to assertion failure messages so you can see exactly which
	 * errors remain rather than just a bare count.
	 *
	 * @param array<string,mixed> $result  Return value of runPhpStan()
	 * @param string $fixtureFile          Basename of the fixture file
	 */
	private function describeErrors(array $result, string $fixtureFile): string
	{
		$fixturePath = realpath(__DIR__ . '/Fixtures/' . $fixtureFile) ?: (__DIR__ . '/Fixtures/' . $fixtureFile);
		foreach ($result['files'] ?? [] as $path => $data) {
			$realPath = realpath($path) ?: $path;
			if ($realPath !== $fixturePath && basename($path) !== $fixtureFile) {
				continue;
			}
			$messages = $data['messages'] ?? [];
			if (empty($messages)) {
				return '';
			}
			$lines = ["\nPHPStan errors in {$fixtureFile}:"];
			foreach ($messages as $msg) {
				$line = $msg['line'] ?? '?';
				$text = $msg['message'] ?? '(no message)';
				$lines[] = "  line {$line}: {$text}";
			}
			return implode("\n", $lines);
		}
		return '';
	}

	/**
	 * Collect the PHPStan error messages reported for a fixture file.
	 *
	 * @param array<string,mixed> $result  Return value of runPhpStan()
	 * @param string $fixtureFile          Basename of the fixture file
	 * @return string[] The reported messages, in the order PHPStan reported them.
	 * @since 4.4.0
	 */
	private function fileMessages(array $result, string $fixtureFile): array
	{
		$fixturePath = realpath(__DIR__ . '/Fixtures/' . $fixtureFile) ?: (__DIR__ . '/Fixtures/' . $fixtureFile);
		foreach ($result['files'] ?? [] as $path => $data) {
			$realPath = realpath($path) ?: $path;
			if ($realPath !== $fixturePath && basename($path) !== $fixtureFile) {
				continue;
			}
			return array_map(
				static fn($message) => (string) ($message['message'] ?? ''),
				$data['messages'] ?? []
			);
		}
		return [];
	}

	/** Path to the "no extensions" config used for the negative pass. */
	private function noExtensionsConfig(): string
	{
		return __DIR__ . '/phpstan-no-extensions.neon';
	}

	// -------------------------------------------------------------------------
	// DynamicMethodsClassReflectionExtension
	// -------------------------------------------------------------------------

	/**
	 * Without the extension PHPStan reports "Call to an undefined method" for
	 * every dy* or fx* call on a TComponent subclass.
	 *
	 * @group phpstan
	 */
	public function testDynamicMethodsExtension_FailsWithoutExtension(): void
	{
		$result = $this->runPhpStan('DynamicMethodsFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'DynamicMethodsFixture.php');
		$this->assertGreaterThan(
			0,
			$errors,
			'Expected PHPStan to report errors for dy*/fx* calls without DynamicMethodsClassReflectionExtension.'
		);
	}

	/**
	 * With the extension active, all dy* or fx* calls are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testDynamicMethodsExtension_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('DynamicMethodsFixture.php');
		$errors = $this->countFileErrors($result, 'DynamicMethodsFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for dy*/fx* calls with DynamicMethodsClassReflectionExtension.'
				. $this->describeErrors($result, 'DynamicMethodsFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// TComponentHasMethodTypeSpecifyingExtension
	// -------------------------------------------------------------------------

	/**
	 * Without the extension, guarded method calls produce "undefined method" errors.
	 *
	 * @group phpstan
	 */
	public function testHasMethodExtension_FailsWithoutExtension(): void
	{
		$result = $this->runPhpStan('HasMethodFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'HasMethodFixture.php');
		$this->assertGreaterThan(
			0,
			$errors,
			'Expected PHPStan errors for method calls guarded by hasMethod() without the extension.'
		);
	}

	/**
	 * With the extension active, hasMethod() guards correctly narrow the type and
	 * all guarded method calls are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testHasMethodExtensionPassesWithExtension(): void
	{
		$result = $this->runPhpStan('HasMethodFixture.php');
		$errors = $this->countFileErrors($result, 'HasMethodFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for hasMethod()-guarded method calls with the extension.'
				. $this->describeErrors($result, 'HasMethodFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// PradoMethodVisibleStaticMethodTypeSpecifyingExtension
	// -------------------------------------------------------------------------

	/**
	 * Without the extension, Prado::method_visible() guards do not narrow the type.
	 *
	 * @group phpstan
	 */
	public function testMethodVisibleExtension_FailsWithoutExtension(): void
	{
		$result = $this->runPhpStan('MethodVisibleFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'MethodVisibleFixture.php');
		$this->assertGreaterThan(
			0,
			$errors,
			'Expected PHPStan errors for method calls guarded by Prado::method_visible() without the extension.'
		);
	}

	/**
	 * With the extension active, Prado::method_visible() guards narrow the type and
	 * all guarded method calls are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testMethodVisibleExtension_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('MethodVisibleFixture.php');
		$errors = $this->countFileErrors($result, 'MethodVisibleFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for Prado::method_visible()-guarded method calls with the extension.'
				. $this->describeErrors($result, 'MethodVisibleFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// TComponentIsaTypeSpecifyingExtension
	// -------------------------------------------------------------------------

	/**
	 * isa() narrowing is carried by the assertion tag on TComponent::isa(), which
	 * travels with the framework source.  The narrowing therefore applies with no
	 * PRADO services configured at all.
	 *
	 * @group phpstan
	 * @since 4.4.0
	 */
	public function testIsaExtension_NarrowsWithoutServicesConfig(): void
	{
		$result = $this->runPhpStan('IsaFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'IsaFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for isa()-guarded subclass calls without the services block.'
				. $this->describeErrors($result, 'IsaFixture.php')
		);
	}

	/**
	 * With the extension active, isa() guards narrow the type to the specified
	 * subclass and all guarded method calls are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testIsaExtension_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('IsaFixture.php');
		$errors = $this->countFileErrors($result, 'IsaFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for isa()-guarded subclass calls with the extension.'
				. $this->describeErrors($result, 'IsaFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// TComponentIsaTypeSpecifyingExtension — interface narrowing
	// -------------------------------------------------------------------------

	/**
	 * Interface narrowing comes from the same assertion tag, so it also applies
	 * with no PRADO services configured.
	 *
	 * This test isolates the interface case from the subclass case above.
	 *
	 * @group phpstan
	 * @since 4.4.0
	 */
	public function testIsaExtensionInterface_NarrowsWithoutServicesConfig(): void
	{
		$result = $this->runPhpStan('IsaInterfaceFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'IsaInterfaceFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for isa()-guarded interface-method calls without the services block.'
				. $this->describeErrors($result, 'IsaInterfaceFixture.php')
		);
	}

	/**
	 * With the extension active, isa() guards on interface class strings narrow
	 * the type to that interface and all guarded method calls are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testIsaExtensionInterface_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('IsaInterfaceFixture.php');
		$errors = $this->countFileErrors($result, 'IsaInterfaceFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for isa()-guarded interface-method calls with the extension.'
				. $this->describeErrors($result, 'IsaInterfaceFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// TComponent::isa() — intersection subjects
	// -------------------------------------------------------------------------

	/**
	 * PHPStan hands a method call to a MethodTypeSpecifyingExtension only when the
	 * subject has exactly one object class name, so an intersection subject such as
	 * `IService&TComponent` never reaches TComponentIsaTypeSpecifyingExtension.
	 * The assertion tag on TComponent::isa() narrows those subjects instead.
	 *
	 * @group phpstan
	 * @since 4.4.0
	 */
	public function testIsaIntersection_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('IsaIntersectionFixture.php');
		$errors = $this->countFileErrors($result, 'IsaIntersectionFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for isa()-guarded calls on intersection-typed subjects.'
				. $this->describeErrors($result, 'IsaIntersectionFixture.php')
		);
	}

	/**
	 * Intersection narrowing comes from the framework source, so it applies with no
	 * PRADO services configured.
	 *
	 * @group phpstan
	 * @since 4.4.0
	 */
	public function testIsaIntersection_NarrowsWithoutServicesConfig(): void
	{
		$result = $this->runPhpStan('IsaIntersectionFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'IsaIntersectionFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for isa()-guarded intersection calls without the services block.'
				. $this->describeErrors($result, 'IsaIntersectionFixture.php')
		);
	}

	/**
	 * Zero errors alone cannot tell a correct narrowing from a permissive one, so
	 * this fixture calls a method that exists nowhere.  PHPStan reports it against
	 * the class the guard narrowed to, which pins the narrowing to the asserted
	 * class.  Without the narrowing the same error names the intersection.
	 *
	 * @group phpstan
	 * @since 4.4.0
	 */
	public function testIsaIntersection_NarrowsToTheAssertedClass(): void
	{
		$result = $this->runPhpStan('IsaIntersectionNegativeFixture.php');
		$messages = $this->fileMessages($result, 'IsaIntersectionNegativeFixture.php');
		$this->assertCount(
			1,
			$messages,
			'Expected exactly one PHPStan error in IsaIntersectionNegativeFixture.php.'
				. $this->describeErrors($result, 'IsaIntersectionNegativeFixture.php')
		);
		$this->assertStringContainsString(
			'IsaNegativeFixtureComponent::thisMethodDoesNotExist()',
			$messages[0],
			'Expected the undefined-method error to name the class the isa() guard narrowed to.'
		);
	}

	// -------------------------------------------------------------------------
	// TComponentCanGetPropertyTypeSpecifyingExtension
	// -------------------------------------------------------------------------

	/**
	 * Without the extension, canGetProperty() guards do not narrow the type.
	 *
	 * @group phpstan
	 */
	public function testCanGetPropertyExtension_FailsWithoutExtension(): void
	{
		$result = $this->runPhpStan('CanGetPropertyFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'CanGetPropertyFixture.php');
		$this->assertGreaterThan(
			0,
			$errors,
			'Expected PHPStan errors for getter calls inside canGetProperty() guards without the extension.'
		);
	}

	/**
	 * With the extension active, canGetProperty() guards narrow the type so that
	 * getter calls inside the block are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testCanGetPropertyExtension_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('CanGetPropertyFixture.php');
		$errors = $this->countFileErrors($result, 'CanGetPropertyFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for canGetProperty()-guarded getter calls with the extension.'
				. $this->describeErrors($result, 'CanGetPropertyFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// TComponentCanSetPropertyTypeSpecifyingExtension
	// -------------------------------------------------------------------------

	/**
	 * Without the extension, canSetProperty() guards do not narrow the type.
	 *
	 * @group phpstan
	 */
	public function testCanSetPropertyExtension_FailsWithoutExtension(): void
	{
		$result = $this->runPhpStan('CanSetPropertyFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'CanSetPropertyFixture.php');
		$this->assertGreaterThan(
			0,
			$errors,
			'Expected PHPStan errors for setter calls inside canSetProperty() guards without the extension.'
		);
	}

	/**
	 * With the extension active, canSetProperty() guards narrow the type so that
	 * setter calls inside the block are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testCanSetPropertyExtension_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('CanSetPropertyFixture.php');
		$errors = $this->countFileErrors($result, 'CanSetPropertyFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for canSetProperty()-guarded setter calls with the extension.'
				. $this->describeErrors($result, 'CanSetPropertyFixture.php')
		);
	}

	// -------------------------------------------------------------------------
	// TComponentPropertiesReflectionExtension
	// -------------------------------------------------------------------------

	/**
	 * Without the extension, magic property access ($obj->PropName) produces
	 * "Access to an undefined property" errors.
	 *
	 * @group phpstan
	 */
	public function testPropertiesReflectionExtension_FailsWithoutExtension(): void
	{
		$result = $this->runPhpStan('PropertiesReflectionFixture.php', $this->noExtensionsConfig());
		$errors = $this->countFileErrors($result, 'PropertiesReflectionFixture.php');
		$this->assertGreaterThan(
			0,
			$errors,
			'Expected PHPStan errors for magic property access without TComponentPropertiesReflectionExtension.'
		);
	}

	/**
	 * With the extension active, PRADO virtual properties are resolved to their
	 * getter/setter methods and all property accesses are accepted without error.
	 *
	 * @group phpstan
	 */
	public function testPropertiesReflectionExtension_PassesWithExtension(): void
	{
		$result = $this->runPhpStan('PropertiesReflectionFixture.php');
		$errors = $this->countFileErrors($result, 'PropertiesReflectionFixture.php');
		$this->assertSame(
			0,
			$errors,
			'Expected zero PHPStan errors for virtual property access with TComponentPropertiesReflectionExtension.'
				. $this->describeErrors($result, 'PropertiesReflectionFixture.php')
		);
	}
}
