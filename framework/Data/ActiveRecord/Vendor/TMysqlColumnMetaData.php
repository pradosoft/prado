<?php
/**
 * TMysqlColumnMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

/**
 * Column meta data for Mysql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TMysqlColumnMetaData extends TComponent
{
	private $_name;
	private $_type;
	private $_autoIncrement;
	private $_default;
	private $_notNull=true;

	private $_isPrimary=null;

	/**
	 * Initialize column meta data.
	 *
	 * @param string column name.
	 * @param string column data type.
	 * @param string column data length.
	 * @param boolean column can not be null.
	 * @param string serial name.
	 * @param string default value.
	 */
	public function __construct($name,$type,$notNull,$autoIncrement,$default,$primary)
	{
		$this->_name=$name;
		$this->_type=$type;
		$this->_notNull=$notNull;
		$this->_autoIncrement=$autoIncrement;
		$this->_default=$default;
		$this->_isPrimary=$primary;
	}

	/**
	 * @return string quoted column name.
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @return boolean true if column is a sequence, false otherwise.
	 */
	public function hasSequence()
	{
		return $this->_autoIncrement;
	}

	/**
	 * @return null no sequence name.
	 */
	public function getSequenceName()
	{
		return null;
	}

	/**
	 * @return boolean true if the column is a primary key, or part of a composite primary key.
	 */
	public function getIsPrimaryKey()
	{
		return $this->_isPrimary;
	}

	public function getType()
	{
		return $this->_type;
	}


	public function getNotNull()
	{
		return $this->_notNull;
	}

	/**
	 * @return boolean true if column has default value, false otherwise.
	 */
	public function hasDefault()
	{
		return $this->_default !== null;
	}

	public function getDefaultValue()
	{
		return $this->_default;
	}
}

?>