<?php

/**
 * TPropertyValueIntegrationCoercionTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\TApplicationConfiguration;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TTemplate;
use Prado\Xml\TXmlDocument;

// ════════════════════════════════════════════════════════════════════════
// Backed enum shared by all fixture classes
// ════════════════════════════════════════════════════════════════════════

enum TAppConfigTestColor: string
{
	case Red   = 'red';
	case Green = 'green';
	case Blue  = 'blue';
}

enum TAppConfigTestPriority: int
{
	case Low    = 1;
	case Medium = 2;
	case High   = 3;
}

// ════════════════════════════════════════════════════════════════════════
// Module fixtures
// ════════════════════════════════════════════════════════════════════════

/**
 * Module with a single typed setter per scalar type.
 *
 * Property matrix:
 * - single: bool, int, float, string, array, TAppConfigTestColor (BackedEnum)
 * - nullable: ?string, ?int, ?TAppConfigTestColor
 * - union: int|float
 */
class TAppConfigTestModule extends \Prado\TModule
{
	// single types
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?TAppConfigTestColor $_colorProp = null;

	// nullable types
	private ?string $_nullableStringProp = null;
	private ?int $_nullableIntProp = null;
	private ?TAppConfigTestColor $_nullableColorProp = null;

	// union types
	private int|float $_intOrFloat = 0;

	public function init($config): void {}

	// ── single ──────────────────────────────────────────────────────────
	public function getBoolProp(): bool { return $this->_boolProp; }
	public function setBoolProp(bool $v): void { $this->_boolProp = $v; }

	public function getIntProp(): int { return $this->_intProp; }
	public function setIntProp(int $v): void { $this->_intProp = $v; }

	public function getFloatProp(): float { return $this->_floatProp; }
	public function setFloatProp(float $v): void { $this->_floatProp = $v; }

	public function getStringProp(): string { return $this->_stringProp; }
	public function setStringProp(string $v): void { $this->_stringProp = $v; }

	public function getArrayProp(): array { return $this->_arrayProp; }
	public function setArrayProp(array $v): void { $this->_arrayProp = $v; }

	public function getColorProp(): ?TAppConfigTestColor { return $this->_colorProp; }
	public function setColorProp(TAppConfigTestColor $v): void { $this->_colorProp = $v; }

	// ── nullable ─────────────────────────────────────────────────────────
	public function getNullableStringProp(): ?string { return $this->_nullableStringProp; }
	public function setNullableStringProp(?string $v): void { $this->_nullableStringProp = $v; }

	public function getNullableIntProp(): ?int { return $this->_nullableIntProp; }
	public function setNullableIntProp(?int $v): void { $this->_nullableIntProp = $v; }

	public function getNullableColorProp(): ?TAppConfigTestColor { return $this->_nullableColorProp; }
	public function setNullableColorProp(?TAppConfigTestColor $v): void { $this->_nullableColorProp = $v; }

	// ── union ─────────────────────────────────────────────────────────
	public function getIntOrFloat(): int|float { return $this->_intOrFloat; }
	public function setIntOrFloat(int|float $v): void { $this->_intOrFloat = $v; }
}

// ════════════════════════════════════════════════════════════════════════
// Service fixture
// ════════════════════════════════════════════════════════════════════════

/**
 * Service with the same typed setter matrix as {@see TAppConfigTestModule}.
 */
class TAppConfigTestService extends \Prado\TService
{
	// single types
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?TAppConfigTestColor $_colorProp = null;

	// nullable types
	private ?string $_nullableStringProp = null;
	private ?int $_nullableIntProp = null;
	private ?TAppConfigTestColor $_nullableColorProp = null;

	// union types
	private int|float $_intOrFloat = 0;

	public function run(): void {}

	// ── single ──────────────────────────────────────────────────────────
	public function getBoolProp(): bool { return $this->_boolProp; }
	public function setBoolProp(bool $v): void { $this->_boolProp = $v; }

	public function getIntProp(): int { return $this->_intProp; }
	public function setIntProp(int $v): void { $this->_intProp = $v; }

	public function getFloatProp(): float { return $this->_floatProp; }
	public function setFloatProp(float $v): void { $this->_floatProp = $v; }

	public function getStringProp(): string { return $this->_stringProp; }
	public function setStringProp(string $v): void { $this->_stringProp = $v; }

	public function getArrayProp(): array { return $this->_arrayProp; }
	public function setArrayProp(array $v): void { $this->_arrayProp = $v; }

	public function getColorProp(): ?TAppConfigTestColor { return $this->_colorProp; }
	public function setColorProp(TAppConfigTestColor $v): void { $this->_colorProp = $v; }

