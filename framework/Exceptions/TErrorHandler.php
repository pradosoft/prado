<?php
/**
 * TErrorHandler class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Exceptions
 */

/**
 * TErrorHandler class
 *
 * TErrorHandler handles all PHP user errors and exceptions generated during
 * servicing user requests. It displays these errors using different templates
 * and if possible, using languages preferred by the client user.
 * Note, PHP parsing errors cannot be caught and handled by TErrorHandler.
 *
 * The templates used to format the error output are stored under System.Exceptions.
 * You may choose to use your own templates, should you not like the templates
 * provided by Prado. Simply set {@link setErrorTemplatePath ErrorTemplatePath}
 * to the path (in namespace format) storing your own templates.
 *
 * There are two sets of templates, one for errors to be displayed to client users
 * (called external errors), one for errors to be displayed to system developers
 * (called internal errors). The template file name for the former is
 * <b>error[StatusCode][-LanguageCode].html</b>, and for the latter it is
 * <b>exception[-LanguageCode].html</b>, where StatusCode refers to response status
 * code (e.g. 404, 500) specified when {@link THttpException} is thrown,
 * and LanguageCode is the client user preferred language code (e.g. en, zh, de).
 * The templates <b>error.html</b> and <b>exception.html</b> are default ones
 * that are used if no other appropriate templates are available.
 * Note, these templates are not Prado control templates. They are simply
 * html files with keywords (e.g. %%ErrorMessage%%, %%Version%%)
 * to be replaced with the corresponding information.
 *
 * By default, TErrorHandler is registered with {@link TApplication} as the
 * error handler module. It can be accessed via {@link TApplication::getErrorHandler()}.
 * You seldom need to deal with the error handler directly. It is mainly used
 * by the application object to handle errors.
 *
 * TErrorHandler may be configured in application configuration file as follows
 * <module id="error" type="TErrorHandler" ErrorTemplatePath="System.Exceptions" />
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Exceptions
 * @since 3.0
 */
class TErrorHandler extends TModule
{
	/**
	 * error template file basename
	 */
	const ERROR_FILE_NAME='error';
	/**
	 * exception template file basename
	 */
	const EXCEPTION_FILE_NAME='exception';
	/**
	 * number of lines before and after the error line to be displayed in case of an exception
	 */
	const SOURCE_LINES=12;

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
		parent::init($application,$config);

