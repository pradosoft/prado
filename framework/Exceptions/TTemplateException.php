<?php
/**
 * Exception classes file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Exceptions
 */

namespace Prado\Exceptions;

use Prado\TPropertyValue;

/**
 * TTemplateException class
 *
 * TTemplateException represents an exception caused by invalid template syntax.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Exceptions
 * @since 3.1
 */
class TTemplateException extends TConfigurationException
{
	private $_template = '';
	private $_lineNumber = 0;
	private $_fileName = '';

	/**
	 * @return string the template source code that causes the exception. This is empty if {@link getTemplateFile TemplateFile} is not empty.
	 */
	public function getTemplateSource()
	{
		return $this->_template;
	}

	/**
	 * @param string $value the template source code that causes the exception
	 */
	public function setTemplateSource($value)
	{
		$this->_template = $value;
	}

	/**
	 * @return string the template file that causes the exception. This could be empty if the template is an embedded template. In this case, use {@link getTemplateSource TemplateSource} to obtain the actual template content.
	 */
	public function getTemplateFile()
	{
		return $this->_fileName;
	}

	/**
	 * @param string $value the template file that causes the exception
	 */
	public function setTemplateFile($value)
	{
		$this->_fileName = $value;
	}

	/**
	 * @return int the line number at which the template has error
	 */
	public function getLineNumber()
	{
		return $this->_lineNumber;
	}

	/**
	 * @param int $value the line number at which the template has error
	 */
	public function setLineNumber($value)
	{
		$this->_lineNumber = TPropertyValue::ensureInteger($value);
	}
}
