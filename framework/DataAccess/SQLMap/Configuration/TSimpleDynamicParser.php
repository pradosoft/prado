<?php

class TSimpleDynamicParser
{
	private $PARAMETER_TOKEN_REGEXP = '/\$([^\$]+)\$/';
	
	const DYNAMIC_TOKEN = '`!`';
	
	public function parse($sqlMap, $statement, $sqlText, $scope)
	{
		$matches = array();
		$mappings = array();
		preg_match_all($this->PARAMETER_TOKEN_REGEXP, $sqlText, $matches);		
		for($i = 0, $k=count($matches[1]); $i<$k; $i++)
		{
			$mappings[] = $matches[1][$i];
			$sqlText = str_replace($matches[0][$i], self::DYNAMIC_TOKEN, $sqlText);
		}
		return array('sql'=>$sqlText, 'parameters'=>$mappings);
	}	
}

?>