	// ── nullable ─────────────────────────────────────────────────────────
	public function getNullableStringProp(): ?string { return $this->_nullableStringProp; }
	public function setNullableStringProp(?string $v): void { $this->_nullableStringProp = $v; }

	public function getNullableIntProp(): ?int { return $this->_nullableIntProp; }
	public function setNullableIntProp(?int $v): void { $this->_nullableIntProp = $v; }

	public function getNullableColorProp(): ?TAppConfigTestColor { return $this->_nullableColorProp; }
	public function setNullableColorProp(?TAppConfigTestColor $v): void { $this->_nullableColorProp = $v; }

	// ── union ─────────────────────────────────────────────────────────
	public function getIntOrFloat(): int|float { return $this->_intOrFloat; }
	public function setIntOrFloat(int|float $v): void { $this->_intOrFloat = $v; }
}

// ════════════════════════════════════════════════════════════════════════
// TControl fixture for TTemplate attribute tests
// ════════════════════════════════════════════════════════════════════════

/**
 * TControl subclass with the same typed setter matrix as the module and service
 * fixtures.  Used by TTemplate attribute coercion tests via
 * `<com:TAppConfigTestControl BoolProp="true" ... />`.
 */
class TAppConfigTestControl extends TControl
{
	// single types
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?TAppConfigTestColor $_colorProp = null;

	// nullable types
	private ?string $_nullableStringProp = null;
	private ?int $_nullableIntProp = null;
	private ?TAppConfigTestColor $_nullableColorProp = null;

	// union types
	private int|float $_intOrFloat = 0;

	// ── single ──────────────────────────────────────────────────────────
	public function getBoolProp(): bool { return $this->_boolProp; }
	public function setBoolProp(bool $v): void { $this->_boolProp = $v; }

	public function getIntProp(): int { return $this->_intProp; }
	public function setIntProp(int $v): void { $this->_intProp = $v; }

	public function getFloatProp(): float { return $this->_floatProp; }
	public function setFloatProp(float $v): void { $this->_floatProp = $v; }

	public function getStringProp(): string { return $this->_stringProp; }
	public function setStringProp(string $v): void { $this->_stringProp = $v; }

	public function getArrayProp(): array { return $this->_arrayProp; }
	public function setArrayProp(array $v): void { $this->_arrayProp = $v; }

	public function getColorProp(): ?TAppConfigTestColor { return $this->_colorProp; }
	public function setColorProp(TAppConfigTestColor $v): void { $this->_colorProp = $v; }

	// ── nullable ─────────────────────────────────────────────────────────
	public function getNullableStringProp(): ?string { return $this->_nullableStringProp; }
	public function setNullableStringProp(?string $v): void { $this->_nullableStringProp = $v; }

	public function getNullableIntProp(): ?int { return $this->_nullableIntProp; }
	public function setNullableIntProp(?int $v): void { $this->_nullableIntProp = $v; }

	public function getNullableColorProp(): ?TAppConfigTestColor { return $this->_nullableColorProp; }
	public function setNullableColorProp(?TAppConfigTestColor $v): void { $this->_nullableColorProp = $v; }

	// ── union ─────────────────────────────────────────────────────────
	public function getIntOrFloat(): int|float { return $this->_intOrFloat; }
	public function setIntOrFloat(int|float $v): void { $this->_intOrFloat = $v; }
}

// ════════════════════════════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════════════════════════════

/**
 * Helper: parse an XML application-configuration string and apply the module
 * properties to a freshly created module instance, exactly as
 * `TApplication::internalLoadModule()` does.
 *
 * Returns `[$module, $config]` so tests can inspect both.
 *
 * @param string $xml well-formed XML with a single `<module>` element.
 * @return array{0: TAppConfigTestModule, 1: TApplicationConfiguration}
 */
function applyXmlModuleConfig(string $xml): array
{
	$dom = new TXmlDocument();
	$dom->loadFromString($xml);
	$config = new TApplicationConfiguration();
	$config->loadFromXml($dom, sys_get_temp_dir());

	$modules = $config->getModules();
	assert(!empty($modules), 'No modules parsed from XML');
	[, [$type, $props]] = [key($modules), current($modules)];
	$module = Prado::createComponent($type);
	foreach ($props as $name => $value) {
		$module->setSubProperty($name, $value);
	}
	return [$module, $config];
}

/**
 * Helper: parse a PHP application-configuration array and apply the module
 * properties to a freshly created module instance.
 *
 * @param array $phpConfig PHP configuration array in the standard Prado format.
 * @return array{0: TAppConfigTestModule, 1: TApplicationConfiguration}
 */
function applyPhpModuleConfig(array $phpConfig): array
{
	$config = new TApplicationConfiguration();
	$config->loadFromPhp($phpConfig, sys_get_temp_dir());

	$modules = $config->getModules();
	[, [$type, $props]] = [key($modules), current($modules)];
	$module = Prado::createComponent($type);
	foreach ($props as $name => $value) {
		$module->setSubProperty($name, $value);
	}
	return [$module, $config];
}

