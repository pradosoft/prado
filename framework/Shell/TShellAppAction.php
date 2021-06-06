<?php
/**
 * TShellAppAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell
 */

namespace Prado\Shell;

/**
 * Base class for command line Application actions.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @package Prado\Shell
 * @since 4.2.0
 */
abstract class TShellAppAction extends TShellAction
{
	/**
	 * Checks if specified parameters are suitable for the specified action
	 * @param array $args parameters
	 * @return bool
	 */
	public function isValidAction($args)
	{
		return isset($args[2]) && 0 == strcasecmp($args[2], $this->action) &&
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
		
		$action = $this->getWriter()->format(" app <directory> " . $this->action, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$parameters = $this->getWriter()->format($parameters, [TShellWriter::BLUE, TShellWriter::BOLD]);
		$optional = $this->getWriter()->format($optional, [TShellWriter::BLUE]);
		$description = $this->getWriter()->format($description, TShellWriter::DARK_GRAY);
		return <<<EOD
{$action} {$parameters}{$optional}
     {$description}
EOD;
	}
}
