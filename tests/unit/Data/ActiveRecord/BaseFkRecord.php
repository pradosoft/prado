<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;

class BaseFkRecord extends TActiveRecord
{
	/**
	 * Returns the short class name as the table name; the namespaced class name is not a table.
	 * @return string the table name.
	 */
	public function table()
	{
		return strtolower((new \ReflectionClass($this))->getShortName());
	}

	use PradoUnitDataConnectionTrait;
	
	public function getDbConnection()
	{
		static $conn;
		if ($conn === null) {
			
			$conn = $this->setUpConnection('prado_unitest', true);
			//$this->OnExecuteCommand[] = array($this,'logger');
		}
		return $conn;
	}

	public function logger($sender, $param)
	{
	}
}
