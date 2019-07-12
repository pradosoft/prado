<?php
/**
 * TSqlMapCacheModel, TSqlMapCacheTypes and TSqlMapCacheKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapCacheKey class.
 *
 * Provides a hash of the object to be cached.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSqlMapCacheKey
{
	private $_key;

	/**
	 * @param mixed $object object to be cached.
	 */
	public function __construct($object)
	{
		$this->_key = $this->generateKey(serialize($object));
	}

	/**
	 * @param string $string serialized object
	 * @return string crc32 hash of the serialized object.
	 */
	protected function generateKey($string)
	{
		return sprintf('%x', crc32($string));
	}

	/**
	 * @return string object hash.
	 */
	public function getHash()
	{
		return $this->_key;
	}
}
