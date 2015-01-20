<?php
/**
 * TSqlMapCacheModel, TSqlMapCacheTypes and TSqlMapCacheKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.SqlMap.Configuration
 */

/**
 * TSqlMapCacheTypes enumerable class.
 *
 * Implemented cache are 'Basic', 'FIFO' and 'LRU'.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.SqlMap.Configuration
 * @since 3.1
 */
class TSqlMapCacheTypes extends TEnumerable
{
	const Basic='Basic';
	const FIFO='FIFO';
	const LRU='LRU';
}