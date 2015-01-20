<?php
/**
 * TSqlMapStatement, TSqlMapInsert, TSqlMapUpdate, TSqlMapDelete,
 * TSqlMapSelect and TSqlMapSelectKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.SqlMap.Configuration
 */

/**
 * TSqlMapSelect corresponds to the <selectKey> element.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.SqlMap.Configuration
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
	 * @param string select generated key type, 'post' or 'pre'.
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
	 * @param string property name for the generated key.
	 */
	public function setProperty($value)
	{
		$this->_property = $value;
	}

	/**
	 * @throws TSqlMapConfigurationException extends is unsupported.
	 */
	public function setExtends($value)
	{
		throw new TSqlMapConfigurationException('sqlmap_can_not_extend_select_key');
	}

	/**
	 * @return boolean true if key is generated after insert command, false otherwise.
	 */
	public function getIsAfter()
	{
		return $this->_type == 'post';
	}
}