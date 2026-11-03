<?php

/**
 * TInputValidator class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TComponent;

/**
 * TInputValidator class
 *
 * TInputValidator checks an input array against a rule set keyed by field name
 * and returns the validated, type-coerced values of the declared fields. Each
 * field's rules are a pipe-delimited string (`'required|integer|min:0'`) or a
 * list of rule strings (`['required', 'integer', 'min:0']`). A rule takes an
 * optional parameter after a colon.
 *
 * | Rule | Meaning |
 * |---|---|
 * | `required` | The field is present and not null, `''` or `[]`. |
 * | `nullable` | A present null value is kept as null and the other rules are skipped. |
 * | `string` | The value is a string. |
 * | `integer`, `int` | The value is an integer or an integer string (`FILTER_VALIDATE_INT`); coerced to int. |
 * | `float`, `numeric` | The value is numeric; coerced to float. |
 * | `boolean`, `bool` | The value is one of `true`, `false`, `1`, `0`, `'1'`, `'0'`, `'true'`, `'false'`; coerced to bool. |
 * | `array` | The value is an array. |
 * | `email` | The value passes `FILTER_VALIDATE_EMAIL`. |
 * | `url` | The value passes `FILTER_VALIDATE_URL`, with any scheme. |
 * | `min:N` | A string has at least N characters; a number is at least N; an array has at least N items. |
 * | `max:N` | A string has at most N characters; a number is at most N; an array has at most N items. |
 * | `in:a,b,c` | The scalar value equals one of the comma-separated options (trimmed). |
 *
 * The rules of a field run in order, each seeing the value the previous ones
 * coerced: `integer|max:150` compares the integer, while `max:150|integer`
 * measures the length of a numeric string. A missing field that is neither
 * `required` nor `nullable` is omitted from the result. An undeclared field in
 * the data is dropped. Every failing field is reported; {@see validate()} throws
 * {@see TInputValidationException} with all errors and {@see isValid()} stores
 * them in {@see getErrors()}.
 *
 * An unknown rule name, or a `min`, `max` or `in` rule without its parameter,
 * throws {@see TConfigurationException} so a typo surfaces during development.
 * {@see addRule()} registers a rule callable with the signature
 * `function (mixed &$value, ?string $parameter, string $field): ?string` that
 * returns null when the value is valid or the error message otherwise, and may
 * coerce `$value` by reference. A list of rules may hold such a callable in place
 * of a rule string.
 *
 * Error messages pass through {@see Prado::localize()} so an application's
 * translation catalogue can translate them.
 *
 * ```php
 * $validator = new TInputValidator();
 * $validator->addRule('even', function (mixed &$value, ?string $parameter, string $field): ?string {
 *     return ((int) $value) % 2 === 0 ? null : "The {$field} field must be even.";
 * });
 * $data = $validator->validate($input, [
 *     'name' => 'required|string|max:10',
 *     'count' => 'required|integer|even',
 *     'email' => 'nullable|email',
 * ]);
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInputValidator extends TComponent
{
	/** @var array<string, callable> rule callables keyed by rule name */
	private array $_rules = [];

	/** @var array<string, string[]> errors of the last run keyed by field name */
	private array $_errors = [];

	/** @var array validated data of the last run */
	private array $_validated = [];

	/**
	 * Constructor. Registers the built-in rules.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setRulesDirect($this->getDefaultRules());
	}

	/**
	 * Restores the built-in rules after unserialization; rules added with
	 * {@see addRule()} are not serialized.
	 */
	public function __wakeup()
	{
		parent::__wakeup();
		$this->setRulesDirect($this->getDefaultRules());
	}

	/**
	 * Leaves the rule callables out of serialization.
	 * @param array &$exprops the properties excluded from serialization.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_rules";
	}

	// ── Direct accessors ──────────────────────────────────────────────────────

	/**
	 * @return array<string, callable> rule callables keyed by rule name.
	 */
	protected function getRulesDirect(): array
	{
		return $this->_rules;
	}

	/**
	 * @param array<string, callable> $value rule callables keyed by rule name.
	 */
	protected function setRulesDirect(array $value): void
	{
		$this->_rules = $value;
	}

	/**
	 * @return array<string, string[]> errors of the last run keyed by field name.
	 */
	protected function getErrorsDirect(): array
	{
		return $this->_errors;
	}

	/**
	 * @param array<string, string[]> $value errors keyed by field name.
	 */
	protected function setErrorsDirect(array $value): void
	{
		$this->_errors = $value;
	}

	/**
	 * @return array validated data of the last run.
	 */
	protected function getValidatedDirect(): array
	{
		return $this->_validated;
	}

	/**
	 * @param array $value validated data.
	 */
	protected function setValidatedDirect(array $value): void
	{
		$this->_validated = $value;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * @return array<string, string[]> errors of the last {@see isValid()} or {@see validate()} run keyed by field name.
	 */
	public function getErrors(): array
	{
		return $this->getErrorsDirect();
	}

	/**
	 * @return array validated, coerced data of the last {@see isValid()} or {@see validate()} run.
	 */
	public function getValidated(): array
	{
		return $this->getValidatedDirect();
	}

	/**
	 * Validates the data against the rules and returns the validated fields.
	 * @param array $data Input data keyed by field name.
	 * @param array<string, array<callable|string>|string> $rules Rules keyed by field name.
	 * @throws TInputValidationException when a field fails a rule.
	 * @throws TConfigurationException when a rule name is not registered, or a rule lacks its parameter.
	 * @return array Validated and type-coerced data holding only the declared fields.
	 */
	public function validate(array $data, array $rules): array
	{
		if (!$this->isValid($data, $rules)) {
			throw new TInputValidationException($this->getErrorsDirect());
		}
		return $this->getValidatedDirect();
	}

	/**
	 * Validates the data against the rules without throwing on failure.
	 * {@see getErrors()} and {@see getValidated()} hold the outcome afterwards.
	 * @param array $data Input data keyed by field name.
	 * @param array<string, array<callable|string>|string> $rules Rules keyed by field name.
	 * @throws TConfigurationException when a rule name is not registered, or a rule lacks its parameter.
	 * @return bool true when every field passes its rules.
	 */
	public function isValid(array $data, array $rules): bool
	{
		$errors = [];
		$validated = [];

		foreach ($rules as $field => $fieldRules) {
			$field = (string) $field;
			$fieldRules = $this->normalizeRules($fieldRules);
			$present = array_key_exists($field, $data);
			$value = $data[$field] ?? null;

			if (!$present || $value === null) {
				if (in_array('required', $fieldRules, true)) {
					$errors[$field][] = $this->getMessage('The {field} field is required.', ['field' => $field]);
				} elseif ($present && in_array('nullable', $fieldRules, true)) {
					$validated[$field] = null;
				}
				continue;
			}

			$fieldErrors = $this->validateField($value, $fieldRules, $field);
			if ($fieldErrors === []) {
				$validated[$field] = $value;
			} else {
				$errors[$field] = $fieldErrors;
			}
		}

		$this->setErrorsDirect($errors);
		$this->setValidatedDirect($validated);
		return $errors === [];
	}

	/**
	 * Registers a rule, replacing any rule of the same name.
	 * @param string $name Rule name as written in a rule string.
	 * @param callable $rule `function (mixed &$value, ?string $parameter, string $field): ?string`;
	 *   returns null when valid or the error message otherwise.
	 */
	public function addRule(string $name, callable $rule): void
	{
		$rules = $this->getRulesDirect();
		$rules[$name] = $rule;
		$this->setRulesDirect($rules);
	}

	/**
	 * @param string $name Rule name.
	 * @return bool true when a rule of that name is registered.
	 */
	public function hasRule(string $name): bool
	{
		return isset($this->getRulesDirect()[$name]);
	}

	// ── Engine ────────────────────────────────────────────────────────────────

	/**
	 * Splits a pipe-delimited rule string into a list and trims each rule string of
	 * a list; an empty rule string is dropped and a callable is kept.
	 * @param array<callable|string>|string $rules The rules of a field.
	 * @return array<callable|string> The rules as a list.
	 */
	protected function normalizeRules(array|string $rules): array
	{
		$list = [];
		foreach (is_array($rules) ? $rules : explode('|', $rules) as $rule) {
			if (is_string($rule)) {
				$rule = trim($rule);
				if ($rule === '') {
					continue;
				}
			}
			$list[] = $rule;
		}
		return $list;
	}

	/**
	 * Applies every rule of one field to a present, non-null value.
	 * @param mixed &$value The field value; coerced in place by the rules.
	 * @param array<callable|string> $fieldRules The rules of the field.
	 * @param string $field The field name.
	 * @throws TConfigurationException when a rule name is not registered.
	 * @return string[] The error messages; empty when the value is valid.
	 */
	protected function validateField(mixed &$value, array $fieldRules, string $field): array
	{
		$errors = [];
		$rules = $this->getRulesDirect();
		foreach ($fieldRules as $rule) {
			if (is_string($rule)) {
				$ruleParts = explode(':', $rule, 2);
				$ruleName = $ruleParts[0];
				$parameter = $ruleParts[1] ?? null;
				if (!isset($rules[$ruleName])) {
					throw new TConfigurationException('inputvalidator_rule_unknown', $ruleName, $field);
				}
				$rule = $rules[$ruleName];
			} else {
				$parameter = null;
			}
			$message = $rule($value, $parameter, $field);
			if ($message !== null) {
				$errors[] = $message;
			}
		}
		return $errors;
	}

	/**
	 * Builds a localized error message.
	 * @param string $text The English message with `{name}` placeholders.
	 * @param array<string, scalar> $parameters Placeholder values keyed by name.
	 * @return string The localized message with the placeholders substituted.
	 */
	protected function getMessage(string $text, array $parameters): string
	{
		return Prado::localize($text, $parameters);
	}

	/**
	 * @return array<string, callable> the built-in rules keyed by rule name.
	 */
	protected function getDefaultRules(): array
	{
		return [
			'required' => $this->ruleRequired(...),
			'nullable' => $this->ruleNullable(...),
			'string' => $this->ruleString(...),
			'integer' => $this->ruleInteger(...),
			'int' => $this->ruleInteger(...),
			'float' => $this->ruleNumeric(...),
			'numeric' => $this->ruleNumeric(...),
			'boolean' => $this->ruleBoolean(...),
			'bool' => $this->ruleBoolean(...),
			'array' => $this->ruleArray(...),
			'email' => $this->ruleEmail(...),
			'url' => $this->ruleUrl(...),
			'min' => $this->ruleMin(...),
			'max' => $this->ruleMax(...),
			'in' => $this->ruleIn(...),
		];
	}

	// ── Built-in rules ────────────────────────────────────────────────────────

	/**
	 * Absence and null are checked before the rules run; an empty string or array fails here.
	 * @param mixed &$value The field value.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value is not `''` or `[]`, else the error message.
	 */
	protected function ruleRequired(mixed &$value, ?string $parameter, string $field): ?string
	{
		if ($value === '' || $value === []) {
			return $this->getMessage('The {field} field is required.', ['field' => $field]);
		}
		return null;
	}

	/**
	 * Null is handled before the rules run, so a non-null value passes.
	 * @param mixed &$value The field value.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null.
	 */
	protected function ruleNullable(mixed &$value, ?string $parameter, string $field): ?string
	{
		return null;
	}

	/**
	 * @param mixed &$value The field value.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value is a string, else the error message.
	 */
	protected function ruleString(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (!is_string($value)) {
			return $this->getMessage('The {field} field must be a string.', ['field' => $field]);
		}
		return null;
	}

	/**
	 * Uses `FILTER_VALIDATE_INT`, which rejects `'--5'`, `'1-2'`, `'1.5'` and `'5e2'`
	 * and accepts surrounding whitespace and a leading `+`.
	 * @param mixed &$value The field value; an integer string or whole float is coerced to int.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value is an integer, else the error message.
	 */
	protected function ruleInteger(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (is_int($value)) {
			return null;
		}
		if (is_string($value) || is_float($value)) {
			$filtered = filter_var($value, FILTER_VALIDATE_INT);
			if ($filtered !== false) {
				$value = $filtered;
				return null;
			}
		}
		return $this->getMessage('The {field} field must be an integer.', ['field' => $field]);
	}

	/**
	 * @param mixed &$value The field value; a numeric value is coerced to float.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value is numeric, else the error message.
	 */
	protected function ruleNumeric(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (!is_numeric($value)) {
			return $this->getMessage('The {field} field must be numeric.', ['field' => $field]);
		}
		$value = (float) $value;
		return null;
	}

	/**
	 * @param mixed &$value The field value; a boolean-like value is coerced to bool.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value is boolean-like, else the error message.
	 */
	protected function ruleBoolean(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (!in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
			return $this->getMessage('The {field} field must be a boolean.', ['field' => $field]);
		}
		$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		return null;
	}

	/**
	 * @param mixed &$value The field value.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value is an array, else the error message.
	 */
	protected function ruleArray(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (!is_array($value)) {
			return $this->getMessage('The {field} field must be an array.', ['field' => $field]);
		}
		return null;
	}

	/**
	 * @param mixed &$value The field value.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value passes `FILTER_VALIDATE_EMAIL`, else the error message.
	 */
	protected function ruleEmail(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return $this->getMessage('The {field} field must be a valid email address.', ['field' => $field]);
		}
		return null;
	}

	/**
	 * @param mixed &$value The field value.
	 * @param ?string $parameter Unused.
	 * @param string $field The field name.
	 * @return ?string null when the value passes `FILTER_VALIDATE_URL`, else the error message.
	 */
	protected function ruleUrl(mixed &$value, ?string $parameter, string $field): ?string
	{
		if (!filter_var($value, FILTER_VALIDATE_URL)) {
			return $this->getMessage('The {field} field must be a valid URL.', ['field' => $field]);
		}
		return null;
	}

	/**
	 * Returns the numeric parameter of a `min` or `max` rule.
	 * @param ?string $parameter The rule parameter.
	 * @param string $rule The rule name.
	 * @param string $field The field name.
	 * @throws TConfigurationException when the parameter is absent or not numeric.
	 * @return float The parameter.
	 */
	protected function getNumericParameter(?string $parameter, string $rule, string $field): float
	{
		if ($parameter === null || !is_numeric($parameter)) {
			throw new TConfigurationException('inputvalidator_parameter_required', $rule, $field);
		}
		return (float) $parameter;
	}

	/**
	 * A string is measured in characters (`mb_strlen`), an array in items, and a
	 * numeric value is compared by value.
	 * @param mixed &$value The field value.
	 * @param ?string $parameter The minimum.
	 * @param string $field The field name.
	 * @throws TConfigurationException when the minimum is absent or not numeric.
	 * @return ?string null when the value meets the minimum, else the error message.
	 */
	protected function ruleMin(mixed &$value, ?string $parameter, string $field): ?string
	{
		$min = $this->getNumericParameter($parameter, 'min', $field);
		$parameters = ['field' => $field, 'parameter' => (string) $parameter];
		if (is_string($value)) {
			if (mb_strlen($value) < $min) {
				return $this->getMessage('The {field} field must be at least {parameter} characters.', $parameters);
			}
		} elseif (is_array($value)) {
			if (count($value) < $min) {
				return $this->getMessage('The {field} field must have at least {parameter} items.', $parameters);
			}
		} elseif (is_numeric($value) && $value < $min) {
			return $this->getMessage('The {field} field must be at least {parameter}.', $parameters);
		}
		return null;
	}

	/**
	 * A string is measured in characters (`mb_strlen`), an array in items, and a
	 * numeric value is compared by value.
	 * @param mixed &$value The field value.
	 * @param ?string $parameter The maximum.
	 * @param string $field The field name.
	 * @throws TConfigurationException when the maximum is absent or not numeric.
	 * @return ?string null when the value is within the maximum, else the error message.
	 */
	protected function ruleMax(mixed &$value, ?string $parameter, string $field): ?string
	{
		$max = $this->getNumericParameter($parameter, 'max', $field);
		$parameters = ['field' => $field, 'parameter' => (string) $parameter];
		if (is_string($value)) {
			if (mb_strlen($value) > $max) {
				return $this->getMessage('The {field} field must not exceed {parameter} characters.', $parameters);
			}
		} elseif (is_array($value)) {
			if (count($value) > $max) {
				return $this->getMessage('The {field} field must not have more than {parameter} items.', $parameters);
			}
		} elseif (is_numeric($value) && $value > $max) {
			return $this->getMessage('The {field} field must not exceed {parameter}.', $parameters);
		}
		return null;
	}

	/**
	 * The options are split on commas and trimmed; a non-scalar value fails.
	 * @param mixed &$value The field value.
	 * @param ?string $parameter The comma-separated options.
	 * @param string $field The field name.
	 * @throws TConfigurationException when the options are absent.
	 * @return ?string null when the value is one of the options, else the error message.
	 */
	protected function ruleIn(mixed &$value, ?string $parameter, string $field): ?string
	{
		if ($parameter === null || trim($parameter) === '') {
			throw new TConfigurationException('inputvalidator_parameter_required', 'in', $field);
		}
		$allowed = array_map('trim', explode(',', $parameter));
		if (!is_scalar($value) || !in_array((string) $value, $allowed, true)) {
			return $this->getMessage('The {field} field must be one of: {list}.', ['field' => $field, 'list' => implode(', ', $allowed)]);
		}
		return null;
	}
}
