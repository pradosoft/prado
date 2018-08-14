<?php

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Exceptions\TException;
use Prado\Prado;
use Prado\TPropertyValue;
use SimpleXMLElement;

/**
 * TSqlMapException is the base exception class for all SqlMap exceptions.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */

class TSqlMapException extends TException
{
	/**
	 * Constructor, similar to the parent constructor. For parameters that
	 * are of SimpleXmlElement, the tag name and its attribute names and values
	 * are expanded into a string.
	 * @param mixed $errorMessage
	 */
	public function __construct($errorMessage)
	{
		$this->setErrorCode($errorMessage);
		$errorMessage = $this->translateErrorMessage($errorMessage);
		$args = func_get_args();
		array_shift($args);
		$n = count($args);
		$tokens = [];
		for ($i = 0; $i < $n; ++$i) {
			if ($args[$i] instanceof SimpleXMLElement) {
				$tokens['{' . $i . '}'] = $this->implodeNode($args[$i]);
			} else {
				$tokens['{' . $i . '}'] = TPropertyValue::ensureString($args[$i]);
			}
		}
		parent::__construct(strtr($errorMessage, $tokens));
	}

	/**
	 * @param SimpleXmlElement $node node
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
