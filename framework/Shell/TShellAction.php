<?php
/**
 * TShellAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell
 */

namespace Prado\Shell;

use Prado\IO\ITextWriter;
use Prado\Prado;

/**
 * Base class for command line actions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Shell
 * @since 3.0.5
 */
abstract class TShellAction extends \Prado\TComponent
{
	protected $action;
	protected $parameters;
	protected $optional;
	protected $description;
	
	protected $_outWriter;
	
	/**
	 * @return ITextWriter the writer for the class
	 */
	public function getWriter(): ITextWriter
	{
		return $this->_outWriter;
	}
	
	/**
	 * @@param ITextWriter $writer the writer for the class
	 */
	public function setWriter(ITextWriter $writer)
	{
		$this->_outWriter = $writer;
	}
	
	
	
	/**
	 * Execute the action.
	 * @param array $args command line parameters
	 * @return bool true if action was handled
	 */
	abstract public function performAction($args);

	/**
	 * Creates a directory and sets its mode
	 * @param string $dir directory name
	 * @param int $mask directory mode mask suitable for chmod()
	 */
	protected function createDirectory($dir, $mask)
	{
		if (!is_dir($dir)) {
			mkdir($dir);
			$this->_outWriter->writeLine("creating $dir");
		}
		if (is_dir($dir)) {
			chmod($dir, $mask);
		}
	}

	/**
	 * Creates a file and fills it with content
	 * @param string $filename file name
	 * @param int $content file contents
	 */
	protected function createFile($filename, $content)
	{
		if (!is_file($filename)) {
			file_put_contents($filename, $content);
			$this->_outWriter->writeLine("creating $filename");
		}
	}

	/**
	 * Checks if specified parameters are suitable for the specified action
	 * @param array $args parameters
	 * @return bool
	 */
	public function isValidAction($args)
	{
		return 0 == strcasecmp($args[0], $this->action) &&
			count($args) - 1 >= count($this->parameters);
	}

	/**
	 * @return string
	 */
	public function renderHelp()
	{
		$params = [];
		foreach ($this->parameters as $v) {
			$params[] = '<' . $v . '>';
		}
		$parameters = implode(' ', $params);
		$options = [];
		foreach ($this->optional as $v) {
			$options[] = '[' . $v . ']';
		}
		$optional = (strlen($parameters) ? ' ' : '') . implode(' ', $options);
		
		$description = $this->getWriter()->wrapText($this->description, 5);
		
		$action = $this->getWriter()->format($this->action, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$parameters = $this->getWriter()->format($parameters, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$optional = $this->getWriter()->format($optional, [TShellWriter::BLUE]);
		$description = $this->getWriter()->format($description, TShellWriter::DARK_GRAY);
		return <<<EOD
 - {$action} {$parameters}{$optional}
     {$description}
EOD;
	}

	/**
	 * Initalize a Prado application inside the specified directory
	 * @param string $directory directory name
	 * @return false|TApplication
	 */
	protected function initializePradoApplication($directory)
	{
		$_SERVER['SCRIPT_FILENAME'] = $directory . '/index.php';
		$app_dir = realpath($directory . '/protected/');
		if ($app_dir !== false && is_dir($app_dir)) {
			if (Prado::getApplication() === null) {
				$app = new TShellApplication($app_dir);
				$app->run();
				$dir = substr(str_replace(realpath('./'), '', $app_dir), 1);
				TShellInterpreter::getInstance()->printGreeting();
				$this->_outWriter->writeLine('** Loaded PRADO appplication in directory "' . $dir . "\".");
			}

			return Prado::getApplication();
		} else {
			TShellInterpreter::getInstance()->printGreeting();
			$this->_outWriter->writeError('Unable to load PRADO application in directory "' . $directory . "\".");
		}
		return false;
	}

	/**
	 * @param string $app_dir application directory
	 * @return false|string
	 */
	protected function getAppConfigFile($app_dir)
	{
		if (false !== ($xml = realpath($app_dir . '/application.xml')) && is_file($xml)) {
			return $xml;
		}
		if (false !== ($xml = realpath($app_dir . '/protected/application.xml')) && is_file($xml)) {
			return $xml;
		}
		if (false !== ($php = realpath($app_dir . '/application.php')) && is_file($php)) {
			return $php;
		}
		if (false !== ($php = realpath($app_dir . '/protected/application.php')) && is_file($php)) {
			return $php;
		}
		$this->_outWriter->writeError('Unable to find application.xml or application.php in ' . $app_dir . ".");
		return false;
	}
}
