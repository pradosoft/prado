<?php

class TConfigDeserialize
{
	private $_properties;

	public function __construct($properties)
	{
		$this->_properties = $properties;
	}

	public function loadConfiguration($object, $node, $file)
	{
		foreach($node->attributes() as $k=>$v)
		{
			if($object->canSetProperty($k))
				$object->{'set'.$k}($this->replaceProperties((string)($v)));
			else 
				throw new TUndefinedAttributeException($k,$node,$object,$file);
		}	
	}

	public function replaceProperties($string)
	{
		foreach($this->_properties as $find => $replace)
			$string = str_replace('${'.$find.'}', $replace, $string);
		return $string;
	}

	public function parameterMap($node, $sqlMap, $file)
	{
		$parameterMap = new TParameterMap;
		$this->loadConfiguration($parameterMap, $node, $file);
		foreach($node->parameter as $parameter)
		{
			$property = $this->parameterProperty($parameter, $sqlMap, $file);
			$property->initialize($sqlMap);
			$parameterMap->addParameterProperty($property);
		}
		return $parameterMap;
	}

	public function parameterProperty($node, $sqlMap, $file)
	{
		$property = new TParameterProperty;
		$this->loadConfiguration($property, $node, $file);
		return $property;
	}

	public function select($node, $sqlMap, $file)
	{
		$select = new TSqlMapSelect;
		$this->loadConfiguration($select, $node, $file);
		$select->initialize($sqlMap);
		return $select;
	}

	public function update($node, $sqlMap, $file)
	{
		$update = new TSqlMapUpdate;
		$this->loadConfiguration($update, $node, $file);
		$update->initialize($sqlMap);
		return $update;
	}

	public function delete($node, $sqlMap, $file)
	{
		$delete = new TSqlMapDelete;
		$this->loadConfiguration($delete, $node, $file);
		$delete->initialize($sqlMap);
		return $delete;
	}


	public function insert($node, $sqlMap, $file)
	{
		$insert = new TSqlMapInsert;
		$this->loadConfiguration($insert, $node, $file);
		if(isset($node->selectKey))
		{
			$selectKey = new TSqlMapSelectKey;
			$this->loadConfiguration($selectKey, $node->selectKey, $file);
			$type = $selectKey->getType();
			$selectKey->setType(strtolower($type) == 'post' ? 'post' : 'pre');
			$insert->setSelectKey($selectKey);
		}
		if(isset($node->generate))
		{
			var_dump("add generate");
		}

		$insert->initialize($sqlMap);
		return $insert;
	}
	
	public function statement($node, $sqlMap, $file)
	{
		$statement = new TSqlMapStatement;
		$this->loadConfiguration($statement, $node, $file);
		$statement->initialize($sqlMap);
		return $statement;
	}
	
	public function resultMap($node, $sqlMap, $file)
	{
		$resultMap = new TResultMap;
		$this->loadConfiguration($resultMap, $node, $file);
		foreach($node->result as $result)
		{
			$property = $this->resultProperty($result, $sqlMap, $file);
			$property->initialize($sqlMap, $resultMap);
			$resultMap->addResultProperty($property);
		}
		
		$discriminator = null;
		if(isset($node->discriminator))
		{
			$discriminator = new TDiscriminator;
			$this->loadConfiguration($discriminator, $node->discriminator, $file);
			$discriminator->initMapping($sqlMap, $resultMap);
		}
		
		
	
		foreach($node->subMap as $subMapNode)
		{
			if(isset($subMapNode['value']))
			{
				if(is_null($discriminator))
					throw new TSqlMapConfigurationException(
						'sqlmap_undefined_discriminator', $resultMap->getID(), $file);
		
						$subMap = new TSubMap;
						$this->loadConfiguration($subMap, $subMapNode, $file);
						$discriminator->add($subMap);
			}
		}
		if(!is_null($discriminator))
			$resultMap->setDiscriminator($discriminator);
		return $resultMap;
	}

	public function resultProperty($node, $sqlMap, $file)
	{
		$resultProperty = new TResultProperty;
		$this->loadConfiguration($resultProperty, $node, $file);
		return $resultProperty;
	}

	public function cacheModel($node, $sqlMap, $file)
	{
		$cacheModel = new TSqlMapCacheModel;
		$this->loadConfiguration($cacheModel, $node, $file);
/*		if(isset($node->flushInterval))
		{
			$interval = $node->flushInterval;
			$span = 0; //span in seconds
			if(isset($interval['hours']))
				$span += intval($interval['hours'])*60*60;
			if(isset($interval['minutes']))
				$span += intval($interval['minutes'])*60;
			if(isset($interval['seconds']))
				$span += intval($interval['seconds']);
			if($span > 0)
				$cacheModel->setFlushInterval($span);
		}*/
		if(isset($node->property))
		{
			foreach($node->property as $property)
				$cacheModel->addProperty((string)$property['name'],
						(string)$property['value']);
		}
		return $cacheModel;
	}
}


?>