<?php
/**
 * TInlineParameterMapParser class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

use Prado\Data\SqlMap\DataMapper\TSqlMapUndefinedException;

/**
 * TInlineParameterMapParser class.
 *
 * The inline parameter map syntax lets you embed the property name,
 * the property type, the column type, and a null value replacement into a
 * parametrized SQL statement.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TInlineParameterMapParser
{
	/**
	 * Regular expression for parsing inline parameter maps.
	 */
	const PARAMETER_TOKEN_REGEXP = '/#([^#]+)#/';

	/**
	 * Parse the sql text for inline parameters.
	 * @param string $sqlText sql text
	 * @param array $scope file and node details for exception message.
	 * @return array 'sql' and 'parameters' name value pairs.
	 */
	public function parse($sqlText, $scope)
	{
		$matches = [];
		$mappings = [];
		preg_match_all(self::PARAMETER_TOKEN_REGEXP, $sqlText, $matches);

		for ($i = 0, $k = count($matches[1]); $i < $k; $i++) {
			$mappings[] = $this->parseMapping($matches[1][$i], $scope);
			$sqlText = str_replace($matches[0][$i], '?', $sqlText);
		}
		return ['sql' => $sqlText, 'parameters' => $mappings];
	}

	/**
	 * Parse inline parameter with syntax as
	 * #propertyName,type=string,dbype=Varchar,nullValue=N/A,handler=string#
	 * @param string $token parameter token
	 * @param array $scope file and node details for exception message.
	 */
	protected function parseMapping($token, $scope)
	{
		$mapping = new TParameterProperty;
		$properties = explode(',', $token);
		$mapping->setProperty(trim(array_shift($properties)));
		foreach ($properties as $property) {
			$prop = explode('=', $property);
			$name = trim($prop[0]);
			$value = trim($prop[1]);
			if ($mapping->canSetProperty($name)) {
				$mapping->{'set' . $name}($value);
			} else {
				throw new TSqlMapUndefinedException(
					'sqlmap_undefined_property_inline_map',
					$name,
					$scope['file'],
					$scope['node'],
					$token
				);
			}
		}
		return $mapping;
	}
}
