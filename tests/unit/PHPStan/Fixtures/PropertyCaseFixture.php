<?php

/**
 * Fixture for the property-name spelling handled by the guard extensions.
 *
 * PRADO virtual properties are written `Title` in templates and in code, while
 * `title` reaches the same accessor because PHP method names are case-insensitive.
 * PHPStan asks for a property with the spelling written in the source, so
 * TComponentCanGetPropertyTypeSpecifyingExtension,
 * TComponentCanSetPropertyTypeSpecifyingExtension and
 * TComponentHasMethodTypeSpecifyingExtension register both spellings.
 *
 * The subject is a bare TComponent, which declares no accessor of its own, so
 * TComponentPropertiesReflectionExtension cannot resolve these properties and the
 * guard extensions are the only thing that can.
 */

declare(strict_types=1);

namespace Prado\Test\Unit\PHPStan\Fixtures;

use Prado\TComponent;

class PropertyCaseCaller
{
	/**
	 * The PRADO spelling of a readable property.
	 */
	public function testCanGetPropertyPascalCase(TComponent $component): void
	{
		if ($component->canGetProperty('Title')) {
			$value = $component->Title;
		}
	}

	/**
	 * The lowercase-first spelling reaches the same accessor.
	 */
	public function testCanGetPropertyLowerFirst(TComponent $component): void
	{
		if ($component->canGetProperty('Title')) {
			$value = $component->title;
		}
	}

	/**
	 * The PRADO spelling of a writable property.
	 */
	public function testCanSetPropertyPascalCase(TComponent $component): void
	{
		if ($component->canSetProperty('Title')) {
			$component->Title = 'value';
		}
	}

	/**
	 * The lowercase-first spelling of a writable property.
	 */
	public function testCanSetPropertyLowerFirst(TComponent $component): void
	{
		if ($component->canSetProperty('Title')) {
			$component->title = 'value';
		}
	}

	/**
	 * hasMethod() on a get{Name} accessor narrows the virtual property as well.
	 */
	public function testHasMethodAccessorPascalCase(TComponent $component): void
	{
		if ($component->hasMethod('getTitle')) {
			$value = $component->Title;
		}
	}

	/**
	 * The getjs{Name} accessor variant narrows the same property.
	 */
	public function testHasMethodJsAccessorPascalCase(TComponent $component): void
	{
		if ($component->hasMethod('getjsTitle')) {
			$value = $component->Title;
		}
	}

	/**
	 * A single-word property name has one spelling, which must still narrow.
	 */
	public function testSingleCaseProperty(TComponent $component): void
	{
		if ($component->canGetProperty('title')) {
			$value = $component->title;
		}
	}
}
