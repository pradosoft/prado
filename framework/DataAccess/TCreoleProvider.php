<?php

Prado::using('System.DataAccess.TDatabaseProvider');

Prado::using('System.DataAccess.creole.*');

class TCreoleProvider extends TDatabaseProvider
{
	private $_connection = null;

	public function getConnection()
	{
		if(is_null($this->_connection))
			$this->_connection = new TCreoleConnection($this);
		return $this->_connection;
	}

	public function getConnectionString()
	{
		if(strlen(parent::getConnectionString()) > 0)
			return parent::getConnectionString();
		else
			return $this->generateConnectionString();
	}

	protected function generateConnectionString()
	{
		$driver = $this->getDriver();
		$user = $this->getUsername();
		$pass = $this->getPassword();
		$host = $this->getHost();
		$database = $this->getDatabase();

		$pass = strlen($pass) > 0 ? ':'.$pass : '';
		$username_password = strlen($user) > 0 ? $user.$pass.'@' : '';
		$database = strlen($database) > 0 ? '/'.$database : '';

		return "{$driver}://{$username_password}{$host}{$database}";
	}
}

class TCreoleConnection extends TDbConnection
{
	private $_connection = null;

	protected function beginDbTransaction()
	{
	}

	/**
	 * Closes the connection to the database.
	 */
	public function close()
	{
		$this->_connection->close();
	}

	public function prepare($statement)
	{
		return $this->_connection->prepareStatement($statement);
	}

	//public function execute($sql,

	/**
	 * Opens a database connection with settings provided in the ConnectionString.
	 */
	public function open()
	{
		if(is_null($this->_connection))
		{
			$connectionString = $this->getProvider()->getConnectionString();
			if(strlen($connectionString) < 1 || strcmp($connectionString,'://') === 0)
				throw new TDbConnectionException('db_driver_required');
			$class = 'System.DataAccess.creole.creole.Creole';
			$creole = Prado::createComponent($class);
			$this->_connection = $creole->getConnection($connectionString);
		}
		return $this->_connection;
	}
}

?>