<?php
/**
 * TSimpleDynamicParser class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSimpleDynamicParser finds place holders $name$ in the sql text and replaces
 * it with a TSimpleDynamicParser::DYNAMIC_TOKEN.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSimpleDynamicParser
{
	const PARAMETER_TOKEN_REGEXP = '/\$([^\$]+)\$/';
	const DYNAMIC_TOKEN = '`!`';

	/**
	 * Parse the sql text for dynamic place holders of the form $name$.
	 * @param string $sqlText Sql text.
	 * @return array name value pairs 'sql' and 'parameters'.
	 */
	public function parse($sqlText)
	{
		$matches = [];
		$mappings = [];
		preg_match_all(self::PARAMETER_TOKEN_REGEXP, $sqlText, $matches);
		for ($i = 0, $k = count($matches[1]); $i < $k; $i++) {
			$mappings[] = $matches[1][$i];
			$sqlText = str_replace($matches[0][$i], self::DYNAMIC_TOKEN, $sqlText);
		}
		return ['sql' => $sqlText, 'parameters' => $mappings];
	}
}
