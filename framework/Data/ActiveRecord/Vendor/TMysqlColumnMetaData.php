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
	private $_property;
	private $_length;

	private $_typeValues;

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
	public function __construct($property, $name,$type,$notNull,$autoIncrement,$default,$primary)
	{
		$this->_property=$property;
		$this->_name=$name;
		//$this->_type=$type;
		$this->_notNull=$notNull;
		$this->_autoIncrement=$autoIncrement;
		$this->_default=$default;
		$this->_isPrimary=$primary;
		$this->processType($type);
	}

	protected function processType($type)
	{
		if(is_int($pos=strpos($type, '(')))
		{
			$match=array();
			if(preg_match('/\((.*)\)/', $type, $match))
			{
				$this->_type = substr($type,0,$pos);
				switch(strtolower($this->_type))
				{
					case 'set':
					case 'enum':
						$this->_typeValues = preg_split('/\s*,\s*|\s+/', preg_replace('/\'|"/', '', $match[1]));
						break;
					default:
						$this->_length=floatval($match[1]);
				}
			}
			else
				$this->_type = $type;
		}
		else
			$this->_type = $type;
	}

	public function getTypeValues()
	{
		return $this->_typeValues;
	}

	public function getLength()
	{
		return $this->_length;
	}

	/**
	 * @return string quoted column name.
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @return string column name, used as active record property name
	 */
	public function getProperty()
	{
		return $this->_property;
	}

	public function getPHPType()
	{
		switch(strtolower($this->_type))
		{
			case 'tinyint': case 'smallint': case 'mediumint': case 'int': case 'year':
				return 'integer';
			case 'bool':
				return 'boolean';
			case 'bigint': case 'float': case 'double': case 'decimal':
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