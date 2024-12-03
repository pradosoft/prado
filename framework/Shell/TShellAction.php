<?php

/**
 * TShellAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Shell;

use Prado\Prado;

/**
 * Base class for command line actions.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com> shell refactor
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0.5
 */
abstract class TShellAction extends \Prado\TComponent
{
	protected $action;
	protected $defaultMethod = 0;
	protected $methods;
	protected $parameters;
	protected $optional;
	protected $description;

	protected $_outWriter;

	/**
	 * @return TShellApplication current application instance
	 */
	public function getApplication()
	{
		return Prado::getApplication();
	}

	/**
	 * @return TShellWriter the writer for the class
	 */
	public function getWriter(): TShellWriter
	{
		return $this->_outWriter;
	}

	/**
	 * @param TShellWriter $writer the writer for the class
	 */
	public function setWriter(TShellWriter $writer)
	{
		$this->_outWriter = $writer;
	}

	/**
	 * @return string the command action for the class
	 */
	public function getAction(): string
	{
		return $this->action;
	}

	/**
	 * @param string $action the command action for the class
	 */
	public function setAction(string $action)
	{
		$this->action = $action;
	}

	/**
	 * Properties for the action set by parameter
	 * @param string $actionID the action being executed
	 * @return array properties for the $actionID
	 */
	public function options($actionID): array
	{
		return [];
	}

	/**
	 * Aliases for the properties to be set by parameter
	 * @return array<string, string> alias => property for the $actionID
	 */
	public function optionAliases(): array
	{
		return [];
	}

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
		if (preg_match("/^{$this->action}(?:\\/([-\w\d]*))?$/", $args[0] ?? '', $match)) {
			if (isset($match[1]) && $match[1]) {
				$i = array_flip($this->methods)[$match[1]] ?? null;
				if ($i === null) {
					return null;
				}
			} else {
				$i = $this->defaultMethod;
				$match[1] = $this->methods[$i];
			}

			$params = ($this->parameters[$i] === null) ? [] : $this->parameters[$i];
			$params = is_array($params) ? $params : [$this->parameters[$i]];
			if (count($args) - 1 < count($params)) {
				return null;
			}
			return $match[1];
		}
		return null;
	}

	/**
	 * renders help for the command
	 * @param string $cmd
	 */
	public function renderHelpCommand($cmd)
	{
		$this->_outWriter->write("\nusage: ");
		$this->_outWriter->writeLine("php prado-cli.php {$this->action}/<action>", [TShellWriter::BLUE, TShellWriter::BOLD]);
		$this->_outWriter->writeLine("\nexample: php prado-cli.php {$this->action}/{$this->methods[0]}\n");
		$this->_outWriter->writeLine("The following actions are available:");
		$this->_outWriter->writeLine();
		foreach ($this->methods as $i => $method) {
			$params = [];
			if ($this->parameters[$i]) {
				$parameters = is_array($this->parameters[$i]) ? $this->parameters[$i] : [$this->parameters[$i]];
				foreach ($parameters as $v) {
					$params[] = '<' . $v . '>';
				}
			}
			$parameters = implode(' ', $params);
			$options = [];
			if ($this->optional[$i]) {
				$optional = is_array($this->optional[$i]) ? $this->optional[$i] : [$this->optional[$i]];
				foreach ($optional as $v) {
					$options[] = '[' . $v . ']';
				}
			}
			$optional = (strlen($parameters) ? ' ' : '') . implode(' ', $options);

			$description = $this->getWriter()->wrapText($this->description[$i + 1], 10);
			$parameters = $this->getWriter()->format($parameters, [TShellWriter::BLUE, TShellWriter::BOLD]);
			$optional = $this->getWriter()->format($optional, [TShellWriter::BLUE]);
			$description = $this->getWriter()->format($description, TShellWriter::DARK_GRAY);

			$this->_outWriter->write('  ');
			$this->_outWriter->writeLine($this->action . '/' . $method . ' ' . $parameters . $optional, [TShellWriter::BLUE, TShellWriter::BOLD]);
			$this->_outWriter->writeLine('         ' . $description);
			$this->_outWriter->writeLine();
		}
	}

	/**
	 * Renders General Help for the command
	 * @return string
	 */
	public function renderHelp()
	{
		$action = $this->getWriter()->format($this->action, [TShellWriter::BLUE, TShellWriter::BOLD]);

		$str = '';
		$length = 31;
		$str .= $this->getWriter()->pad("  {$action}", $length);
		$description = $this->getWriter()->wrapText($this->description[0], $length);
		$str .= $description . PHP_EOL;
		foreach ($this->methods as $i => $method) {
			$str .= $this->getWriter()->pad("     {$this->action}/$method", $length);
			$description = $this->getWriter()->wrapText($this->description[$i + 1], $length);
			$str .= $this->getWriter()->format($description, TShellWriter::DARK_GRAY) . PHP_EOL;
		}
		return $str;
	}
}
