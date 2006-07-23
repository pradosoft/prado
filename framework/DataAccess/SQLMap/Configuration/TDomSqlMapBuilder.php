<?php

class TDomSqlMapBuilder
{
	const DEFAULT_CONFIG_FILE = 'sqlmap.xml';

	private $_document;

	private $_sqlMapper;

	private $_configFile;

	private $_properties;

	private $_deserialize;

	private $_useNamespaces = false;

	private $_FlushOnExecuteStatements=array();

	public function __construct($cachedir='./cache')
	{
		$this->_properties = new TMap;
		$this->_deserialize = new TConfigDeserialize($this->_properties);
	}

	public function configure($resource=null)
	{
		if($resource instanceof SimpleXMLElement)
			return $this->build($resource);
		
		if(!is_string($resource))
			$resource = self::DEFAULT_CONFIG_FILE;
		
		$this->_configFile = $resource;
		if(!is_file($resource))
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_config', $resource);

		return $this->build($this->getConfigAsXmlDocument($resource));
	}

	protected function getConfigAsXmlDocument($file)
	{
		return simplexml_load_file($file);
	}

	public function build(SimpleXMLElement $document)
	{
		$this->_document = $document;
		$this->initialize($document);
		return $this->_sqlMapper;
	}

	protected function initialize($document)
	{
		$this->_sqlMapper = new TSqlMapper(new TTypeHandlerFactory);

		if(isset($document->properties))
		{
			$this->loadGlobalProperties($document->properties);
		}
		
		if(isset($document->settings) && isset($document->settings->setting))
			$this->configureSettings($document->settings);
			
		foreach($document->xpath('//typeHandler') as $handler)	
			$this->loadTypeHandler($handler, $this->_configFile);			

		//load database provider
		if(isset($document->provider) && isset($document->provider->datasource))
		{
			$this->loadProvider($document->provider, 
				$document->provider->datasource, $document, $this->_configFile);
		}
		else
		{
			throw new TSqlMapConfigurationException(
					'sqlmap_unable_to_find_db_config', $this->_configFile);
		}

		foreach($document->xpath('//sqlMap') as $sqlmap)
			$this->loadSqlMappingFiles($sqlmap);

		if($this->_sqlMapper->getIsCacheModelsEnabled())
			$this->attachCacheModel();
	}

	protected function configureSettings($node)
	{
		foreach($node->setting as $setting)
		{
			if(isset($setting['useStatementNamespaces']))
			{
				$this->_useNamespaces = 
					TPropertyValue::ensureBoolean(
						(string)$setting['useStatementNamespaces']);
			}

			if(isset($setting['cacheModelsEnabled']))
			{
				$this->_sqlMapper->setCacheModelsEnabled(
					TPropertyValue::ensureBoolean(
						(string)$setting['cacheModelsEnabled']));
			}
		}
	}

	/**
	 * Attach CacheModel to statement and register trigger statements for 
	 * cache models
	 */
	protected function attachCacheModel()
	{
		foreach($this->_sqlMapper->getStatements() as $mappedStatement)
		{
			if(strlen($model = $mappedStatement->getStatement()->getCacheModel()) > 0)
			{
				$cache = $this->_sqlMapper->getCache($model); 
				//var_dump($model);
				$mappedStatement->getStatement()->setCache($cache);
			}
		}	

		foreach($this->_FlushOnExecuteStatements as $cacheID => $statementIDs)
		{
			if(count($statementIDs) > 0)
			{
				foreach($statementIDs as $statementID)
				{
					$cacheModel = $this->_sqlMapper->getCache($cacheID);
					$statement = $this->_sqlMapper->getMappedStatement($statementID);
					$cacheModel->registerTriggerStatement($statement);
				}
			}
		}
	}

	protected function loadGlobalProperties($node)
	{
		if(isset($node['resource']))
			$this->loadPropertyResource($node);

		foreach($node->children() as $child)
		{
			if(isset($child['resource']))
				$this->loadPropertyResource($child);
			$this->_properties[(string)$child['key']] = (string)$child['value'];
		}
	}

	protected function loadPropertyResource($node)
	{
		$resource = $this->getResourceFromPath((string)$node['resource']);
		$properties = $this->getConfigAsXmlDocument($resource);
		$this->loadGlobalProperties($properties);
	}

