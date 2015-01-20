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
 * TSqlMapInsert class corresponds to the <insert> element.
 *
 * The <insert> element allows <selectKey> child elements that can be used
 * to generate a key to be used for the insert command.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.SqlMap.Configuration
 * @since 3.1
 */
class TSqlMapInsert extends TSqlMapStatement
{
	private $_selectKey=null;

	/**
	 * @return TSqlMapSelectKey select key element.
	 */
	public function getSelectKey()
	{
		return $this->_selectKey;
	}

	/**
	 * @param TSqlMapSelectKey select key.
	 */
	public function setSelectKey($value)
	{
		$this->_selectKey = $value;
	}
}