<?php

/**
 * ChoiceFormat class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@see mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@see http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 */

namespace Prado\I18N\core;

use Closure;
use Exception;

/**
 * ChoiceFormat class.
 *
 * ChoiceFormat converts between ranges of numeric values and string
 * names for those ranges.
 *
 * A ChoiceFormat splits the real number line -Inf to +Inf into two or
 * more contiguous ranges. Each range is mapped to a string.
 * ChoiceFormat is generally used in a MessageFormat for displaying
 * grammatically correct plurals such as "There are 2 files."
 *
 * ```php
 *	$string = '[0] are no files |[1] is one file |(1,Inf] are {number} files';
 *
 *  $formatter = new MessageFormat(...); //init for a source
 *	$translated = $formatter->format($string);
 *
 *  $choice = new ChoiceFormat();
 * 	echo $choice->format($translated, 0); //shows "are no files"
 * ```
 *
 * The message/string choices are separated by the pipe "|" followed
 * by a set notation of the form
 *  # <tt>[1,2]</tt> -- accepts values between 1 and 2, inclusive.
 *  # <tt>(1,2)</tt> -- accepts values between 1 and 2, excluding 1 and 2.
 *  # <tt>{1,2,3,4}</tt> -- only values defined in the set are accepted.
 *  # <tt>[-Inf,0)</tt> -- accepts value greater or equal to negative infinity
 *                       and strictly less than 0
 * Any non-empty combinations of the delimiters of square and round brackets
 * are acceptable.
 *
 * Since version 3.1.2 the following set notation is also possible.
 *
 *  # <tt>{n: n % 10 > 1 && n % 10 < 5}</tt> --  matches numbers like 2, 3, 4, 22, 23, 24
 *
 * Where set is defined by the expression after <tt>n:</tt>. In particular, the expression
 * accepts the following mathematical/logical operators to form a set of logical conditions
 * on the value given by <tt>n</tt>:
 *   # <tt>&lt;</tt> -- less than.
 *   # <tt>&lt;=</tt> -- less than equals.
 *   # <tt>&gt;</tt> -- greater than.
 *   # <tt>&gt=</tt> -- greater than equals.
 *   # <tt>==</tt> -- of equal value.
 *   # <tt>!=</tt> -- not of equal value.
 *   # <tt>%</tt> -- modulo, e.g., 1 % 10 equals 1, 11 % 10 equals 1.
 *   # <tt>-</tt> -- minus, negative.
 *   # <tt>+</tt> -- addition.
 *   # <tt>&amp;</tt> -- conditional AND.
 *   # <tt>&amp;&amp;</tt> -- condition AND with short circuit.
 *   # <tt>|</tt> -- conditional OR.
 *   # <tt>||</tt> -- conditional OR with short circuit.
 *   # <tt>!</tt> -- negation.
 *
 * Additional round brackets can also be used to perform grouping.
 * The operators follow PHP precedence. The expression is parsed by ChoiceFormat
 * and is not executed as PHP; any other token makes the set match no number.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 */
class ChoiceFormat
{
	/**
	 * The pattern to validate a set notation
	 * @var string
	 */
	protected $validate = '/[\(\[\{]|[-Inf\d:\s]+|,|[\+Inf\d\s:\?\-=!><%\|&\(\)]+|[\)\]\}]/ms';

	/**
	 * The pattern to parse the formatting string.
	 * @var string
	 */
	protected $parse = '/\s*\|?([\(\[\{]([-Inf\d:\s]+,?[\+Inf\d\s:\?\-=!><%\|&\(\)]*)+[\)\]\}])\s*/';

	/**
	 * The binary operators of the set notation expression, from lowest to highest precedence.
	 */
	private const SET_OPERATORS = [['||'], ['&&'], ['|'], ['&'], ['==', '!='], ['<', '<=', '>', '>='], ['+', '-'], ['%']];