	protected function loadProvider($providerNode, $node, $document, $file)
	{
		//$id = (string)$node['id'];
		$class = (string)$providerNode['class'];
		if(strlen($class) > 0)
		{
			if(class_exists($class,false))
			{
				$provider = new $class;
				$this->_deserialize->loadConfiguration($provider, $node,$file);
				$this->_sqlMapper->setDataProvider($provider);
			}
			else
			{
				throw new TSqlMapConfigurationException(
						'sqlmap_unable_find_provider_class_def', $file, $class);
			}
		}
		else
		{
			throw new TSqlMapConfigurationException(
						'sqlmap_unable_find_provider_class', $file);
		}
		//var_dump($node);
	}

	protected function loadTypeHandler($node, $file)
	{
		if(!is_null($node['type']) && !is_null($node['callback']))
		{
			$type = (string)$node['type'];
			$dbType = (string)$node['dbType'];
			$class = (string)$node['callback'];
			if(class_exists('Prado', false))
			{
				$handler = Prado::createComponent($class);
			}
			else
			{
				if(class_exists($class,false))
					$handler = new $class;
				else
				throw new TSqlMapConfigurationException(
						'sqlmap_type_handler_class_undef', $file, $class);
			}
			$factory = $this->_sqlMapper->getTypeHandlerFactory();
			$factory->register($type, $handler, $dbType);
		}
		else
		{
			throw new TSqlMapConfigurationException(
				'sqlmap_type_handler_callback_undef', $file);
		}
	}

	protected function loadSqlMappingFiles($node)
	{
		$resource = $this->getResourceFromPath((string)$node['resource']);
		$sqlmap = $this->getConfigAsXmlDocument($resource);
		$this->configureSqlMap($sqlmap,$resource);
		$this->resolveResultMapping();
	}

	protected function getResourceFromPath($resource)
	{
		$basedir = dirname($this->_configFile);
		$file = realpath($basedir.'/'.$resource);
		if(!is_string($file) || !is_file($file))
			$file = realpath($resource);
		if(is_string($file) && is_file($file))
			return $file;
		else
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_resource', $resource);
	}

	protected function configureSqlMap($document,$file)
	{
	//	if(isset($document->typeAlias))
	//		foreach($document->typeAlias as $node)
	//			TTypeAliasDeSerializer::Deserialize($node, $this->_sqlMapper);
		foreach($document->xpath('//resultMap') as $node)	
			$this->loadResultMap($node,$document,$file);

		foreach($document->xpath('//parameterMap') as $node)
			$this->loadParameterMap($node, $document, $file);

		foreach($document->xpath('//statement') as $node)
			$this->loadStatementTag($node, $document,$file);
		
		foreach($document->xpath('//select') as $node)
			$this->loadSelectTag($node, $document, $file);

		foreach($document->xpath('//insert') as $node)
			$this->loadInsertTag($node, $document, $file);
		
		foreach($document->xpath('//update') as $node)
			$this->loadUpdateTag($node, $document, $file);

		foreach($document->xpath('//delete') as $node)
			$this->loadDeleteTag($node, $document, $file);
/*		if(isset($document->procedure))
			foreach($document->procedure as $node)
				$this->loadProcedureTag($node);
*/
		if($this->_sqlMapper->getIsCacheModelsEnabled())
		{
			if(isset($document->cacheModel))
				foreach($document->cacheModel as $node)
					$this->loadCacheModel($node, $document, $file);
		}
	}

	protected function loadCacheModel($node, $document, $file)
	{
		$cacheModel = $this->_deserialize->cacheModel($node, $this->_sqlMapper, $file);
		if(isset($node->flushOnExecute))
		{
			foreach($node->flushOnExecute as $flush)
			{
				$id = $cacheModel->getID();
				if(!isset($this->_FlushOnExecuteStatements[$id]))
					$this->_FlushOnExecuteStatements[$id] = array();

				$this->_FlushOnExecuteStatements[$id][] = (string)$flush['statement'];
			}
		}
		//var_dump($cacheModel);
		$cacheModel->initialize($this->_sqlMapper);
		$this->_sqlMapper->addCache($cacheModel);
	}

