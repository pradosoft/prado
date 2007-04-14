<?php
/**
 * TIbmColumnMetaData class file.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TIbmColumnMetaData.php 1807 2007-03-31 06:42:15Z wei $
 * @package System.Data.ActiveRecord.Vendor
 */

/**
 * TIbmColumnMetaData class.
 *
 * Column details for IBM DB2 database. Using php_pdo_ibm.dll extension.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @version $Id: TIbmColumnMetaData.php 1807 2007-03-31 06:42:15Z wei $
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TIbmColumnMetaData extends TComponent
{
	private $_name;
	private $_type;
	private $_length;
	private $_autoIncrement;
	private $_default;
	private $_notNull=true;

	private $_isPrimary=null;

	private $_property;

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
	public function __construct($name,$type,$length,$notNull,$autoIncrement,$default,$primary)
	{
		$this->_property=$name;
		$this->_name=$name;
		$this->_type=$type;
		$this->_length=$length;
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
	 * @return integer length.
	 */
	public function getLength()
	{
		return $this->_length;
	}

	/**
	 * @return string active record property name
	 */
	public function getProperty()
	{
		return $this->_property;
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

	/**
	 * @return string column type
	 */
	public function getType()
	{
		return $this->_type;
	}


	/**
	 * @return boolean false if column can be null, true otherwise.
	 */
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

	/**
	 * @return string default column value.
	 */
	public function getDefaultValue()
	{
		return $this->_default;
	}

	/**
	 * @return string PHP primative type derived from the column type.
	 */
	public function getPHPType()
	{
		switch(strtolower($this->_type))
		{
			case 'smallint': case 'integer':
				return 'integer';
			case 'real': case 'float': case 'double': case 'decimal': case 'bigint':
				return 'float';
			default:
				return 'string';
		}
	}

}

?>