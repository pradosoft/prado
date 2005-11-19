<?php

class TErrorHandler extends TComponent implements IModule
{
	const ERROR_FILE_NAME='error';
	const EXCEPTION_FILE_NAME='exception';

	/**
	 * @var string module ID
	 */
	private $_id;
	/**
	 * @var TApplication application instance
	 */
	private $_application;
	/**
	 * @var string error template directory
	 */
	private $_templatePath=null;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application,$config)
	{
		$this->_application=$application;
		$application->attachEventHandler('Error',array($this,'handleError'));
		$application->setErrorHandler($this);
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

	public function getErrorTemplatePath()
	{
		return $this->_templatePath;
	}

	public function setErrorTemplatePath($value)
	{
		if(($templatePath=Prado::getPathOfNamespace($this->_templatePath))!==null && is_dir($templatePath))
			$this->_templatePath=$templatePath;
		else
			throw new TConfigurationException('errorhandler_errortemplatepath_invalid',$value);
	}

	public function handleError($sender,$param)
	{
		static $handling=false;
		// We need to restore error and exception handlers,
		// otherwise because errors occured in error handler
		// will not be handled properly.
		restore_error_handler();
		restore_exception_handler();
		if($handling)	// ensure that we do not enter infinite loop of error handling
			$this->handleRecursiveError($param);
		else
		{
			$handling=true;
			if(($response=Prado::getApplication()->getResponse())!==null)
				$response->clear();
			if($param instanceof THttpException)
				$this->handleExternalError($param->getStatusCode(),$param);
			else if(Prado::getApplication()->getMode()==='Debug')
				$this->displayException($param);
			else
				$this->handleExternalError(500,$param);
		}
		exit(1);
	}

	protected function handleExternalError($statusCode,$exception)
	{
		if(!($exception instanceof THttpException))
			error_log($exception->__toString());
		if($this->_templatePath===null)
			$this->_templatePath=dirname(__FILE__);
		$base=$this->_templatePath.'/'.self::ERROR_FILE_NAME;
		$lang=array_shift(explode('-',array_shift(Prado::getUserLanguages())));
		if(!empty($lang) && !ctype_alpha($lang))
			$lang='';
		if(is_file("$base$statusCode-$lang.html"))
			$errorFile="$base$statusCode-$lang.html";
		else if(is_file("$base$statusCode.html"))
			$errorFile="$base$statusCode.html";
		else if(is_file("$base-$lang.html"))
			$errorFile="$base-$lang.html";
		else
			$errorFile="$base.html";
		if(($content=@file_get_contents($errorFile))===false)
			die("Unable to open error template file '$errorFile'.");

		$serverAdmin=isset($_SERVER['SERVER_ADMIN'])?$_SERVER['SERVER_ADMIN']:'';
		$fields=array(
			'%%StatusCode%%',
			'%%ErrorMessage%%',
			'%%ServerAdmin%%',
			'%%Version%%',
			'%%Time%%'
		);
		$values=array(
			"$statusCode",
			htmlspecialchars($exception->getMessage()),
			$serverAdmin,
			$_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
			strftime('%Y-%m-%d %H:%m',time())
		);
		echo str_replace($fields,$values,$content);
	}

	protected function handleRecursiveError($exception)
	{
		if(Prado::getApplication()->getMode()==='Debug')
		{
			echo "<html><head><title>Recursive Error</title></head>\n";
			echo "<body><h1>Recursive Error</h1>\n";
			echo "<pre>".$exception."</pre>\n";
			echo "</body></html>";
		}
		else
		{
			error_log("Error happened while processing an existing error:\n".$param->__toString());
			header('HTTP/1.0 500 Internal Error');
		}
	}

	protected function displayException($exception)
	{
		$lines=file($exception->getFile());
		$errorLine=$exception->getLine();
		$beginLine=$errorLine-9>=0?$errorLine-9:0;
		$endLine=$errorLine+8<=count($lines)?$errorLine+8:count($lines);
		$source='';
		for($i=$beginLine;$i<$endLine;++$i)
		{
			if($i===$errorLine-1)
			{
				$line=highlight_string(sprintf("Line %4d: %s",$i+1,$lines[$i]),true);
				$source.="<div style=\"background-color: #ffeeee\">".$line."</div>";
			}
			else
				$source.=highlight_string(sprintf("Line %4d: %s",$i+1,$lines[$i]),true);
		}
		$fields=array(
			'%%ErrorType%%',
			'%%ErrorMessage%%',
			'%%SourceFile%%',
			'%%SourceCode%%',
			'%%StackTrace%%',
			'%%Version%%',
			'%%Time%%'
		);
		$values=array(
			get_class($exception),
			htmlspecialchars($exception->getMessage()),
			htmlspecialchars($exception->getFile()).' ('.$exception->getLine().')',
			$source,
			htmlspecialchars($exception->getTraceAsString()),
			$_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
			strftime('%Y-%m-%d %H:%m',time())
		);
		$lang=array_shift(explode('-',array_shift(Prado::getUserLanguages())));
		if(!empty($lang) && !ctype_alpha($lang))
			$lang='';
		$exceptionFile=dirname(__FILE__).'/'.self::EXCEPTION_FILE_NAME.'-'.$lang.'.html';
		if(!is_file($exceptionFile))
			$exceptionFile=dirname(__FILE__).'/'.self::EXCEPTION_FILE_NAME.'.html';
		if(($content=@file_get_contents($exceptionFile))===false)
			die("Unable to open exception template file '$exceptionFile'.");
		echo str_replace($fields,$values,$content);
	}
}

?>