	protected function loadUpdateTag($node, $document, $file)
	{
		$update = $this->_deserialize->insert($node, $this->_sqlMapper, $file);
		if(!is_null($update->getGenerate()))
		{
			var_dump('generate update');
		}
		else
		{
			$this->processSqlStatement($update, $document, $node, $file);
		}
		$mappedStatement = new TUpdateMappedStatement($this->_sqlMapper, $update);
		$this->_sqlMapper->addMappedStatement($mappedStatement);
	}

	protected function loadDeleteTag($node, $document, $file)
	{
		$delete = $this->_deserialize->delete($node, $this->_sqlMapper, $file);
		if(!is_null($delete->getGenerate()))
		{
			var_dump('generate delete');
		}
		else
		{
			$this->processSqlStatement($delete, $document, $node, $file);
		}
		$mappedStatement = new TDeleteMappedStatement($this->_sqlMapper, $delete);
		$this->_sqlMapper->addMappedStatement($mappedStatement);
	}


	protected function loadParameterMap($node, $document, $file)
	{
		$id = (string)$node['id'];
		if($this->_sqlMapper->getParameterMaps()->contains($id))
			return;
		$parameterMap = $this->_deserialize->parameterMap($node, $this->_sqlMapper, $file);
		$extendMap = $parameterMap->getExtends();
		if(strlen($extendMap) > 0)
		{
			if($this->_sqlMapper->getParameterMaps()->contains($extendMap) == false)
			{
				$nodes = $document->xpath("//parameterMap[@id='{$extendMap}']");
				if(isset($nodes[0]))
					$this->loadParameterMap($nodes[0],$document,$file);
				else
					throw new TSqlMapConfigurationException(
						'sqlmap_unable_to_find_parent_parameter_map', $extendMap, $file);
			}
			$superMap = $this->_sqlMapper->getParameterMap($extendMap);
			$index = 0;
			foreach($superMap->getPropertyNames() as $propertyName)
			{
				$parameterMap->insertParameterProperty($index++, 
						$superMap->getProperty($propertyName));
			}
		}
		$this->_sqlMapper->addParameterMap($parameterMap);
	}

	protected function loadInsertTag($node, $document, $file)
	{
		$insert = $this->_deserialize->insert($node, $this->_sqlMapper, $file);
		if(!is_null($insert->getGenerate()))
		{
			var_dump("generate insert");
		}
		else
		{
			$this->processSqlStatement($insert, $document, $node, $file);
		}

		$mappedStatement = new TInsertMappedStatement($this->_sqlMapper, $insert);
		$this->_sqlMapper->addMappedStatement($mappedStatement);
		if(!is_null($insert->getSelectKey()))
		{
			$selectKey = $insert->getSelectKey();
			$selectKey->setID($insert->getID());
			$selectKey->initialize($this->_sqlMapper);
			$selectKey->setID($insert->getID().'.SelectKey');
			$this->processSqlStatement($selectKey, 
					$document, $node->selectKey, $file);
			$mappedStatement = new TMappedStatement($this->_sqlMapper, $selectKey);
			$this->_sqlMapper->addMappedStatement($mappedStatement);
		}
	}

	protected function processSqlStatement($statement, $document, $node, $file)
	{
		$commandText = (string)$node;
		if(strlen($extend = $statement->getExtends()) > 0)
		{
			$superNodes = $document->xpath("//*[@id='{$extend}']");
			if(isset($superNodes[0]))
				$commandText = (string)$superNodes[0] . $commandText;
			else
				throw new TSqlMapConfigurationException(
						'sqlmap_unable_to_find_parent_sql', $extend, $file);
		}

		//$sql = new TStaticSql();
		//$sql->buildPreparedStatement($statement, (string)$node);
		$commandText = $this->_deserialize->replaceProperties($commandText);
		$this->applyInlineParameterMap($statement, $commandText, $node, $file);
		//$statement->setSql($sql);
	}

