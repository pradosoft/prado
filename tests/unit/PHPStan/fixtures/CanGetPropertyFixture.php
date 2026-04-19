<?php

/**
 * Fixture for TComponentCanGetPropertyTypeSpecifyingExtension tests.
 *
 * PHPStan should not report errors on this file when the extension is active.
 * When the extension is disabled, PHPStan cannot prove the getter exists inside
 * a canGetProperty() guard and will report "Call to an undefined method".
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * Exposes Title and Count as virtual properties.
 */
class CanGetPropertyFixtureComponent extends TComponent
{
	private string $_title = '';
	private int $_count = 0;

	public function getTitle(): string
	{
		return $this->_title;
	}

	public function setTitle(string $value): void
	{
		$this->_title = $value;
	}

	public function getCount(): int
	{
		return $this->_count;
	}
}

class CanGetPropertyCaller extends TComponent
{
	/**
	 * Guard on an external component's property getter.
	 */
	public function testCanGetPropertyGuard(CanGetPropertyFixtureComponent $component): void
	{
		if ($component->canGetProperty('Title')) {
			// With extension: PHPStan knows getTitle() exists
			$value = $component->getTitle();
			\assert(\is_string($value));
			
			$value = $component->title;
			\assert(\is_string($value));
		}
	}

	/**
	 * A read-only property: getter exists but no setter.
	 */
	public function testCanGetReadOnlyProperty(CanGetPropertyFixtureComponent $component): void
	{
		if ($component->canGetProperty('Count')) {
			$count = $component->getCount();
			\assert(\is_int($count));
			
			$count = $component->count;
			\assert(\is_int($count));
		}
	}

	/**
	 * Self-based guard pattern used inside TComponent subclasses.
	 */
	public function testCanGetPropertyOnSelf(): void
	{
		if ($this->canGetProperty('Title')) {
			$this->getTitle();
			$this->title;
		}
	}
}
