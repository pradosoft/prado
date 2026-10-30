<?php

/**
 * TInputValidationException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TInputValidationException class
 *
 * TInputValidationException is thrown by {@see TInputValidator::validate()} when
 * the input data fails one or more rules. {@see getErrors()} returns the failures
 * keyed by field name; each value is a list of message strings.
 *
 * The exception message is the `inputvalidator_invalid` framework message with
 * the names of the failing fields as its parameter.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInputValidationException extends TInvalidDataValueException
{
	/** @var array<string, string[]> field-level error messages keyed by field name */
	private array $_errors = [];

	/**
	 * Constructor.
	 * @param array<string, string[]> $errors Field-level errors keyed by field name.
	 * @param ?\Throwable $previous The previous exception for chaining.
	 */
	public function __construct(array $errors = [], ?\Throwable $previous = null)
	{
		$this->setErrorsDirect($errors);
		$fields = implode(', ', array_keys($errors));
		if ($previous === null) {
			parent::__construct('inputvalidator_invalid', $fields);
		} else {
			parent::__construct('inputvalidator_invalid', $fields, $previous);
		}
	}

	/**
	 * @return array<string, string[]> field-level errors keyed by field name.
	 */
	protected function getErrorsDirect(): array
	{
		return $this->_errors;
	}

	/**
	 * @param array<string, string[]> $value field-level errors keyed by field name.
	 */
	protected function setErrorsDirect(array $value): void
	{
		$this->_errors = $value;
	}

	/**
	 * @return array<string, string[]> field-level errors keyed by field name; each value is a list of messages.
	 */
	public function getErrors(): array
	{
		return $this->getErrorsDirect();
	}
}
