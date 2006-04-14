<?php

class TInlineParameterMapParser
{
	private $PARAMETER_TOKEN_REGEXP = '/#(#?[^#]+#?)#/';

	public function parseInlineParameterMap($sqlMap, $statement, $sqlText, $scope)
	{
		$parameterClass = !is_null($statement) 
							? $statement->getParameterClass() : null;
		$matches = array();
		$mappings = array();
		preg_match_all($this->PARAMETER_TOKEN_REGEXP, $sqlText, $matches);
		
		for($i = 0, $k=count($matches[1]); $i<$k; $i++)
		{
			$mappings[] = $this->parseMapping($matches[1][$i], 
									$parameterClass, $sqlMap, $scope);
			$sqlText = str_replace($matches[0][$i], '?', $sqlText);
		}
		return array('sql'=>$sqlText, 'parameters'=>$mappings);
	}

	/**
	 * Parse inline parameter with syntax as
	 * #propertyName,type=string,dbype=Varchar,nullValue=N/A,handler=string#
	 */
	protected function parseMapping($token, $parameterClass, $sqlMap, $scope)
	{
		$mapping = new TParameterProperty;
		$properties = explode(',', $token);
		$mapping->setProperty(trim(array_shift($properties)));
		//var_dump($properties);
		foreach($properties as $property)
		{
			$prop = explode('=',$property);
			$name = trim($prop[0]); $value=trim($prop[1]);
			if($mapping->canSetProperty($name))
				$mapping->{'set'.$name}($value);
			else
				throw new TSqlMapUndefinedException(
						'sqlmap_undefined_property_inline_map',
						$name, $scope['statement'], $scope['file']);
		}
		$mapping->initialize($sqlMap);
		return $mapping;
	}
}

?>