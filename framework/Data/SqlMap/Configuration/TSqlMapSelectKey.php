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

use Prado\Data\SqlMap\DataMapper\TSqlMapConfigurationException;

/**
 * TSqlMapSelect corresponds to the <selectKey> element.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSqlMapSelectKey extends TSqlMapStatement
{
	private $_type = 'post';
	private $_property;

	/**
	 * @return string select generated key type, 'post' or 'pre'.
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param string $value select generated key type, 'post' or 'pre'.
	 */
	public function setType($value)
	{
		$this->_type = strtolower($value) == 'post' ? 'post' : 'pre';
	}

	/**
	 * @return string property name for the generated key.
	 */
	public function getProperty()
	{
		return $this->_property;
	}

	/**
	 * @param string $value property name for the generated key.
	 */
	public function setProperty($value)
	{
		$this->_property = $value;
	}

	/**
	 * @param mixed $value
	 * @throws TSqlMapConfigurationException extends is unsupported.
	 */
	public function setExtends($value)
	{
		throw new TSqlMapConfigurationException('sqlmap_can_not_extend_select_key');
	}

	/**
	 * @return bool true if key is generated after insert command, false otherwise.
	 */
	public function getIsAfter()
	{
		return $this->_type == 'post';
	}
}