		$this->_application=$application;
		$application->setErrorHandler($this);
	}

	/**
	 * @return string the directory containing error template files.
	 */
	public function getErrorTemplatePath()
	{
		return $this->_templatePath;
	}

	/**
	 * Sets the path storing all error and exception template files.
	 * The path must be in namespace format, such as System.Exceptions (which is the default).
	 * @param string template path in namespace format
	 * @throws TConfigurationException if the template path is invalid
	 */
	public function setErrorTemplatePath($value)
	{
		if(($templatePath=Prado::getPathOfNamespace($this->_templatePath))!==null && is_dir($templatePath))
			$this->_templatePath=$templatePath;
		else
			throw new TConfigurationException('errorhandler_errortemplatepath_invalid',$value);
	}

	/**
	 * Handles PHP user errors and exceptions.
	 * This is the event handler responding to the <b>Error</b> event
	 * raised in {@link TApplication}.
	 * The method mainly uses appropriate template to display the error/exception.
	 * It terminates the application immediately after the error is displayed.
	 * @param mixed sender of the event
	 * @param mixed event parameter (if the event is raised by TApplication, it refers to the exception instance)
	 */
	public function handleError($sender,$param)
	{
		static $handling=false;
		// We need to restore error and exception handlers,
		// because within error and exception handlers, new errors and exceptions
		// cannot be handled properly by PHP
		restore_error_handler();
		restore_exception_handler();
		// ensure that we do not enter infinite loop of error handling
		if($handling)
			$this->handleRecursiveError($param);
		else
		{
			$handling=true;
			if(($response=Prado::getApplication()->getResponse())!==null)
				$response->clear();
			if(!headers_sent())
				header('Content-Type: text/html; charset=UTF-8');
			if($param instanceof THttpException)
				$this->handleExternalError($param->getStatusCode(),$param);
			else if(Prado::getApplication()->getMode()===TApplication::STATE_DEBUG)
				$this->displayException($param);
			else
				$this->handleExternalError(500,$param);
		}
		exit(1);
	}

	/**
	 * Displays error to the client user.
	 * THttpException and errors happened when the application is in <b>Debug</b>
	 * mode will be displayed to the client user.
	 * @param integer response status code
	 * @param Exception exception instance
	 */
	protected function handleExternalError($statusCode,$exception)
	{
		if(!($exception instanceof THttpException))
			error_log($exception->__toString());
		if($this->_templatePath===null)
			$this->_templatePath=Prado::getFrameworkPath().'/Exceptions/templates';
		$base=$this->_templatePath.'/'.self::ERROR_FILE_NAME;
		$lang=Prado::getPreferredLanguage();
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
		$tokens=array(
			'%%StatusCode%%' => "$statusCode",
			'%%ErrorMessage%%' => htmlspecialchars($exception->getMessage()),
			'%%ServerAdmin%%' => $serverAdmin,
			'%%Version%%' => $_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
			'%%Time%%' => @strftime('%Y-%m-%d %H:%M',time())
		);
		echo strtr($content,$tokens);
	}

	/**
	 * Handles error occurs during error handling (called recursive error).
	 * THttpException and errors happened when the application is in <b>Debug</b>
	 * mode will be displayed to the client user.
	 * Error is displayed without using existing template to prevent further errors.
	 * @param Exception exception instance
	 */
	protected function handleRecursiveError($exception)
	{
		if(Prado::getApplication()->getMode()===TApplication::STATE_DEBUG)
		{
			echo "<html><head><title>Recursive Error</title></head>\n";
			echo "<body><h1>Recursive Error</h1>\n";
			echo "<pre>".$exception->__toString()."</pre>\n";
			echo "</body></html>";
		}
		else
		{
			error_log("Error happened while processing an existing error:\n".$param->__toString());
			header('HTTP/1.0 500 Internal Error');
		}
	}


	/**
	 * Displays exception information.
	 * Exceptions are displayed with rich context information, including
	 * the call stack and the context source code.
	 * This method is only invoked when application is in <b>Debug</b> mode.
	 * @param Exception exception instance
	 */
	protected function displayException($exception)
	{
		$lines=file($exception->getFile());
		$errorLine=$exception->getLine();
		$beginLine=$errorLine-self::SOURCE_LINES>=0?$errorLine-self::SOURCE_LINES:0;
		$endLine=$errorLine+self::SOURCE_LINES<=count($lines)?$errorLine+self::SOURCE_LINES:count($lines);

		$source='';
		for($i=$beginLine-1;$i<$endLine;++$i)
		{
			if($i===$errorLine-1)
			{
				$line=htmlspecialchars(sprintf("%04d: %s",$i+1,str_replace("\t",'    ',$lines[$i])));
				$source.="<div class=\"error\">".$line."</div>";
			}
			else
				$source.=htmlspecialchars(sprintf("%04d: %s",$i+1,str_replace("\t",'    ',$lines[$i])));
		}

		$tokens=array(
			'%%ErrorType%%' => get_class($exception),
			'%%ErrorMessage%%' => htmlspecialchars($exception->getMessage()),
			'%%SourceFile%%' => htmlspecialchars($exception->getFile()).' ('.$exception->getLine().')',
			'%%SourceCode%%' => $source,
			'%%StackTrace%%' => htmlspecialchars($exception->getTraceAsString()),
			'%%Version%%' => $_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
			'%%Time%%' => @strftime('%Y-%m-%d %H:%M',time())
		);
		$lang=Prado::getPreferredLanguage();
		$exceptionFile=Prado::getFrameworkPath().'/Exceptions/templates/'.self::EXCEPTION_FILE_NAME.'-'.$lang.'.html';
		if(!is_file($exceptionFile))
			$exceptionFile=Prado::getFrameworkPath().'/Exceptions/templates/'.self::EXCEPTION_FILE_NAME.'.html';
		if(($content=@file_get_contents($exceptionFile))===false)
			die("Unable to open exception template file '$exceptionFile'.");
		echo strtr($content,$tokens);
	}
}

?>