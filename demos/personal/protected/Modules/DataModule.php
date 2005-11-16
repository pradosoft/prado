<?php

class DataModule extends TComponent implements IModule
{
	/**
	 * extension of the db file name
	 */
	const DB_FILE_EXT='.db';
	const DB_SCHEMA='
		CREATE TABLE tblBlogs (id INTEGER PRIMARY KEY, title VARCHAR(256), content TEXT, lastupdate INTEGER, author VARCHAR(64));
		CREATE TABLE tblComments (id INTEGER PRIMARY KEY, bid INTEGER, content TEXT, lastupdate INTEGER, author VARCHAR(64), email VARCHAR(64), option INTEGER);
	';
	/**
	 * @var string id of this module
	 */
	private $_id='';
	private $_file=null;
	/**
	 * @var SQLiteDatabase the sqlite database instance
	 */
	private $_db=null;
	private $_initialized=false;

	/**
	 * Destructor.
	 * Disconnect the db connection.
	 */
	public function __destruct()
	{
		$this->_db=null;
		parent::__destruct();
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It checks if the DbFile
	 * property is set, and creates a SQLiteDatabase instance for it.
	 * If the database or the tables do not exist, they will be created.
	 * @param IApplication Prado application, can be null
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException DbFile is set invalid
	 */
	public function init($application,$config)
	{
		if(!function_exists('sqlite_open'))
			throw new TConfigurationException('SQLite extension required.');
		if($this->_file===null)
			throw new TConfigurationException('SQLite db file required.');
		if(($fname=Prado::getPathOfNamespace($this->_file,self::DB_FILE_EXT))===null)
			throw new TConfigurationException('SQLite db file invalid: '.$this->_file);
		$error='';
		if(($this->_db=new SQLiteDatabase($fname,0666,$error))===false)
			throw new TConfigurationException('SQLite db connection failed: ',$error);
		$res=$this->_db->query('SELECT * FROM sqlite_master WHERE tbl_name=\'tblBlogs\' AND type=\'table\'');
		if($res===false || $res->numRows()===0 && $this->_db->query(self::DB_SCHEMA)===false)
			throw new TConfigurationException('SQLite db table creation failed: '.sqlite_error_string(sqlite_last_error()));
		$this->_initialized=true;
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return string database file path (in namespace form)
	 */
	public function getDbFile()
	{
		return $this->_file;
	}

	/**
	 * @param string database file path (in namespace form)
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setDbFile($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('DbFile cannot be modified after the module is initialized.');
		else
			$this->_file=$value;
	}

	public function getBlogsByTime($time)
	{
	}
}

class Blog
{
	public $id;
	public $title;
	public $author;
	public $content;
	public $lastupdate;
	public $comments;
}

class Comment
{
	public $id;
	public $bid;
	public $author;
	public $content;
	public $lastupdate;
}

?>