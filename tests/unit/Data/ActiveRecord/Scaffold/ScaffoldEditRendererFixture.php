<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\Scaffold\IScaffoldEditRenderer;
use Prado\Web\UI\TControl;

/**
 * Edit renderer that writes {@see $itemName} into the record and counts its updates.
 */
class ScaffoldEditRendererFixture extends TControl implements IScaffoldEditRenderer
{
	public string $itemName = 'from renderer';
	public int $updates = 0;
	public int $dataSets = 0;
	private $_data;

	public function getData()
	{
		return $this->_data;
	}

	public function setData($value)
	{
		$this->_data = $value;
		$this->dataSets++;
	}

	public function updateRecord($record)
	{
		$record->item_name = $this->itemName;
		$this->updates++;
	}
}