/**
 * Helper: parse a PHP application-configuration array and apply the service
 * properties to a freshly created service instance, exactly as
 * `TApplication::startService()` does.
 *
 * @param array $phpConfig PHP configuration array.
 * @return array{0: TAppConfigTestService, 1: TApplicationConfiguration}
 */
function applyPhpServiceConfig(array $phpConfig): array
{
	$config = new TApplicationConfiguration();
	$config->loadFromPhp($phpConfig, sys_get_temp_dir());

	$services = $config->getServices();
	[, [$type, $props]] = [key($services), current($services)];
	$service = Prado::createComponent($type);
	foreach ($props as $name => $value) {
		$service->setSubProperty($name, $value);
	}
	return [$service, $config];
}

/**
 * Helper: parse an XML application-configuration string and apply the service
 * properties to a freshly created service instance.
 *
 * @param string $xml well-formed XML with a single `<service>` element.
 * @return array{0: TAppConfigTestService, 1: TApplicationConfiguration}
 */
function applyXmlServiceConfig(string $xml): array
{
	$dom = new TXmlDocument();
	$dom->loadFromString($xml);
	$config = new TApplicationConfiguration();
	$config->loadFromXml($dom, sys_get_temp_dir());

	$services = $config->getServices();
	[, [$type, $props]] = [key($services), current($services)];
	$service = Prado::createComponent($type);
	foreach ($props as $name => $value) {
		$service->setSubProperty($name, $value);
	}
	return [$service, $config];
}

/**
 * Helper: parse a TTemplate from a raw template string and instantiate it into
 * a plain TControl, returning the first child TAppConfigTestControl.
 *
 * This mirrors what TTemplateControl does during its page lifecycle:
 * `TTemplate::instantiateIn($tplControl)` calls `configureProperty()` →
 * `setSubProperty()` → `applyProperty()` for each template attribute.
 *
 * @param string $tpl raw template markup string containing exactly one
 *   `<com:TAppConfigTestControl .../>` tag.
 * @return TAppConfigTestControl the first child control with properties applied.
 */
function instantiateTemplateControl(string $tpl): TAppConfigTestControl
{
	$template = new TTemplate($tpl, sys_get_temp_dir(), null);
	$tplControl = new TControl();
	$template->instantiateIn($tplControl);
	/** @var TAppConfigTestControl $child */
	$child = $tplControl->getControls()->itemAt(0);
	return $child;
}

// ════════════════════════════════════════════════════════════════════════
// Test class
// ════════════════════════════════════════════════════════════════════════

/**
 * TPropertyValueIntegrationCoercionTest
 *
 * Integration tests that exercise property coercion through the full
 * `TApplicationConfiguration` + `TTemplate` pipeline.  Unlike the unit-level
 * tests in `TComponentCoercionIntegrationTest`, these tests go through the real
 * parsing and instantiation paths:
 *
 * - **XML config → module** — `TXmlDocument::loadFromString()` →
 *   `TApplicationConfiguration::loadFromXml()` → module `setSubProperty()` loop.
 * - **PHP config → module** — raw PHP array →
 *   `TApplicationConfiguration::loadFromPhp()` → module `setSubProperty()` loop.
 * - **XML config → service** — same XML path but for services.
 * - **PHP config → service** — same PHP path but for services.
 * - **TTestApplicationConfiguration injection** — pre-built config tuples
 *   injected directly without parsing, verifying the harness.
 * - **TTemplate attributes → TControl** — `TTemplate::instantiateIn()` →
 *   `configureProperty()` → `setSubProperty()` → `applyProperty()`.
 *
 * Each group covers the full typed parameter matrix:
 * - *single*: `bool`, `int`, `float`, `string`, `array`, `BackedEnum`
 * - *nullable*: `?string`, `?int`, `?BackedEnum`
 * - *union*: `int|float` with int-range, float, large-int overflow
 */
class TPropertyValueIntegrationCoercionTest extends PHPUnit\Framework\TestCase
{
	// ══════════════════════════════════════════════════════════════════════
	// XML config → Module
	// ══════════════════════════════════════════════════════════════════════

	public function testXmlModule_singleTypes_allParsedAndCoerced(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="testmod" class="TAppConfigTestModule"
						BoolProp="true"
						IntProp="42"
						FloatProp="1.5"
						StringProp="hello"
						ArrayProp="a, b, c"
						ColorProp="green"
					/>
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(true, $module->getBoolProp());
		self::assertSame(42, $module->getIntProp());
		self::assertSame(1.5, $module->getFloatProp());
		self::assertSame('hello', $module->getStringProp());
		self::assertSame(['a', 'b', 'c'], $module->getArrayProp());
		self::assertSame(TAppConfigTestColor::Green, $module->getColorProp());
	}

