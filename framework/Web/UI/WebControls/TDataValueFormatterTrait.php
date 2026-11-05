<?php

/**
 * TDataValueFormatterTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TDataValueFormatterTrait trait.
 *
 * TDataValueFormatterTrait formats bound data values for list controls and datagrid columns.
 * A format is a format string or a callable; {@see formatDataValue()} describes each form.
 *
 * {@see getDataFormat()} and {@see setDataFormat()} back the format properties.  A format
 * string is kept in viewstate.  A callable is kept for the current request only, so it is
 * set on every request, for example in `onInit`.
 *
 * The using class defines the `DATA_FORMAT_EXPRESSION_ERROR` constant, the error code thrown
 * for an invalid `#` expression.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TDataValueFormatterTrait
{
	/**
	 * @var array<string, callable> the callable formats by viewstate key, kept for the current request.
	 */
	private array $_dataFormatters = [];

	/**
	 * Returns a format property.
	 * @param string $key the viewstate key of the format property.
	 * @return callable|string the callable format, or the format string; defaults to ''.
	 */
	protected function getDataFormat(string $key): mixed
	{
		return $this->_dataFormatters[$key] ?? $this->getViewState($key, '');
	}

	/**
	 * Sets a format property.  A callable, other than a function name string, is kept for
	 * the current request; a format string is kept in viewstate.
	 * @param string $key the viewstate key of the format property.
	 * @param callable|string $value the callable format, or the format string.
	 */
	protected function setDataFormat(string $key, mixed $value): void
	{
		if ($this->isDataFormatCallable($value)) {
			$this->_dataFormatters[$key] = $value;
			$this->setViewState($key, '', '');
		} else {
			unset($this->_dataFormatters[$key]);
			$this->setViewState($key, $value, '');
		}
	}

	/**
	 * @param mixed $format the format.
	 * @return bool whether the format is a callable other than a function name string.
	 */
	protected function isDataFormatCallable(mixed $format): bool
	{
		return !is_string($format) && is_callable($format);
	}

	/**
	 * Formats a data value.
	 *
	 * | Format | Result |
	 * |---|---|
	 * | callable | the callable's return value for the data value |
	 * | `''` | the data value as a string |
	 * | starts with `#` | the PHP expression after `#`, with `{0}` and `$value` as the data value |
	 * | other string | `sprintf($format, $value)` |
	 *
	 * A `#` expression is PHP code evaluated by {@see \Prado\TComponent::evaluateExpression()}
	 * as `fn ($value) => <expression>`.  It comes from templates or application code and never
	 * contains request data.  A callable formats a value without evaluating code.
	 * @param callable|string $format the format.
	 * @param mixed $value the data to be formatted.
	 * @throws TInvalidDataValueException if the `#` expression is invalid or throws.
	 * @return mixed the formatted result.
	 */
	protected function formatDataValue($format, $value)
	{
		if ($this->isDataFormatCallable($format)) {
			return $format($value);
		} elseif ($format === '') {
			return TPropertyValue::ensureString($value);
		} elseif ($format[0] === '#') {
			$expression = strtr(substr($format, 1), ['{0}' => '$value']);
			try {
				$formatter = $this->evaluateExpression('fn ($value) => ' . $expression);
				return $formatter($value);
			} catch (\Throwable $e) {
				throw new TInvalidDataValueException(static::DATA_FORMAT_EXPRESSION_ERROR, $this::class, $expression, ($e->getPrevious() ?? $e)->getMessage(), $e);
			}
		} else {
			return sprintf($format, $value);
		}
	}
}
