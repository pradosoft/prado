<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputCommon;

/**
 * Drives the TScaffoldInputCommon control factories directly. {@see $kind} names the
 * factory pair: `create<Kind>Control` builds the input and `get<Kind>Value` reads it,
 * falling back to getDefaultControlValue when the class has no reader for the kind.
 */
class CommonScaffoldInputHarness extends TScaffoldInputCommon
{
	public ?string $kind = null;

	/** @var mixed the value createControl returned */
	public $created;

	public function __construct(?string $kind = null)
	{
		$this->kind = $kind;
	}

	protected function createControl($container, $column, $record)
	{
		if ($this->kind === null) {
			return null;
		}
		return $this->created = $this->{'create' . $this->kind . 'Control'}($container, $column, $record);
	}

	protected function getControlValue($container, $column, $record)
	{
		$method = 'get' . $this->kind . 'Value';
		if ($this->kind !== null && method_exists($this, $method)) {
			return $this->$method($container, $column, $record);
		}
		return $this->getDefaultControlValue($container, $column, $record);
	}

	public function callGetRecordPropertyValue($column, $record)
	{
		return $this->getRecordPropertyValue($column, $record);
	}

	public function callSetRecordPropertyValue($item, $record, $input)
	{
		$this->setRecordPropertyValue($item, $record, $input);
	}

	public function callGetMatchingIndices($checks, $values)
	{
		return $this->getMatchingIndices($checks, $values);
	}
}
