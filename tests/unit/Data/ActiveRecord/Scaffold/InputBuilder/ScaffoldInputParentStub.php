<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

/**
 * Stands in for the TScaffoldEditView that an input builder receives as its parent.
 * The builders read only RecordPk and ValidationGroup from it.
 */
class ScaffoldInputParentStub
{
	public function __construct(private ?array $_recordPk = null, private string $_validationGroup = 'group_test')
	{
	}

	public function getRecordPk()
	{
		return $this->_recordPk;
	}

	public function getValidationGroup()
	{
		return $this->_validationGroup;
	}
}