	protected function applyInlineParameterMap($statement, $sqlStatement, $node, $file)
	{
		$scope['statement']  = $statement->getID();
		$scope['file'] = $file;
	
		if($statement->parameterMap() == null)
		{
			// Build a Parametermap with the inline parameters.
			// if they exist. Then delete inline infos from sqltext.
			$parameterParser = new TInlineParameterMapParser;
			$sqlText = $parameterParser->parse(
							$this->_sqlMapper, $statement, $sqlStatement, $scope);
			if(count($sqlText['parameters']) > 0)
			{
				$map = new TParameterMap();
				$map->setID($statement->getID().'-InLineParameterMap');
				$statement->setInlineParameterMap($map);
				foreach($sqlText['parameters'] as $property)
					$map->addParameterProperty($property);
			}
			$sqlStatement = $sqlText['sql'];
		}
		
		$simpleDynamic = new TSimpleDynamicParser;
		$dynamics = $simpleDynamic->parse($this->_sqlMapper, $statement, $sqlStatement, $scope);
		if(count($dynamics['parameters']) > 0)
		{
			$sql = new TSimpleDynamicSql($dynamics['parameters']);
			$sqlStatement = $dynamics['sql'];
		}
		else
			$sql = new TStaticSql();
		$sql->buildPreparedStatement($statement, $sqlStatement);
		$statement->setSql($sql);
	}

	protected function resolveResultMapping()
	{
		$maps = $this->_sqlMapper->getResultMaps();
		foreach($maps as $entry)
		{
			foreach($entry->getColumns() as $item)
			{
				$resultMap = $item->getResultMapping();
				if(strlen($resultMap) > 0)
				{
					if($maps->contains($resultMap))
						$item->setNestedResultMap($maps[$resultMap]);
					else
						throw new TSqlMapConfigurationException(
							'sqlmap_unable_to_find_result_mapping', 
								$resultMap, $this->_configFile, $entry->getID());
				}
			}
			if(!is_null($entry->getDiscriminator()))
			{
				$entry->getDiscriminator()->initialize($this->_sqlMapper);
			}
		}
	}

	protected function loadSelectTag($node, $document, $file)
	{
		$select = $this->_deserialize->select($node, $this->_sqlMapper, $file);
		if(!is_null($select->getGenerate()))
		{
			var_dump("generate select");
		}
		else
		{
			$this->processSqlStatement($select, $document, $node, $file);
			/*$sql = new TStaticSql();
			$sql->buildPreparedStatement($select, (string)$node);
			$select->setSql($sql);*/
		}
		
		$mappedStatement = new TMappedStatement($this->_sqlMapper, $select);
		if($this->_sqlMapper->getIsCacheModelsEnabled() && 
				strlen($select->getCacheModel()) > 0)
		{
			$mappedStatement = new TCachingStatement($mappedStatement);
		}

		$this->_sqlMapper->addMappedStatement($mappedStatement);
	}

	protected function loadResultMap($node,$document,$file)
	{
		$resultMap = $this->_deserialize->resultMap($node, $this->_sqlMapper,$file);
		$extendMap = $resultMap->getExtends();
		if(strlen($extendMap) > 0)
		{
			if(!$this->_sqlMapper->getResultMaps()->contains($extendMap))
			{
				$nodes = $document->xpath("//resultMap[@id='{$extendMap}']");
				if(isset($nodes[0]))
					$this->loadResultMap($nodes[0],$document,$file);
				else
					throw new TSqlMapConfigurationException(
						'sqlmap_unable_to_find_parent_result_map', $extendMap, $file);
			}
			$superMap = $this->_sqlMapper->getResultMap($extendMap);
			$resultMap->getColumns()->mergeWith($superMap->getColumns());
		}
		if(!$this->_sqlMapper->getResultMaps()->contains($resultMap->getID()))
			$this->_sqlMapper->addResultMap($resultMap);
	}


	protected function loadStatementTag($node, $document, $file)
	{
		$statement = $this->_deserialize->statement($node, $this->_sqlMapper, $file);
		
		/*$sql = new TStaticSql();
		$sql->buildPreparedStatement($statement, (string)$node);
		$statement->setSql($sql);*/
		$this->processSqlStatement($statement, $document, $node, $file);

		$mappedStatement = new TMappedStatement($this->_sqlMapper, $statement);
		$this->_sqlMapper->addMappedStatement($mappedStatement);
	}
}

?>