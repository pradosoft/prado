<?php
/**
 * TSqlMapStatement, TSqlMapInsert, TSqlMapUpdate, TSqlMapDelete,
 * TSqlMapSelect and TSqlMapSelectKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapStatement class corresponds to <statement> element.
 *
 * Mapped Statements can hold any SQL statement and can use Parameter Maps
 * and Result Maps for input and output.
 *
 * The <statement> element is a general "catch all" element that can be used
 * for any type of SQL statement. Generally it is a good idea to use one of the
 * more specific statement-type elements. The more specific elements provided
 * better error-checking and even more functionality. (For example, the insert
 * statement can return a database-generated key.)
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSqlMapStatement extends \Prado\TComponent
{
	private $_parameterMapName;
	private $_parameterMap;
	private $_parameterClassName;
	private $_resultMapName;
	private $_resultMap;
	private $_resultClassName;
	private $_cacheModelName;
	private $_SQL;
	private $_listClass;
	private $_typeHandler;
	private $_extendStatement;
	private $_cache;
	private $_ID;

	/**
	 * @return string name for this statement, unique to each sql map manager.
	 */
	public function getID()
	{
		return $this->_ID;
	}

	/**
	 * @param string $value name for this statement, which must be unique for each sql map manager.
	 */
	public function setID($value)
	{
		$this->_ID = $value;
	}

	/**
	 * @return string name of a parameter map.
	 */
	public function getParameterMap()
	{
		return $this->_parameterMapName;
	}

	/**
	 * A Parameter Map defines an ordered list of values that match up with
	 * the "?" placeholders of a standard, parameterized query statement.
	 * @param string $value parameter map name.
	 */
	public function setParameterMap($value)
	{
		$this->_parameterMapName = $value;
	}

	/**
	 * @return string parameter class name.
	 */
	public function getParameterClass()
	{
		return $this->_parameterClassName;
	}

	/**
	 * If a {@link ParameterMap setParameterMap()} property is not specified,
	 * you may specify a ParameterClass instead and use inline parameters.
	 * The value of the parameterClass attribute can be any existing PHP class name.
	 * @param string $value parameter class name.
	 */
	public function setParameterClass($value)
	{
		$this->_parameterClassName = $value;
	}

	/**
	 * @return string result map name.
	 */
	public function getResultMap()
	{
		return $this->_resultMapName;
	}

	/**
	 * A Result Map lets you control how data is extracted from the result of a
	 * query, and how the columns are mapped to object properties.
	 * @param string $value result map name.
	 */
	public function setResultMap($value)
	{
		$this->_resultMapName = $value;
	}

	/**
	 * @return string result class name.
	 */
	public function getResultClass()
	{
		return $this->_resultClassName;
	}

	/**
	 * If a {@link ResultMap setResultMap()} is not specified, you may specify a
	 * ResultClass instead. The value of the ResultClass property can be the
	 * name of a PHP class or primitives like integer, string, or array. The
	 * class specified will be automatically mapped to the columns in the
	 * result, based on the result metadata.
	 * @param string $value result class name.
	 */
	public function setResultClass($value)
	{
		$this->_resultClassName = $value;
	}

	/**
	 * @return string cache mode name.
	 */
	public function getCacheModel()
	{
		return $this->_cacheModelName;
	}

	/**
	 * @param string $value cache mode name.
	 */
	public function setCacheModel($value)
	{
		$this->_cacheModelName = $value;
	}

	/**
	 * @return TSqlMapCacheModel cache implementation instance for this statement.
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * @param TSqlMapCacheModel $value cache implementation instance for this statement.
	 */
	public function setCache($value)
	{
		$this->_cache = $value;
	}

	/**
	 * @return TStaticSql sql text container.
	 */
	public function getSqlText()
	{
		return $this->_SQL;
	}

	/**
	 * @param TStaticSql $value sql text container.
	 */
	public function setSqlText($value)
	{
		$this->_SQL = $value;
	}

	/**
	 * @return string name of a PHP class that implements \ArrayAccess.
	 */
	public function getListClass()
	{
		return $this->_listClass;
	}

	/**
	 * An \ArrayAccess class can be specified to handle the type of objects in the collection.
	 * @param string $value name of a PHP class that implements \ArrayAccess.
	 */
	public function setListClass($value)
	{
		$this->_listClass = $value;
	}

	/**
	 * @return string another statement element name.
	 */
	public function getExtends()
	{
		return $this->_extendStatement;
	}

	/**
	 * @param string $value name of another statement element to extend.
	 */
	public function setExtends($value)
	{
		$this->_extendStatement = $value;
	}

	/**
	 * @return TResultMap the result map corresponding to the
	 * {@link ResultMap getResultMap()} property.
	 */
	public function resultMap()
	{
		return $this->_resultMap;
	}

	/**
	 * @return TParameterMap the parameter map corresponding to the
	 * {@link ParameterMap getParameterMap()} property.
	 */
	public function parameterMap()
	{
		return $this->_parameterMap;
	}

	/**
	 * @param TInlineParameterMap $map parameter extracted from the sql text.
	 */
	public function setInlineParameterMap($map)
	{
		$this->_parameterMap = $map;
	}

	/**
	 * @param TSqlMapManager $manager initialize the statement, sets the result and parameter maps.
	 */
	public function initialize($manager)
	{
		if (strlen($this->_resultMapName) > 0) {
			$this->_resultMap = $manager->getResultMap($this->_resultMapName);
		}
		if (strlen($this->_parameterMapName) > 0) {
			$this->_parameterMap = $manager->getParameterMap($this->_parameterMapName);
		}
	}

	/**
	 * @param TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @return \ArrayAccess new instance of list class.
	 */
	public function createInstanceOfListClass($registry)
	{
		if (strlen($type = $this->getListClass()) > 0) {
			return $this->createInstanceOf($registry, $type);
		}
		return [];
	}

	/**
	 * Create a new instance of a given type.
	 * @param TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @param string $type result class name.
	 * @param array $row result data.
	 * @return mixed result object.
	 */
	protected function createInstanceOf($registry, $type, $row = null)
	{
		$handler = $registry->getTypeHandler($type);
		if ($handler !== null) {
			return $handler->createNewInstance($row);
		} else {
			return $registry->createInstanceOf($type);
		}
	}

	/**
	 * Create a new instance of result class.
	 * @param TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @param array $row result data.
	 * @return mixed result object.
	 */
	public function createInstanceOfResultClass($registry, $row)
	{
		if (strlen($type = $this->getResultClass()) > 0) {
			return $this->createInstanceOf($registry, $type, $row);
		}
	}

	public function __sleep()
	{
		$cn = __CLASS__;
		$exprops = ["\0$cn\0_resultMap"];
		if (!$this->_parameterMapName) {
			$exprops[] = "\0$cn\0_parameterMapName";
		}
		if (!$this->_parameterMap) {
			$exprops[] = "\0$cn\0_parameterMap";
		}
		if (!$this->_parameterClassName) {
			$exprops[] = "\0$cn\0_parameterClassName";
		}
		if (!$this->_resultMapName) {
			$exprops[] = "\0$cn\0_resultMapName";
		}
		if (!$this->_resultMap) {
			$exprops[] = "\0$cn\0_resultMap";
		}
		if (!$this->_resultClassName) {
			$exprops[] = "\0$cn\0_resultClassName";
		}
		if (!$this->_cacheModelName) {
			$exprops[] = "\0$cn\0_cacheModelName";
		}
		if (!$this->_SQL) {
			$exprops[] = "\0$cn\0_SQL";
		}
		if (!$this->_listClass) {
			$exprops[] = "\0$cn\0_listClass";
		}
		if (!$this->_typeHandler) {
			$exprops[] = "\0$cn\0_typeHandler";
		}
		if (!$this->_extendStatement) {
			$exprops[] = "\0$cn\0_extendStatement";
		}
		if (!$this->_cache) {
			$exprops[] = "\0$cn\0_cache";
		}

		return array_diff(parent::__sleep(), $exprops);
	}
}
