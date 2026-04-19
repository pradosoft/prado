<?php

/**
 * Fixture for TComponentPropertiesReflectionExtension tests.
 *
 * PHPStan should not report errors on this file when the extension is active.
 * When the extension is disabled, PHPStan reports "Access to an undefined property"
 * for every magic $component->PropertyName access.
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * A component with typical PRADO virtual property pairs.
 */
class PropertiesFixtureComponent extends TComponent
{
	private string $_name = '';
	private int $_age = 0;
	private bool $_active = false;

	// Standard get/set pair → readable and writable property 'Name'
	public function getName(): string
	{
		return $this->_name;
	}

	public function setName(string $value): void
	{
		$this->_name = $value;
	}

	// Read-only virtual property 'Age' (getter only)
	public function getAge(): int
	{
		return $this->_age;
	}

	// Write-only virtual property 'Active' (setter only)
	public function setActive(bool $value): void
	{
		$this->_active = $value;
	}
}

class PropertiesReflectionCaller
{
	/**
	 * Read and write a standard get/set property via magic access.
	 * Without the extension PHPStan would flag both lines.
	 */
	public function testReadWriteProperty(PropertiesFixtureComponent $component): void
	{
		// Write
		$component->Name = 'Alice';
		// Read
		$name = $component->Name;
		\assert(\is_string($name));
	}

	/**
	 * Read a read-only virtual property.
	 */
	public function testReadOnlyProperty(PropertiesFixtureComponent $component): void
	{
		$age = $component->Age;
		\assert(\is_int($age));
	}

	/**
	 * Write a write-only virtual property.
	 */
	public function testWriteOnlyProperty(PropertiesFixtureComponent $component): void
	{
		$component->Active = true;
	}
}
