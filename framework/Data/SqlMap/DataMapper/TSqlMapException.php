<?php

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Exceptions\TException;
use Prado\Prado;
use SimpleXMLElement;

/**
 * TSqlMapException is the base exception class for all SqlMap exceptions.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSqlMapException extends TException
{
	/**
	 * Constructor. The arguments follow {@see \Prado\Exceptions\TException::__construct()}.
	 * - `new TSqlMapException('message_code', $param0, ...)` → error code 0.
	 * - `new TSqlMapException($errorCode, 'message_code', $param0, ...)` → integer error code.
	 * - A trailing {@see \Throwable} argument is the previous exception in the chain.
	 * - A {@see \SimpleXMLElement} parameter expands to its tag name with attribute names and values.
	 * @param int|string $errorCode the integer error code, or the message code in the old style.
	 * @param mixed $errorMessage the message code when $errorCode is an integer, otherwise
	 *   the first message parameter.
	 * @param mixed ...$args the remaining message parameters, optionally followed by the
	 *   previous exception.
	 */
	public function __construct($errorCode, $errorMessage = null, ...$args)
	{
		if (!is_int($errorCode)) {
			if (func_num_args() > 1) {
				array_unshift($args, $errorMessage);
			}
			$errorMessage = $errorCode;
			$errorCode = 0;
		}
		foreach ($args as $i => $arg) {
			if ($arg instanceof SimpleXMLElement) {
				$args[$i] = $this->implodeNode($arg);
			}
		}
		parent::__construct($errorCode, $errorMessage, ...$args);
	}

	/**
	 * @param \SimpleXmlElement $node node
	 * @return string tag name and attribute names and values.
	 */
	protected function implodeNode($node)
	{
		$attributes = [];
		foreach ($node->attributes() as $k => $v) {
			$attributes[] = $k . '="' . (string) $v . '"';
		}
		return '<' . $node->getName() . ' ' . implode(' ', $attributes) . '>';
	}

	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		$lang = Prado::getPreferredLanguage();
		$dir = __DIR__;
		$msgFile = $dir . '/messages-' . $lang . '.txt';
		if (!is_file($msgFile)) {
			$msgFile = $dir . '/messages.txt';
		}
		return $msgFile;
	}
}
