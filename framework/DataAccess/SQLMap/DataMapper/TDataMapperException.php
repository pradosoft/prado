<?php

class TDataMapperException extends TException
{
	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		$lang=Prado::getPreferredLanguage();
		$msgFile=Prado::getFrameworkPath().'/DataAccess/SQLMap/DataMapper/messages-'.$lang.'.txt';
		if(!is_file($msgFile))
			$msgFile=Prado::getFrameworkPath().'/DataAccess/SQLMap/DataMapper/messages.txt';
		return $msgFile;
	}
}

class TSqlMapConfigurationException extends TDataMapperException
{

}

class TUndefinedAttributeException extends TSqlMapConfigurationException
{
	public function __construct($attr, $node, $object, $file)
	{
		parent::__construct(
			'sqlmap_undefined_attribute', get_class($object), $attr,
			htmlentities($node->asXml()),$file);
	}
}

class TSqlMapExecutionException extends TDataMapperException
{
}

class TSqlMapQueryExecutionException extends TSqlMapExecutionException
{
	protected $parent;
	public function __construct($statement, $exception)
	{
		$this->parent = $exception;
		parent::__construct('sqlmap_query_execution_error', 
			$statement->getID(), $exception->getMessage());
	}
}

class TSqlMapUndefinedException extends TDataMapperException
{
	
}

class TSqlMapDuplicateException extends TDataMapperException
{
}

class TSqlMapConnectionException extends TDataMapperException
{
}

class TInvalidPropertyException extends TDataMapperException
{

}
?>