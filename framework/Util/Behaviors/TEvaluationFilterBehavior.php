<?php

/**
 * TEvaluationFilterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;
use Prado\Util\TCallChain;
use Prado\Util\TClassBehavior;

/**
 * TEvaluationFilterBehavior class.
 *
 * TEvaluationFilterBehavior checks the PHP code of {@see \Prado\TComponent::evaluateExpression()}
 * and {@see \Prado\TComponent::evaluateStatements()} before it runs.  Code that fails a check
 * throws a {@see \Prado\Exceptions\TInvalidOperationException} and is not evaluated.  The
 * behavior checks the code returned by the rest of the filter chain, so it sees the code
 * that runs.
 *
 * The code is tokenized as PHP parses it.  The checks are:
 * | Code | Result |
 * |---|---|
 * | PHP statements | Rejected unless {@see setAllowStatements AllowStatements} is true. |
 * | `eval`, `include`, `require`, `exit`, backticks, `$$`, `${`, `?>`, attributes, and declarations (`function`, `class`, `interface`, `trait`, `enum`, `namespace`, `use`, `global`, `goto`, `declare`) | Rejected. |
 * | `name(...)` | `name` must be in {@see setAllowedFunctions AllowedFunctions}. |
 * | `Class::method(...)`, `Class::$property`, `new Class` | `Class::method`, `Class::__construct`, or `Class::*` must be in {@see setAllowedStaticMethods AllowedStaticMethods}. |
 * | `Class::CONSTANT`, `Class::class` | Allowed. |
 * | `->name`, `?->name` | Rejected when `name` begins with `_` or, with or without a `get`/`set` prefix, is in {@see setDeniedMembers DeniedMembers}. |
 * | `->$name`, `->{...}` | Rejected. |
 * | A call on a variable, string, `)`, `]`, or `}` | Rejected. |
 *
 * Code that does not parse is returned unchanged, and the evaluation reports the parse error.
 *
 * Evaluated code runs in the scope of {@see \Prado\TComponent}, so the `_` rule keeps it
 * from the private behavior and event properties.  The default DeniedMembers keep it from
 * evaluating other code and from changing behaviors and event handlers.
 *
 * The filter reduces what evaluated code can do.  It is not a sandbox: an allowed method
 * can reach other objects.  Request data must not be evaluated.
 *
 * Each class that evaluates code needs the behavior.  Template controls, themes, templates,
 * services, modules, and cron tasks are {@see \Prado\TApplicationComponent}s; configuration
 * conditions are evaluated by the {@see \Prado\TApplication}.
 * ```xml
 * <module id="behaviors" class="Prado\Util\TBehaviorsModule">
 *     <behavior name="evaluationFilter" Class="Prado\Util\Behaviors\TEvaluationFilterBehavior" AttachToClass="Prado\TApplicationComponent" />
 *     <behavior name="appEvaluationFilter" Class="Prado\Util\Behaviors\TEvaluationFilterBehavior" AttachTo="Application" />
 * </module>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEvaluationFilterBehavior extends TClassBehavior
{
	/** The default functions evaluated code may call.  None takes a callable or accesses files. */
	public const DEFAULT_ALLOWED_FUNCTIONS = [
		'abs', 'array_key_exists', 'array_keys', 'array_merge', 'array_reverse', 'array_search',
		'array_slice', 'array_sum', 'array_values', 'basename', 'boolval', 'ceil', 'checkdate',
		'count', 'date', 'dirname', 'explode', 'floatval', 'floor', 'gmdate', 'htmlentities',
		'htmlspecialchars', 'implode', 'in_array', 'intval', 'is_array', 'is_bool', 'is_float',
		'is_int', 'is_null', 'is_numeric', 'is_object', 'is_string', 'json_encode', 'lcfirst',
		'ltrim', 'max', 'mb_strlen', 'mb_strtolower', 'mb_strtoupper', 'mb_substr', 'min', 'mktime',
		'nl2br', 'number_format', 'range', 'rawurlencode', 'round', 'rtrim', 'sprintf',
		'str_contains', 'str_ends_with', 'str_pad', 'str_repeat', 'str_replace', 'str_starts_with',
		'strip_tags', 'stripos', 'strlen', 'strpos', 'strtolower', 'strtotime', 'strtoupper',
		'strval', 'substr', 'time', 'trim', 'ucfirst', 'ucwords', 'urlencode', 'wordwrap',
	];

	/** The default static methods evaluated code may call.  `<%[ ]%>` template tags call `Prado::localize()`. */
	public const DEFAULT_ALLOWED_STATIC_METHODS = ['Prado::localize', 'Prado\Prado::localize'];

	/** The default object members evaluated code may not access. */
	public const DEFAULT_DENIED_MEMBERS = [
		'asa', 'attachBehavior', 'attachBehaviors', 'attachEventHandler', 'Behaviors',
		'BehaviorsEnabled', 'callBehaviorsMethod', 'clearBehaviors', 'detachBehavior',
		'detachBehaviors', 'detachEventHandler', 'disableBehavior', 'disableBehaviors',
		'enableBehavior', 'enableBehaviors', 'evaluateExpression', 'evaluateStatements',
		'EventHandlers', 'fxAttachClassBehavior', 'fxDetachClassBehavior',
	];

	/** Tokens that evaluated code may not contain. */
	private const DENIED_TOKENS = [
		T_ATTRIBUTE => true, T_CLASS => true, T_CLOSE_TAG => true, T_DECLARE => true,
		T_DOLLAR_OPEN_CURLY_BRACES => true, T_ENUM => true, T_EVAL => true, T_EXIT => true,
		T_FUNCTION => true, T_GLOBAL => true, T_GOTO => true, T_HALT_COMPILER => true,
		T_INCLUDE => true, T_INCLUDE_ONCE => true, T_INLINE_HTML => true, T_INTERFACE => true,
		T_NAMESPACE => true, T_OPEN_TAG => true, T_OPEN_TAG_WITH_ECHO => true, T_REQUIRE => true,
		T_REQUIRE_ONCE => true, T_TRAIT => true, T_USE => true, '`' => true, '$' => true,
	];

	/** Tokens that name a function, class, or member. */
	private const NAME_TOKENS = [
		T_STRING => true, T_NAME_QUALIFIED => true, T_NAME_FULLY_QUALIFIED => true,
		T_NAME_RELATIVE => true, T_STATIC => true,
	];

	/** Tokens whose value can be called as a dynamic callable. */
	private const DYNAMIC_CALL_TOKENS = [
		T_VARIABLE => true, T_CONSTANT_ENCAPSED_STRING => true, T_END_HEREDOC => true,
		')' => true, ']' => true, '}' => true, '"' => true,
	];

	/** Tokens before a name that make it something other than a function call. */
	private const NOT_FUNCTION_PREFIX = [
		T_OBJECT_OPERATOR => true, T_NULLSAFE_OBJECT_OPERATOR => true, T_DOUBLE_COLON => true,
		T_NEW => true,
	];

	/** @var bool Whether PHP statements are evaluated.  Default false. */
	private bool $_allowStatements = false;

	/** @var ?array<string, true> The allowed function names in lower case; null for the default. */
	private ?array $_allowedFunctions = null;

	/** @var ?array<string, true> The allowed `class::method` names in lower case; null for the default. */
	private ?array $_allowedStaticMethods = null;

	/** @var ?array<string, true> The denied member names in lower case; null for the default. */
	private ?array $_deniedMembers = null;

	/**
	 * Checks an expression after the rest of the filter chain.
	 * @param \Prado\TComponent $owner The component evaluating the expression.
	 * @param string $expression The PHP expression.
	 * @param TCallChain $chain The filter chain.
	 * @throws TInvalidOperationException When the expression fails a check.
	 * @return string The expression.
	 */
	public function dyEvaluateExpressionFilter($owner, $expression, TCallChain $chain)
	{
		$expression = $chain->dyEvaluateExpressionFilter($expression);
		$this->checkCode($owner, $expression, 'return ' . $expression . ';');
		return $expression;
	}

	/**
	 * Checks statements after the rest of the filter chain.
	 * @param \Prado\TComponent $owner The component evaluating the statements.
	 * @param string $statements The PHP statements.
	 * @param TCallChain $chain The filter chain.
	 * @throws TInvalidOperationException When statements are not allowed or fail a check.
	 * @return string The statements.
	 */
	public function dyEvaluateStatementsFilter($owner, $statements, TCallChain $chain)
	{
		$statements = $chain->dyEvaluateStatementsFilter($statements);
		if (!$this->getAllowStatements()) {
			throw new TInvalidOperationException('evaluationfilter_statements_denied', $owner::class, $statements);
		}
		$this->checkCode($owner, $statements, $statements);
		return $statements;
	}

	/**
	 * Throws when the code, as `eval()` parses it, fails a check.
	 * @param object $owner The component evaluating the code.
	 * @param string $code The code to report.
	 * @param string $source The code as `eval()` receives it.
	 * @throws TInvalidOperationException When the code fails a check.
	 */
	protected function checkCode(object $owner, string $code, string $source): void
	{
		if (($denied = $this->findDeniedCode($source)) !== null) {
			throw new TInvalidOperationException('evaluationfilter_code_denied', $owner::class, $code, $denied);
		}
	}

	/**
	 * Returns the first part of the code that fails a check.
	 * @param string $source The code as `eval()` receives it.
	 * @return ?string The denied code, or null when the code passes or does not parse.
	 */
	public function findDeniedCode(string $source): ?string
	{
		try {
			$tokens = token_get_all('<?php ' . $source, TOKEN_PARSE);
		} catch (\ParseError $e) {
			return null;
		}
		$tokens = array_values(array_filter($tokens, fn ($token) => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
		$count = count($tokens);
		for ($i = 1; $i < $count; $i++) {
			if (($denied = $this->findDeniedToken($tokens, $i)) !== null) {
				return $denied;
			}
		}
		return null;
	}

	/**
	 * Returns the token at an index when it fails a check.
	 * @param array $tokens The tokens without whitespace and comments.
	 * @param int $i The token index.
	 * @return ?string The denied code, or null when the token passes.
	 */
	protected function findDeniedToken(array $tokens, int $i): ?string
	{
		$id = $this->tokenId($tokens[$i]);
		$text = $this->tokenText($tokens[$i]);
		$prevId = $this->tokenId($tokens[$i - 1]);
		$nextId = isset($tokens[$i + 1]) ? $this->tokenId($tokens[$i + 1]) : null;
		$nextText = isset($tokens[$i + 1]) ? $this->tokenText($tokens[$i + 1]) : '';

		if (isset(self::DENIED_TOKENS[$id])) {
			if ($id === T_CLASS && $prevId === T_DOUBLE_COLON) {
				return null;
			}
			return $text;
		}
		if ($id === T_OBJECT_OPERATOR || $id === T_NULLSAFE_OBJECT_OPERATOR) {
			if ($nextId !== T_STRING || $this->isDeniedMember($nextText)) {
				return $text . $nextText;
			}
		} elseif ($id === T_DOUBLE_COLON) {
			if ($nextId === T_CLASS || ($nextId === T_STRING && $this->tokenId($tokens[$i + 2] ?? '') !== '(')) {
				return null;
			}
			$member = $nextId === T_VARIABLE ? '*' : $nextText;
			if (!isset(self::NAME_TOKENS[$prevId]) || ($nextId !== T_STRING && $nextId !== T_VARIABLE) || !$this->isAllowedStaticMethod($this->tokenText($tokens[$i - 1]), $member)) {
				return $this->tokenText($tokens[$i - 1]) . $text . $nextText;
			}
		} elseif ($id === T_NEW) {
			if (!isset(self::NAME_TOKENS[$nextId]) || !$this->isAllowedStaticMethod($nextText, '__construct')) {
				return $text . ' ' . $nextText;
			}
		} elseif ($id === '(') {
			if (isset(self::DYNAMIC_CALL_TOKENS[$prevId])) {
				return $this->tokenText($tokens[$i - 1]) . $text;
			}
			if (isset(self::NAME_TOKENS[$prevId]) && !isset(self::NOT_FUNCTION_PREFIX[$this->tokenId($tokens[$i - 2] ?? '')]) && !$this->isAllowedFunction($this->tokenText($tokens[$i - 1]))) {
				return $this->tokenText($tokens[$i - 1]) . $text;
			}
		}
		return null;
	}

	/**
	 * @param array|string $token A token from `token_get_all()`.
	 * @return int|string The token ID, or the character of a one-character token.
	 */
	private function tokenId($token): int|string
	{
		return is_array($token) ? $token[0] : $token;
	}

	/**
	 * @param array|string $token A token from `token_get_all()`.
	 * @return string The token text.
	 */
	private function tokenText($token): string
	{
		return is_array($token) ? $token[1] : $token;
	}

	/**
	 * @param string $name A function name from the code.
	 * @return bool Whether evaluated code may call the function.
	 */
	public function isAllowedFunction(string $name): bool
	{
		return isset($this->getAllowedFunctionMap()[$this->normalizeName($name)]);
	}

	/**
	 * @param string $class A class name from the code.
	 * @param string $method A static method name, `__construct` for `new`, or `*` for a static property.
	 * @return bool Whether evaluated code may call the static method.
	 */
	public function isAllowedStaticMethod(string $class, string $method): bool
	{
		$map = $this->getAllowedStaticMethodMap();
		$class = $this->normalizeName($class);
		return isset($map[$class . '::*']) || ($method !== '*' && isset($map[$class . '::' . strtolower($method)]));
	}

	/**
	 * @param string $name A member name after `->` or `?->`.
	 * @return bool Whether evaluated code may not access the member.
	 */
	public function isDeniedMember(string $name): bool
	{
		$map = $this->getDeniedMemberMap();
		$name = strtolower($name);
		if ($name === '' || $name[0] === '_' || isset($map[$name])) {
			return true;
		}
		$prefix = substr($name, 0, 3);
		return ($prefix === 'get' || $prefix === 'set') && isset($map[substr($name, 3)]);
	}

	/**
	 * @return bool Whether PHP statements are evaluated.  Default false.
	 */
	public function getAllowStatements(): bool
	{
		return $this->_allowStatements;
	}

	/**
	 * @param bool|string $value Whether PHP statements are evaluated.
	 * @return static The current object.
	 */
	public function setAllowStatements($value): static
	{
		$this->_allowStatements = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return string[] The function names evaluated code may call, in lower case.
	 */
	public function getAllowedFunctions(): array
	{
		return array_keys($this->getAllowedFunctionMap());
	}

	/**
	 * @param null|string|string[] $value The function names, as an array or a comma separated
	 *   string, that evaluated code may call; null for {@see DEFAULT_ALLOWED_FUNCTIONS}.
	 * @return static The current object.
	 */
	public function setAllowedFunctions($value): static
	{
		$this->_allowedFunctions = $value === null ? null : $this->createMap($value);
		return $this;
	}

	/**
	 * @return string[] The `class::method` names evaluated code may call, in lower case.
	 */
	public function getAllowedStaticMethods(): array
	{
		return array_keys($this->getAllowedStaticMethodMap());
	}

	/**
	 * @param null|string|string[] $value The `Class::method` names, as an array or a comma
	 *   separated string, that evaluated code may call; null for {@see DEFAULT_ALLOWED_STATIC_METHODS}.
	 *   `Class::__construct` allows `new Class`, and `Class::*` allows every static member.
	 * @return static The current object.
	 */
	public function setAllowedStaticMethods($value): static
	{
		$this->_allowedStaticMethods = $value === null ? null : $this->createMap($value);
		return $this;
	}

	/**
	 * @return string[] The member names evaluated code may not access, in lower case.
	 */
	public function getDeniedMembers(): array
	{
		return array_keys($this->getDeniedMemberMap());
	}

	/**
	 * @param null|string|string[] $value The member names, as an array or a comma separated
	 *   string, that evaluated code may not access; null for {@see DEFAULT_DENIED_MEMBERS}.
	 *   Members that begin with `_` are always denied.
	 * @return static The current object.
	 */
	public function setDeniedMembers($value): static
	{
		$this->_deniedMembers = $value === null ? null : $this->createMap($value);
		return $this;
	}

	/**
	 * @return array<string, true> The allowed function names as keys.
	 */
	protected function getAllowedFunctionMap(): array
	{
		return $this->_allowedFunctions ??= $this->createMap(self::DEFAULT_ALLOWED_FUNCTIONS);
	}

	/**
	 * @return array<string, true> The allowed `class::method` names as keys.
	 */
	protected function getAllowedStaticMethodMap(): array
	{
		return $this->_allowedStaticMethods ??= $this->createMap(self::DEFAULT_ALLOWED_STATIC_METHODS);
	}

	/**
	 * @return array<string, true> The denied member names as keys.
	 */
	protected function getDeniedMemberMap(): array
	{
		return $this->_deniedMembers ??= $this->createMap(self::DEFAULT_DENIED_MEMBERS);
	}

	/**
	 * @param string|string[] $value Names as an array or a comma separated string.
	 * @return array<string, true> The normalized names as keys.
	 */
	private function createMap($value): array
	{
		if (is_string($value)) {
			$value = explode(',', $value);
		}
		$map = [];
		foreach ($value as $name) {
			if (($name = $this->normalizeName((string) $name)) !== '') {
				$map[$name] = true;
			}
		}
		return $map;
	}

	/**
	 * @param string $name A function, class, or member name.
	 * @return string The name in lower case without whitespace or a leading `\`.
	 */
	private function normalizeName(string $name): string
	{
		return strtolower(ltrim(trim($name), '\\'));
	}
}
