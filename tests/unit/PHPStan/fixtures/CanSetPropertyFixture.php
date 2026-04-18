<?php

/**
 * Fixture for TComponentCanSetPropertyTypeSpecifyingExtension tests.
 *
 * PHPStan should not report errors on this file when the extension is active.
 * When the extension is disabled, PHPStan cannot prove the setter exists inside
 * a canSetProperty() guard and will report "Call to an undefined method".
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * Exposes Label and Priority as virtual properties with setters.
 */
class CanSetPropertyFixtureComponent extends TComponent
{
	private string $_label = '';
	private int $_priority = 0;
	private int $_readOnly = 0;

	public function getLabel(): string
	{
		return $this->_label;
	}

	public function setLabel(string $value): void
	{
		$this->_label = $value;
	}

	public function getPriority(): int
	{
		return $this->_priority;
	}

	public function setPriority(int $value): void
	{
		$this->_priority = $value;
	}

	public function getReadOnly(): int
	{
		return $this->_readOnly;
	}
}

class CanSetPropertyCaller extends TComponent
{
	/**
	 * Guard on an external component's property setter.
	 */
	public function testCanSetPropertyGuard(CanSetPropertyFixtureComponent $component): void
	{
		if ($component->canSetProperty('Label')) {
			// With extension: PHPStan knows setLabel() exists
			$component->setLabel('hello');
			$component->label = 'hello';
		}
	}

	/**
	 * A second writable property.
	 */
	public function testCanSetPriorityGuard(CanSetPropertyFixtureComponent $component): void
	{
		if ($component->canSetProperty('Priority')) {
			$component->setPriority(10);
			$component->priority = 10;
		}
	}

	/**
	 * Self-based pattern.
	 */
	public function testCanSetPropertyOnSelf(): void
	{
		if ($this->canSetProperty('Label')) {
			$this->setLabel('world');
			$this->label = 'world';
		}
	}
}
