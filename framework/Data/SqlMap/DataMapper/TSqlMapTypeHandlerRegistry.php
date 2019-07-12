<?php
/**
 * TSqlMapTypeHandlerRegistry, and abstract TSqlMapTypeHandler classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Prado;

/**
 * TTypeHandlerFactory provides type handler classes to convert database field type
 * to PHP types and vice versa.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
class TSqlMapTypeHandlerRegistry
{
	private $_typeHandlers = [];

	/**
	 * @param string $dbType database field type
	 * @return TSqlMapTypeHandler type handler for give database field type.
	 */
	public function getDbTypeHandler($dbType = 'NULL')
	{
		foreach ($this->_typeHandlers as $handler) {
			if ($handler->getDbType() === $dbType) {
				return $handler;
			}
		}
	}

	/**
	 * @param string $class type handler class name
	 * @return TSqlMapTypeHandler type handler
	 */
	public function getTypeHandler($class)
	{
		if (isset($this->_typeHandlers[$class])) {
			return $this->_typeHandlers[$class];
		}
	}

	/**
	 * @param TSqlMapTypeHandler $handler registers a new type handler
	 */
	public function registerTypeHandler(TSqlMapTypeHandler $handler)
	{
		$this->_typeHandlers[$handler->getType()] = $handler;
	}

	/**
	 * Creates a new instance of a particular class (for PHP primative types,
	 * their corresponding default value for given type is used).
	 * @param string $type PHP type name
	 * @throws TSqlMapException if class name is not found.
	 * @return mixed default type value, if no type is specified null is returned.
	 */
	public function createInstanceOf($type = '')
	{
		if (strlen($type) > 0) {
			switch (strtolower($type)) {
				case 'string': return '';
				case 'array': return [];
				case 'float': case 'double': case 'decimal': return 0.0;
				case 'integer': case 'int': return 0;
				case 'bool': case 'boolean': return false;
			}

			if (class_exists('Prado', false)) {
				return Prado::createComponent($type);
			} elseif (class_exists($type, false)) { //NO auto loading
				return new $type;
			} else {
				throw new TSqlMapException('sqlmap_unable_to_find_class', $type);
			}
		}
	}

	/**
	 * Converts the value to given type using PHP's settype() function.
	 * @param string $type PHP primative type.
	 * @param mixed $value value to be casted
	 * @return mixed type casted value.
	 */
	public function convertToType($type, $value)
	{
		switch (strtolower($type)) {
			case 'integer': case 'int':
				$type = 'integer'; break;
			case 'float': case 'double': case 'decimal':
				$type = 'float'; break;
			case 'boolean': case 'bool':
				$type = 'boolean'; break;
			case 'string':
				$type = 'string'; break;
			default:
				return $value;
		}
		settype($value, $type);
		return $value;
	}
}
