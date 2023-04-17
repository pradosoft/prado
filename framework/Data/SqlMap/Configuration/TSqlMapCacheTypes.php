<?php
/**
 * TSqlMapCacheModel, TSqlMapCacheTypes and TSqlMapCacheKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapCacheTypes enumerable enum.
 *
 * Implemented cache are 'Basic', 'FIFO' and 'LRU'.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
enum TSqlMapCacheTypes: string
{
	case Basic = 'Basic';
	case FIFO = 'FIFO';
	case LRU = 'LRU';
}
