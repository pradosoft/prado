<?php
/**
 * TSqlMapManager class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap
 */

namespace Prado\Data\SqlMap;

use Prado\Caching\TChainedCacheDependency;
use Prado\Collections\TMap;
use Prado\Data\SqlMap\Configuration\TParameterMap;
use Prado\Data\SqlMap\Configuration\TResultMap;
use Prado\Data\SqlMap\Configuration\TSqlMapCacheModel;
use Prado\Data\SqlMap\Configuration\TSqlMapXmlConfiguration;
use Prado\Data\SqlMap\DataMapper\TSqlMapConfigurationException;
use Prado\Data\SqlMap\DataMapper\TSqlMapDuplicateException;
use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry;
use Prado\Data\SqlMap\DataMapper\TSqlMapUndefinedException;
use Prado\Data\SqlMap\Statements\IMappedStatement;
use Prado\Prado;

/**
 * TSqlMapManager class holds the sqlmap configuation result maps, statements
 * parameter maps and a type handler factory.
 *
 * Use {@link SqlMapGateway getSqlMapGateway()} property to obtain the gateway
 * instance used for querying statements defined in the SqlMap configuration files.
 *
 * <code>
 * $conn = new TDbConnection($dsn,$dbuser,$dbpass);
 * $manager = new TSqlMapManager($conn);
 * $manager->configureXml('mydb-sqlmap.xml');
 * $sqlmap = $manager->getSqlMapGateway();
 * $result = $sqlmap->queryForObject('Products');
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap
 * @since 3.1
 */
class TSqlMapManager extends \Prado\TComponent
{
	private $_mappedStatements;
	private $_resultMaps;
	private $_parameterMaps;
	private $_typeHandlers;
	private $_cacheModels;

	private $_connection;
	private $_gateway;
	private $_cacheDependencies;

	/**
	 * Constructor, create a new SqlMap manager.
	 * @param TDbConnection $connection database connection
	 */
	public function __construct($connection = null)
	{
		$this->_connection = $connection;

		$this->_mappedStatements = new TMap;
		$this->_resultMaps = new TMap;
		$this->_parameterMaps = new TMap;
		$this->_cacheModels = new TMap;
	}

	/**
	 * @param TDbConnection $conn default database connection
	 */
	public function setDbConnection($conn)
	{
		$this->_connection = $conn;
	}

	/**
	 * @return TDbConnection default database connection
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return TTypeHandlerFactory The TypeHandlerFactory
	 */
	public function getTypeHandlers()
	{
		if ($this->_typeHandlers === null) {
			$this->_typeHandlers = new TSqlMapTypeHandlerRegistry();
		}
		return $this->_typeHandlers;
	}

	/**
	 * @return TSqlMapGateway SqlMap gateway.
	 */
	public function getSqlmapGateway()
	{
		if ($this->_gateway === null) {
			$this->_gateway = $this->createSqlMapGateway();
		}
		return $this->_gateway;
	}

	/**
	 * Loads and parses the SqlMap configuration file.
	 * @param string $file xml configuration file.
	 */
	public function configureXml($file)
	{
		$config = new TSqlMapXmlConfiguration($this);
		$config->configure($file);
	}

	/**
	 * @return TChainedCacheDependency
	 * @since 3.1.5
	 */
	public function getCacheDependencies()
	{
		if ($this->_cacheDependencies === null) {
			$this->_cacheDependencies = new TChainedCacheDependency();
		}

		return $this->_cacheDependencies;
	}

	/**
	 * Configures the current TSqlMapManager using the given xml configuration file
	 * defined in {@link ConfigFile setConfigFile()}.
	 * @return TSqlMapGateway create and configure a new TSqlMapGateway.
	 */
	protected function createSqlMapGateway()
	{
		return new TSqlMapGateway($this);
	}

	/**
	 * @return TMap mapped statements collection.
	 */
	public function getMappedStatements()
	{
		return $this->_mappedStatements;
	}

