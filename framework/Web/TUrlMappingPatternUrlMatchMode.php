<?php

/**
 * TUrlMapping, TUrlMappingPattern and TUrlMappingPatternUrlMatchMode class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * TUrlMappingPatternUrlMatchMode class
 *
 * TUrlMappingPatternUrlMatchMode defines the enumerable type for the part of the URL that
 * {@see \Prado\Web\TUrlMappingPattern::getPatternMatches()} matches the pattern against.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUrlMappingPatternUrlMatchMode extends \Prado\TEnumerable
{
	/**
	 * Match the pattern against the PATH_INFO part of the URL, the part after the
	 * entry script and before the question mark.
	 */
	public const PathInfo = 'PathInfo';

	/**
	 * Match the pattern against the PATH_INFO and the query string of the URL,
	 * separated by a question mark.
	 */
	public const Full = 'Full';
}
