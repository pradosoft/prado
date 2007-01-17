<?php
/**
 * TPgsqlColumnMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

/**
 * Column meta data for Postgre 7.3 or later.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TPgsqlColumnMetaData extends TComponent
{
	private $_name;
	private $_type;
	private $_sequenceName;
	private $_default;
	private $_length;
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
	public function __construct($name,$type,$length,$notNull,$serial,$default)
	{
		$this->_name=$name;
		$this->_type=$type;
		$this->_length=$length;
		$this->_notNull=$notNull;
		$this->_sequenceName=$serial;
		$this->_default=$default;
	}

	/**
	 * @return string quoted column name.
	 */
	public function getName()
	{
		return $this->_name;
	}

	public function getPHPType()
	{
		switch(strtolower($this->_type))
		{
			case 'bit': case 'bit varying': case 'real': case 'serial':
				return 'integer';
			case 'boolean':
				return 'boolean';
			case 'bigint': case 'bigserial': case 'double precision': case 'money': case 'numeric':
				return 'float';
			default:
				return 'string';
		}
	}

	/**
	 * @return boolean true if column is a sequence, false otherwise.
	 */
	public function hasSequence()
	{
		return $this->_sequenceName != null;
	}

	/**
	 * @return string sequence name, only applicable if column is a sequence.
	 */
	public function getSequenceName()
	{
		return $this->_sequenceName;
	}

	/**
	 * Set the column as primary key
	 */
	public function setIsPrimaryKey($value)
	{
		if($this->_isPrimary===null)
			$this->_isPrimary=$value;
		else
			throw new TActiveRecordException('ar_column_meta_data_read_only');
	}

	/**
	 * @return boolean true if the column is a primary key, or part of a composite primary key.
	 */
	public function getIsPrimaryKey()
	{
		return $this->_isPrimary===null? false : $this->_isPrimary;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function getLength()
	{
		return $this->_length;
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