	/**
	 * Gets a MappedStatement by name.
	 * @param string $name The name of the statement.
	 * @throws TSqlMapUndefinedException
	 * @return IMappedStatement The MappedStatement
	 */
	public function getMappedStatement($name)
	{
		if ($this->_mappedStatements->contains($name) == false) {
			throw new TSqlMapUndefinedException('sqlmap_contains_no_statement', $name);
		}
		return $this->_mappedStatements[$name];
	}

	/**
	 * Adds a (named) MappedStatement.
	 * @param IMappedStatement $statement The statement to add
	 * @throws TSqlMapDuplicateException
	 */
	public function addMappedStatement(IMappedStatement $statement)
	{
		$key = $statement->getID();
		if ($this->_mappedStatements->contains($key) == true) {
			throw new TSqlMapDuplicateException('sqlmap_already_contains_statement', $key);
		}
		$this->_mappedStatements->add($key, $statement);
	}

	/**
	 * @return TMap result maps collection.
	 */
	public function getResultMaps()
	{
		return $this->_resultMaps;
	}

	/**
	 * Gets a named result map
	 * @param string $name result name.
	 * @throws TSqlMapUndefinedException
	 * @return TResultMap the result map.
	 */
	public function getResultMap($name)
	{
		if ($this->_resultMaps->contains($name) == false) {
			throw new TSqlMapUndefinedException('sqlmap_contains_no_result_map', $name);
		}
		return $this->_resultMaps[$name];
	}

	/**
	 * @param TResultMap $result add a new result map to this SQLMap
	 * @throws TSqlMapDuplicateException
	 */
	public function addResultMap(TResultMap $result)
	{
		$key = $result->getID();
		if ($this->_resultMaps->contains($key) == true) {
			throw new TSqlMapDuplicateException('sqlmap_already_contains_result_map', $key);
		}
		$this->_resultMaps->add($key, $result);
	}

	/**
	 * @return TMap parameter maps collection.
	 */
	public function getParameterMaps()
	{
		return $this->_parameterMaps;
	}

	/**
	 * @param string $name parameter map ID name.
	 * @throws TSqlMapUndefinedException
	 * @return TParameterMap the parameter with given ID.
	 */
	public function getParameterMap($name)
	{
		if ($this->_parameterMaps->contains($name) == false) {
			throw new TSqlMapUndefinedException('sqlmap_contains_no_parameter_map', $name);
		}
		return $this->_parameterMaps[$name];
	}

	/**
	 * @param TParameterMap $parameter add a new parameter map to this SQLMap.
	 * @throws TSqlMapDuplicateException
	 */
	public function addParameterMap(TParameterMap $parameter)
	{
		$key = $parameter->getID();
		if ($this->_parameterMaps->contains($key) == true) {
			throw new TSqlMapDuplicateException('sqlmap_already_contains_parameter_map', $key);
		}
		$this->_parameterMaps->add($key, $parameter);
	}

	/**
	 * Adds a named cache.
	 * @param TSqlMapCacheModel $cacheModel the cache to add.
	 * @throws TSqlMapConfigurationException
	 */
	public function addCacheModel(TSqlMapCacheModel $cacheModel)
	{
		if ($this->_cacheModels->contains($cacheModel->getID())) {
			throw new TSqlMapConfigurationException('sqlmap_cache_model_already_exists', $cacheModel->getID());
		} else {
			$this->_cacheModels->add($cacheModel->getID(), $cacheModel);
		}
	}

	/**
	 * Gets a cache by name
	 * @param string $name the name of the cache to get.
	 * @throws TSqlMapConfigurationException
	 * @return TSqlMapCacheModel the cache object.
	 */
	public function getCacheModel($name)
	{
		if (!$this->_cacheModels->contains($name)) {
			throw new TSqlMapConfigurationException('sqlmap_unable_to_find_cache_model', $name);
		}
		return $this->_cacheModels[$name];
	}

	/**
	 * Flushes all cached objects that belong to this SqlMap
	 */
	public function flushCacheModels()
	{
		foreach ($this->_cacheModels as $cache) {
			$cache->flush();
		}
	}
}
