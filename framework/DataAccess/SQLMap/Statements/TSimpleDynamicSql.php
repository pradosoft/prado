<?php

class TSimpleDynamicSql extends TStaticSql
{
	private $_mappings=array();
	
	public function __construct($mappings)
	{
		$this->_mappings = $mappings;
	}

	public function getPreparedStatement($parameter=null)
	{
		$statement = parent::getPreparedStatement($parameter);
		if($parameter !== null)
			$this->mapDynamicParameter($statement, $parameter);
		return $statement;
	}
	
	protected function mapDynamicParameter($statement, $parameter)
	{
		$sql = $statement->getPreparedSql();
		foreach($this->_mappings as $property)
		{
			$value = TPropertyAccess::get($parameter, $property);
			$sql = preg_replace('/'.TSimpleDynamicParser::DYNAMIC_TOKEN.'/', $value, $sql, 1);
		}
		$statement->setPreparedSql($sql);
	}
}

?>