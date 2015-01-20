<?php
/**
 * TActiveRecord, TActiveRecordEventParameter, TActiveRecordInvalidFinderResult class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.ActiveRecord
 */

/**
 * TActiveRecordInvalidFinderResult class.
 * TActiveRecordInvalidFinderResult defines the enumerable type for possible results
 * if an invalid {@link TActiveRecord::__call magic-finder} invoked.
 *
 * The following enumerable values are defined:
 * - Null: return null (default)
 * - Exception: throws a TActiveRecordException
 *
 * @author Yves Berkholz <godzilla80@gmx.net>
 * @package System.Data.ActiveRecord
 * @see TActiveRecordManager::setInvalidFinderResult
 * @see TActiveRecordConfig::setInvalidFinderResult
 * @see TActiveRecord::setInvalidFinderResult
 * @since 3.1.5
 */
class TActiveRecordInvalidFinderResult extends TEnumerable
{
	const Null = 'Null';
	const Exception = 'Exception';
}