	public function testXmlModule_boolProp_falseString(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" BoolProp="false" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(false, $module->getBoolProp());
	}

	/**
	 * 'yes', 'no', 'on', 'off' are NOT truthy in PRADO's ensureBoolean —
	 * only 'true' (case-insensitive) and non-zero numeric strings are truthy.
	 */
	public function testXmlModule_boolProp_yesNoOnOff_allFalse(): void
	{
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$xml = <<<XML
				<?xml version="1.0" encoding="utf-8"?>
				<application>
					<modules>
						<module id="m" class="TAppConfigTestModule" BoolProp="$s" />
					</modules>
				</application>
				XML;

			[$module] = applyXmlModuleConfig($xml);
			self::assertSame(false, $module->getBoolProp(), "Expected false for BoolProp='$s'");
		}
	}

	public function testXmlModule_intProp_negativeString(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" IntProp="-7" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(-7, $module->getIntProp());
	}

	public function testXmlModule_floatProp_scientificNotation(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" FloatProp="1.5e3" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(1500.0, $module->getFloatProp());
	}

	public function testXmlModule_arrayProp_bracketSyntax(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" ArrayProp="[1, 2, 3]" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame([1, 2, 3], $module->getArrayProp());
	}

	public function testXmlModule_arrayProp_keyedSyntax(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" ArrayProp='("x" =&gt; 1, "y" =&gt; 2)' />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(['x' => 1, 'y' => 2], $module->getArrayProp());
	}

	public function testXmlModule_colorProp_fromBackingValue(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" ColorProp="red" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(TAppConfigTestColor::Red, $module->getColorProp());
	}

	public function testXmlModule_colorProp_fromCaseName(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" ColorProp="Blue" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(TAppConfigTestColor::Blue, $module->getColorProp());
	}

	// ── nullable via XML ─────────────────────────────────────────────────

	public function testXmlModule_nullable_emptyAttributeBecomesNull(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" NullableStringProp="" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertNull($module->getNullableStringProp());
	}

	public function testXmlModule_nullable_nonEmptyStringPreserved(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" NullableStringProp="set" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame('set', $module->getNullableStringProp());
	}

	public function testXmlModule_nullableInt_emptyAttributeBecomesNull(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" NullableIntProp="" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertNull($module->getNullableIntProp());
	}

	public function testXmlModule_nullableInt_numericStringBecomesInt(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" NullableIntProp="99" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(99, $module->getNullableIntProp());
	}

	public function testXmlModule_nullableColor_emptyAttributeBecomesNull(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" NullableColorProp="" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertNull($module->getNullableColorProp());
	}

	public function testXmlModule_nullableColor_backingValueBecomesEnum(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" NullableColorProp="blue" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(TAppConfigTestColor::Blue, $module->getNullableColorProp());
	}

	// ── union via XML ────────────────────────────────────────────────────

	public function testXmlModule_union_intString_picksInt(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" IntOrFloat="7" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(7, $module->getIntOrFloat());
	}

	public function testXmlModule_union_floatString_picksFloat(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" IntOrFloat="7.5" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertSame(7.5, $module->getIntOrFloat());
	}

	public function testXmlModule_union_overflowInt_promotesToFloat(): void
	{
		$overMax = bcadd((string) PHP_INT_MAX, '1');
		$xml = <<<XML
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<modules>
					<module id="m" class="TAppConfigTestModule" IntOrFloat="$overMax" />
				</modules>
			</application>
			XML;

		[$module] = applyXmlModuleConfig($xml);
		self::assertIsFloat($module->getIntOrFloat());
		self::assertSame((float) $overMax, $module->getIntOrFloat());
	}

	// ══════════════════════════════════════════════════════════════════════
	// PHP config → Module
	// ══════════════════════════════════════════════════════════════════════

	public function testPhpModule_singleTypes_allParsedAndCoerced(): void
	{
		$phpConfig = [
			'modules' => [
				'testmod' => [
					'class'      => 'TAppConfigTestModule',
					'properties' => [
						'BoolProp'   => 'true',
						'IntProp'    => '42',
						'FloatProp'  => '1.5',
						'StringProp' => 'hello',
						'ArrayProp'  => 'a, b, c',
						'ColorProp'  => 'green',
					],
				],
			],
		];

		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(true, $module->getBoolProp());
		self::assertSame(42, $module->getIntProp());
		self::assertSame(1.5, $module->getFloatProp());
		self::assertSame('hello', $module->getStringProp());
		self::assertSame(['a', 'b', 'c'], $module->getArrayProp());
		self::assertSame(TAppConfigTestColor::Green, $module->getColorProp());
	}

	/**
	 * PHP configs may carry already-typed values (booleans, arrays) rather than
	 * strings, since PHP arrays are not limited to string values.  Coercion must
	 * handle both PHP-typed source values and string source values uniformly.
	 */
	public function testPhpModule_nativePhpTypedValues_passThroughCoercion(): void
	{
		$phpConfig = [
			'modules' => [
				'testmod' => [
					'class'      => 'TAppConfigTestModule',
					'properties' => [
						'BoolProp'  => true,           // native PHP bool
						'IntProp'   => 99,             // native PHP int
						'FloatProp' => 2.718,          // native PHP float
						'ArrayProp' => ['x', 'y'],    // native PHP array
						'ColorProp' => TAppConfigTestColor::Blue, // enum instance
					],
				],
			],
		];

		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(true,  $module->getBoolProp());
		self::assertSame(99,   $module->getIntProp());
		self::assertSame(2.718, $module->getFloatProp());
		self::assertSame(['x', 'y'], $module->getArrayProp());
		self::assertSame(TAppConfigTestColor::Blue, $module->getColorProp());
	}

	public function testPhpModule_boolProp_yesNoOnOff_allFalse(): void
	{
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$phpConfig = [
				'modules' => [
					'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['BoolProp' => $s]],
				],
			];
			[$module] = applyPhpModuleConfig($phpConfig);
			self::assertSame(false, $module->getBoolProp(), "Expected false for '$s'");
		}
	}

	// ── nullable via PHP ─────────────────────────────────────────────────

	public function testPhpModule_nullable_emptyStringBecomesNull(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['NullableStringProp' => '']],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertNull($module->getNullableStringProp());
	}

	public function testPhpModule_nullable_nativeNull_staysNull(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['NullableStringProp' => null]],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertNull($module->getNullableStringProp());
	}

	public function testPhpModule_nullableInt_emptyStringBecomesNull(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['NullableIntProp' => '']],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertNull($module->getNullableIntProp());
	}

	public function testPhpModule_nullableInt_numericStringBecomesInt(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['NullableIntProp' => '55']],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(55, $module->getNullableIntProp());
	}

	public function testPhpModule_nullableColor_emptyStringBecomesNull(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['NullableColorProp' => '']],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertNull($module->getNullableColorProp());
	}

	// ── union via PHP ────────────────────────────────────────────────────

	public function testPhpModule_union_intString_picksInt(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['IntOrFloat' => '5']],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(5, $module->getIntOrFloat());
	}

	public function testPhpModule_union_floatString_picksFloat(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['IntOrFloat' => '5.5']],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(5.5, $module->getIntOrFloat());
	}

	public function testPhpModule_union_nativeInt_passthrough(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['IntOrFloat' => 10]],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(10, $module->getIntOrFloat());
	}

	public function testPhpModule_union_nativeFloat_passthrough(): void
	{
		$phpConfig = [
			'modules' => [
				'm' => ['class' => 'TAppConfigTestModule', 'properties' => ['IntOrFloat' => 10.5]],
			],
		];
		[$module] = applyPhpModuleConfig($phpConfig);
		self::assertSame(10.5, $module->getIntOrFloat());
	}

	// ══════════════════════════════════════════════════════════════════════
	// XML config → Service
	// ══════════════════════════════════════════════════════════════════════

	public function testXmlService_singleTypes_allParsedAndCoerced(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<services>
					<service id="testsvc" class="TAppConfigTestService"
						BoolProp="true"
						IntProp="10"
						FloatProp="3.14"
						StringProp="world"
						ArrayProp="x, y, z"
						ColorProp="red"
					/>
				</services>
			</application>
			XML;

		[$service] = applyXmlServiceConfig($xml);
		self::assertSame(true, $service->getBoolProp());
		self::assertSame(10, $service->getIntProp());
		self::assertSame(3.14, $service->getFloatProp());
		self::assertSame('world', $service->getStringProp());
		self::assertSame(['x', 'y', 'z'], $service->getArrayProp());
		self::assertSame(TAppConfigTestColor::Red, $service->getColorProp());
	}

	public function testXmlService_boolProp_yesNoOnOff_allFalse(): void
	{
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$xml = <<<XML
				<?xml version="1.0" encoding="utf-8"?>
				<application>
					<services>
						<service id="s" class="TAppConfigTestService" BoolProp="$s" />
					</services>
				</application>
				XML;

			[$service] = applyXmlServiceConfig($xml);
			self::assertSame(false, $service->getBoolProp(), "Expected false for BoolProp='$s'");
		}
	}

	public function testXmlService_nullable_emptyAttributeBecomesNull(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<services>
					<service id="s" class="TAppConfigTestService" NullableStringProp="" />
				</services>
			</application>
			XML;

		[$service] = applyXmlServiceConfig($xml);
		self::assertNull($service->getNullableStringProp());
	}

	public function testXmlService_nullableInt_numericStringBecomesInt(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<services>
					<service id="s" class="TAppConfigTestService" NullableIntProp="77" />
				</services>
			</application>
			XML;

		[$service] = applyXmlServiceConfig($xml);
		self::assertSame(77, $service->getNullableIntProp());
	}

	public function testXmlService_union_intString_picksInt(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<services>
					<service id="s" class="TAppConfigTestService" IntOrFloat="4" />
				</services>
			</application>
			XML;

		[$service] = applyXmlServiceConfig($xml);
		self::assertSame(4, $service->getIntOrFloat());
	}

	public function testXmlService_union_floatString_picksFloat(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<services>
					<service id="s" class="TAppConfigTestService" IntOrFloat="4.5" />
				</services>
			</application>
			XML;

		[$service] = applyXmlServiceConfig($xml);
		self::assertSame(4.5, $service->getIntOrFloat());
	}

	public function testXmlService_union_overflowInt_promotesToFloat(): void
	{
		$overMax = bcadd((string) PHP_INT_MAX, '1');
		$xml = <<<XML
			<?xml version="1.0" encoding="utf-8"?>
			<application>
				<services>
					<service id="s" class="TAppConfigTestService" IntOrFloat="$overMax" />
				</services>
			</application>
			XML;

		[$service] = applyXmlServiceConfig($xml);
		self::assertIsFloat($service->getIntOrFloat());
	}

	// ══════════════════════════════════════════════════════════════════════
	// PHP config → Service
	// ══════════════════════════════════════════════════════════════════════

	public function testPhpService_singleTypes_allParsedAndCoerced(): void
	{
		$phpConfig = [
			'services' => [
				'testsvc' => [
					'class'      => 'TAppConfigTestService',
					'properties' => [
						'BoolProp'   => 'false',
						'IntProp'    => '3',
						'FloatProp'  => '2.5',
						'StringProp' => 'test',
						'ArrayProp'  => '[10, 20, 30]',
						'ColorProp'  => 'blue',
					],
				],
			],
		];

		[$service] = applyPhpServiceConfig($phpConfig);
		self::assertSame(false, $service->getBoolProp());
		self::assertSame(3, $service->getIntProp());
		self::assertSame(2.5, $service->getFloatProp());
		self::assertSame('test', $service->getStringProp());
		self::assertSame([10, 20, 30], $service->getArrayProp());
		self::assertSame(TAppConfigTestColor::Blue, $service->getColorProp());
	}

	public function testPhpService_nativePhpTypedValues(): void
	{
		$phpConfig = [
			'services' => [
				'testsvc' => [
					'class'      => 'TAppConfigTestService',
					'properties' => [
						'BoolProp'  => false,
						'IntProp'   => 3,
						'FloatProp' => 2.5,
						'ArrayProp' => [10, 20, 30],
					],
				],
			],
		];

		[$service] = applyPhpServiceConfig($phpConfig);
		self::assertSame(false, $service->getBoolProp());
		self::assertSame(3,    $service->getIntProp());
		self::assertSame(2.5,  $service->getFloatProp());
		self::assertSame([10, 20, 30], $service->getArrayProp());
	}

	public function testPhpService_nullable_emptyStringBecomesNull(): void
	{
		$phpConfig = [
			'services' => [
				's' => ['class' => 'TAppConfigTestService', 'properties' => ['NullableStringProp' => '']],
			],
		];
		[$service] = applyPhpServiceConfig($phpConfig);
		self::assertNull($service->getNullableStringProp());
	}

	public function testPhpService_nullableInt_numericString(): void
	{
		$phpConfig = [
			'services' => [
				's' => ['class' => 'TAppConfigTestService', 'properties' => ['NullableIntProp' => '11']],
			],
		];
		[$service] = applyPhpServiceConfig($phpConfig);
		self::assertSame(11, $service->getNullableIntProp());
	}

	public function testPhpService_union_intString_picksInt(): void
	{
		$phpConfig = [
			'services' => [
				's' => ['class' => 'TAppConfigTestService', 'properties' => ['IntOrFloat' => '9']],
			],
		];
		[$service] = applyPhpServiceConfig($phpConfig);
		self::assertSame(9, $service->getIntOrFloat());
	}

	public function testPhpService_union_floatString_picksFloat(): void
	{
		$phpConfig = [
			'services' => [
				's' => ['class' => 'TAppConfigTestService', 'properties' => ['IntOrFloat' => '9.9']],
			],
		];
		[$service] = applyPhpServiceConfig($phpConfig);
		self::assertSame(9.9, $service->getIntOrFloat());
	}

	// ══════════════════════════════════════════════════════════════════════
	// TTestApplicationConfiguration — injection harness
	// ══════════════════════════════════════════════════════════════════════

	public function testHarness_addModuleConfig_appliesViaNormalLoop(): void
	{
		$config = new TTestApplicationConfiguration();
		$config->addModuleConfig('m', 'TAppConfigTestModule', [
			'BoolProp'   => 'true',
			'IntProp'    => '20',
			'FloatProp'  => '0.5',
			'StringProp' => 'injected',
			'ColorProp'  => 'green',
		]);

		$modules = $config->getModules();
		self::assertArrayHasKey('m', $modules);
		[$type, $props] = $modules['m'];
		$module = Prado::createComponent($type);
		foreach ($props as $name => $value) {
			$module->setSubProperty($name, $value);
		}

		self::assertSame(true,       $module->getBoolProp());
		self::assertSame(20,         $module->getIntProp());
		self::assertSame(0.5,        $module->getFloatProp());
		self::assertSame('injected', $module->getStringProp());
		self::assertSame(TAppConfigTestColor::Green, $module->getColorProp());
	}

	public function testHarness_addServiceConfig_appliesViaNormalLoop(): void
	{
		$config = new TTestApplicationConfiguration();
		$config->addServiceConfig('svc', 'TAppConfigTestService', [
			'BoolProp'  => 'false',
			'IntProp'   => '7',
			'ColorProp' => 'red',
		]);

		$services = $config->getServices();
		self::assertArrayHasKey('svc', $services);
		[$type, $props] = $services['svc'];
		$service = Prado::createComponent($type);
		foreach ($props as $name => $value) {
			$service->setSubProperty($name, $value);
		}

		self::assertSame(false, $service->getBoolProp());
		self::assertSame(7,     $service->getIntProp());
		self::assertSame(TAppConfigTestColor::Red, $service->getColorProp());
	}

	public function testHarness_setModuleConfigs_replacesEntireMap(): void
	{
		$config = new TTestApplicationConfiguration();
		$config->addModuleConfig('old', 'TAppConfigTestModule', ['IntProp' => '1']);
		$config->setModuleConfigs([
			'new' => ['TAppConfigTestModule', ['IntProp' => '99'], null],
		]);

		$modules = $config->getModules();
		self::assertArrayNotHasKey('old', $modules);
		self::assertArrayHasKey('new', $modules);
	}

	public function testHarness_nullable_injectedNull_staysNull(): void
	{
		$config = new TTestApplicationConfiguration();
		$config->addModuleConfig('m', 'TAppConfigTestModule', [
			'NullableStringProp' => null,
			'NullableIntProp'    => null,
		]);

		[$type, $props] = $config->getModules()['m'];
		$module = Prado::createComponent($type);
		foreach ($props as $name => $value) {
			$module->setSubProperty($name, $value);
		}

		self::assertNull($module->getNullableStringProp());
		self::assertNull($module->getNullableIntProp());
	}

	// ══════════════════════════════════════════════════════════════════════
	// TTemplate attributes → TControl
	//
	// TTemplate::instantiateIn() → configureProperty() → setSubProperty() →
	// applyProperty().  All attribute values are raw strings from the markup.
	// ══════════════════════════════════════════════════════════════════════

	public function testTemplate_control_singleTypes_allAttributesCoerced(): void
	{
		$tpl = '<com:TAppConfigTestControl'
			. ' BoolProp="true"'
			. ' IntProp="42"'
			. ' FloatProp="1.5"'
			. ' StringProp="template"'
			. ' ArrayProp="p, q, r"'
			. ' ColorProp="green"'
			. ' />';

		$child = instantiateTemplateControl($tpl);
		self::assertInstanceOf(TAppConfigTestControl::class, $child);
		self::assertSame(true,       $child->getBoolProp());
		self::assertSame(42,         $child->getIntProp());
		self::assertSame(1.5,        $child->getFloatProp());
		self::assertSame('template', $child->getStringProp());
		self::assertSame(['p', 'q', 'r'], $child->getArrayProp());
		self::assertSame(TAppConfigTestColor::Green, $child->getColorProp());
	}

	public function testTemplate_control_boolProp_falseString(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl BoolProp="false" />');
		self::assertSame(false, $child->getBoolProp());
	}

	public function testTemplate_control_boolProp_yesNoOnOff_allFalse(): void
	{
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$child = instantiateTemplateControl('<com:TAppConfigTestControl BoolProp="' . $s . '" />');
			self::assertSame(false, $child->getBoolProp(), "Expected false for BoolProp='$s'");
		}
	}

	public function testTemplate_control_intProp_negativeString(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl IntProp="-5" />');
		self::assertSame(-5, $child->getIntProp());
	}

	public function testTemplate_control_floatProp_scientificNotation(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl FloatProp="2e3" />');
		self::assertSame(2000.0, $child->getFloatProp());
	}

	public function testTemplate_control_arrayProp_bracketSyntax(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl ArrayProp="[10, 20, 30]" />');
		self::assertSame([10, 20, 30], $child->getArrayProp());
	}

	public function testTemplate_control_arrayProp_bareWordList(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl ArrayProp="a, b, c" />');
		self::assertSame(['a', 'b', 'c'], $child->getArrayProp());
	}

	public function testTemplate_control_colorProp_fromBackingValue(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl ColorProp="blue" />');
		self::assertSame(TAppConfigTestColor::Blue, $child->getColorProp());
	}

	public function testTemplate_control_colorProp_fromCaseName(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl ColorProp="Red" />');
		self::assertSame(TAppConfigTestColor::Red, $child->getColorProp());
	}

	public function testTemplate_control_colorProp_allCasesRoundtrip(): void
	{
		foreach (TAppConfigTestColor::cases() as $case) {
			// Via backing value
			$child = instantiateTemplateControl('<com:TAppConfigTestControl ColorProp="' . $case->value . '" />');
			self::assertSame($case, $child->getColorProp());

			// Via case name
			$child = instantiateTemplateControl('<com:TAppConfigTestControl ColorProp="' . $case->name . '" />');
			self::assertSame($case, $child->getColorProp());
		}
	}

	// ── nullable via template ────────────────────────────────────────────

	public function testTemplate_control_nullable_emptyAttributeBecomesNull(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl NullableStringProp="" />');
		self::assertNull($child->getNullableStringProp());
	}

	public function testTemplate_control_nullable_nonEmptyPreserved(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl NullableStringProp="set" />');
		self::assertSame('set', $child->getNullableStringProp());
	}

	public function testTemplate_control_nullableInt_emptyAttributeBecomesNull(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl NullableIntProp="" />');
		self::assertNull($child->getNullableIntProp());
	}

	public function testTemplate_control_nullableInt_numericStringBecomesInt(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl NullableIntProp="33" />');
		self::assertSame(33, $child->getNullableIntProp());
	}

	public function testTemplate_control_nullableColor_emptyAttributeBecomesNull(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl NullableColorProp="" />');
		self::assertNull($child->getNullableColorProp());
	}

	public function testTemplate_control_nullableColor_backingValue(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl NullableColorProp="red" />');
		self::assertSame(TAppConfigTestColor::Red, $child->getNullableColorProp());
	}

	// ── union via template ────────────────────────────────────────────────

	public function testTemplate_control_union_intString_picksInt(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl IntOrFloat="6" />');
		self::assertSame(6, $child->getIntOrFloat());
	}

	public function testTemplate_control_union_floatString_picksFloat(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl IntOrFloat="6.5" />');
		self::assertSame(6.5, $child->getIntOrFloat());
	}

	public function testTemplate_control_union_scientificNotation_picksFloat(): void
	{
		$child = instantiateTemplateControl('<com:TAppConfigTestControl IntOrFloat="1.5e2" />');
		self::assertSame(150.0, $child->getIntOrFloat());
	}

	public function testTemplate_control_union_overflowInt_promotesToFloat(): void
	{
		$overMax = bcadd((string) PHP_INT_MAX, '1');
		$child   = instantiateTemplateControl('<com:TAppConfigTestControl IntOrFloat="' . $overMax . '" />');
		self::assertIsFloat($child->getIntOrFloat());
		self::assertSame((float) $overMax, $child->getIntOrFloat());
	}

	// ── multiple attributes in one template tag ───────────────────────────

	public function testTemplate_control_multipleAttributes_allCoercedTogether(): void
	{
		$tpl = '<com:TAppConfigTestControl'
			. ' BoolProp="false"'
			. ' IntProp="-3"'
			. ' FloatProp="0.001"'
			. ' StringProp="multi"'
			. ' ColorProp="red"'
			. ' NullableStringProp=""'
			. ' NullableIntProp="7"'
			. ' IntOrFloat="2.5"'
			. ' />';

		$child = instantiateTemplateControl($tpl);
		self::assertSame(false,   $child->getBoolProp());
		self::assertSame(-3,      $child->getIntProp());
		self::assertSame(0.001,   $child->getFloatProp());
		self::assertSame('multi', $child->getStringProp());
		self::assertSame(TAppConfigTestColor::Red, $child->getColorProp());
		self::assertNull($child->getNullableStringProp());
		self::assertSame(7,   $child->getNullableIntProp());
		self::assertSame(2.5, $child->getIntOrFloat());
	}
}
