<?php

class TSqlMapException extends TException
{
	/**
	 * Constructor.
	 * @param string error message. This can be a string that is listed
	 * in the message file. If so, the message in the preferred language
	 * will be used as the error message. Any rest parameters will be used
	 * to replace placeholders ({0}, {1}, {2}, etc.) in the message.
	 */
	public function __construct($errorMessage)
	{
		$this->setErrorCode($errorMessage);
		$errorMessage=$this->translateErrorMessage($errorMessage);
		$args=func_get_args();
		array_shift($args);
		$n=count($args);
		$tokens=array();
		for($i=0;$i<$n;++$i)
		{
			if($args[$i] instanceof SimpleXmlElement)
				$tokens['{'.$i.'}']=$this->implodeNode($args[$i]);
			else
				$tokens['{'.$i.'}']=TPropertyValue::ensureString($args[$i]);
		}
		parent::__construct(strtr($errorMessage,$tokens));
	}

	protected function implodeNode($node)
	{
		$attributes=array();
		foreach($node->attributes() as $k=>$v)
			$attributes[]=$k.'="'.(string)$v.'"';
		return '<'.$node->getName().' '.implode(' ',$attributes).'>';
	}

	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		$lang=Prado::getPreferredLanguage();
		$dir=dirname(__FILE__);
		$msgFile=$dir.'/messages-'.$lang.'.txt';
		if(!is_file($msgFile))
			$msgFile=$dir.'/messages.txt';
		return $msgFile;
	}
}

class TSqlMapConfigurationException extends TSqlMapException
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

class TSqlMapExecutionException extends TSqlMapException
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

class TSqlMapUndefinedException extends TSqlMapException
{

}

class TSqlMapDuplicateException extends TSqlMapException
{
}

class TSqlMapConnectionException extends TSqlMapException
{
}

class TInvalidPropertyException extends TSqlMapException
{

}
?>