	/**
	 * The value for positive infinity.
	 * @var float
	 */
	protected $inf;


	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->inf = -log(0);
	}


	/**
	 * Determine if the given number belongs to a given set
	 * @param float $number the number to test.
	 * @param string $set the set, in set notation.
	 * @return bool true if number is in the set, false otherwise.
	 */
	public function isValid($number, $set)
	{
		$n = preg_match_all($this->validate, $set, $matches, PREG_SET_ORDER);

		if ($n < 3) {
			throw new Exception("Invalid set \"{$set}\"");
		}

		if (preg_match('/\{\s*n:([^\}]+)\}/', $set, $def)) {
			return $this->isValidSetNotation($number, $def[1]);
		}

		$leftBracket = $matches[0][0];
		$rightBracket = $matches[$n - 1][0];

		$i = 0;
		$elements = [];
		foreach ($matches as $match) {
			$string = $match[0];
			if ($i != 0 && $i != $n - 1 && $string !== ',') {
				if ($string == '-Inf') {
					$elements[] = -1 * $this->inf;
				} elseif ($string == '+Inf' || $string == 'Inf') {
					$elements[] = $this->inf;
				} else {
					$elements[] = (float) $string;
				}
			}
			$i++;
		}
		$total = count($elements);
		$number = (float) $number;

		if ($leftBracket == '{' && $rightBracket == '}') {
			return in_array($number, $elements);
		}

		$left = false;
		if ($leftBracket == '[') {
			$left = $number >= $elements[0];
		} elseif ($leftBracket == '(') {
			$left = $number > $elements[0];
		}

		$right = false;
		if ($rightBracket == ']') {
			$right = $number <= $elements[$total - 1];
		} elseif ($rightBracket == ')') {
			$right = $number < $elements[$total - 1];
		}

		if ($left && $right) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if the given number satisfies a set notation expression.
	 * @param float $number the number to test.
	 * @param string $set the expression after "n:" in the set notation.
	 * @return bool true if the expression is true for the number, false otherwise or when the expression is invalid.
	 */
	protected function isValidSetNotation($number, $set)
	{
		try {
			return (bool) $this->compileSetNotation($set)($number);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Compile a set notation expression into a closure of the number "n".
	 * @param string $set the expression after "n:" in the set notation.
	 * @throws Exception when the expression has a token or form outside the set notation.
	 * @return Closure the expression as a function of the number.
	 */
	protected function compileSetNotation($set): Closure
	{
		$tokens = $this->tokenizeSetNotation($set);
		$pos = 0;
		$expression = $this->parseSetBinary($tokens, $pos, 0);
		if ($pos !== count($tokens)) {
			throw new Exception("Invalid set notation \"{$set}\"");
		}
		return $expression;
	}

	/**
	 * Split a set notation expression into numbers, "n", operators, and brackets.
	 * @param string $set the expression after "n:" in the set notation.
	 * @throws Exception when the expression has a token outside the set notation.
	 * @return string[] the tokens.
	 */
	protected function tokenizeSetNotation($set): array
	{
		$tokens = [];
		$length = strlen($set);
		$offset = 0;
		while ($offset < $length) {
			if (!preg_match('/\s*(\d+(?:\.\d+)?|n|==|!=|<=|>=|&&|\|\||[-+%<>!&|()])\s*/A', $set, $match, 0, $offset)) {
				throw new Exception("Invalid set notation \"{$set}\"");
			}
			$tokens[] = $match[1];
			$offset += strlen($match[0]);
		}
		return $tokens;
	}

	/**
	 * Parse the binary operators of a precedence level and all higher levels.
	 * @param string[] $tokens the expression tokens.
	 * @param int $pos the position of the next token, advanced past the parsed tokens.
	 * @param int $level the index into {@see SET_OPERATORS}.
	 * @throws Exception when the tokens do not form an expression.
	 * @return Closure the parsed expression.
	 */
	protected function parseSetBinary(array $tokens, int &$pos, int $level): Closure
	{
		if ($level >= count(self::SET_OPERATORS)) {
			return $this->parseSetUnary($tokens, $pos);
		}
		$left = $this->parseSetBinary($tokens, $pos, $level + 1);
		while (in_array($tokens[$pos] ?? null, self::SET_OPERATORS[$level], true)) {
			$operator = $tokens[$pos++];
			$right = $this->parseSetBinary($tokens, $pos, $level + 1);
			$left = match ($operator) {
				'||' => fn ($n) => $left($n) || $right($n),
				'&&' => fn ($n) => $left($n) && $right($n),
				'|' => fn ($n) => (int) $left($n) | (int) $right($n),
				'&' => fn ($n) => (int) $left($n) & (int) $right($n),
				'==' => fn ($n) => $left($n) == $right($n),
				'!=' => fn ($n) => $left($n) != $right($n),
				'<' => fn ($n) => $left($n) < $right($n),
				'<=' => fn ($n) => $left($n) <= $right($n),
				'>' => fn ($n) => $left($n) > $right($n),
				'>=' => fn ($n) => $left($n) >= $right($n),
				'+' => fn ($n) => $left($n) + $right($n),
				'-' => fn ($n) => $left($n) - $right($n),
				'%' => function ($n) use ($left, $right) {
					$divisor = (int) $right($n);
					if ($divisor === 0) {
						throw new Exception('Modulo by zero in set notation');
					}
					return (int) $left($n) % $divisor;
				},
				default => throw new Exception("Unknown set notation operator \"{$operator}\""),
			};
		}
		return $left;
	}

	/**
	 * Parse a unary operator, a number, "n", or a bracketed expression.
	 * @param string[] $tokens the expression tokens.
	 * @param int $pos the position of the next token, advanced past the parsed tokens.
	 * @throws Exception when the tokens do not form an expression.
	 * @return Closure the parsed expression.
	 */
	protected function parseSetUnary(array $tokens, int &$pos): Closure
	{
		$token = $tokens[$pos++] ?? null;
		if ($token === '!' || $token === '-' || $token === '+') {
			$operand = $this->parseSetUnary($tokens, $pos);
			return match ($token) {
				'!' => fn ($n) => !$operand($n),
				'-' => fn ($n) => -$operand($n),
				'+' => fn ($n) => +$operand($n),
			};
		}
		if ($token === 'n') {
			return fn ($n) => $n;
		}
		if ($token === '(') {
			$expression = $this->parseSetBinary($tokens, $pos, 0);
			if (($tokens[$pos++] ?? null) !== ')') {
				throw new Exception('Unbalanced brackets in set notation');
			}
			return $expression;
		}
		if ($token !== null && is_numeric($token)) {
			$value = $token + 0;
			return fn ($n) => $value;
		}
		throw new Exception('Invalid set notation');
	}

	/**
	 * Parse a choice string and get a list of sets and a list of strings
	 * corresponding to the sets.
	 * @param string $string the string containing the choices
	 * @return array array($sets, $strings)
	 */
	public function parse($string)
	{
		$n = preg_match_all($this->parse, $string, $matches, PREG_OFFSET_CAPTURE);
		$sets = [];
		foreach ($matches[1] as $match) {
			$sets[] = $match[0];
		}
		$offset = $matches[0];
		$strings = [];
		for ($i = 0; $i < $n; $i++) {
			$len = strlen($offset[$i][0]);
			$begin = $i == 0 ? $len : $offset[$i][1] + $len;
			$end = $i == $n - 1 ? strlen($string) : $offset[$i + 1][1];
			$strings[] = substr($string, $begin, $end - $begin);
		}
		return [$sets, $strings];
	}

	/**
	 * For the choice string, and a number, find and return the
	 * string that satisfied the set within the choices.
	 * @param string $string the choices string.
	 * @param float $number the number to test.
	 * @return false|string the choosen string, or false if no result.
	 */
	public function format($string, $number)
	{
		[$sets, $strings] = $this->parse($string);
		$total = count($sets);
		for ($i = 0; $i < $total; $i++) {
			if ($this->isValid($number, $sets[$i])) {
				return $strings[$i];
			}
		}
		return false;
	}
}
