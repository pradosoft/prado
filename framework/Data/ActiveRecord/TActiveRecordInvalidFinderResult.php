<?php
/**
 * TActiveRecordInvalidFinderResult class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord;

/**
 * TActiveRecordInvalidFinderResult enum.
 * TActiveRecordInvalidFinderResult defines the enumerable type for possible results
 * if an invalid {@link TActiveRecord::__call magic-finder} invoked.
 *
 * The following enumerable values are defined:
 * - Null: return null (default)
 * - Exception: throws a TActiveRecordException
 *
 * @author Yves Berkholz <godzilla80@gmx.net>
 * @see TActiveRecordManager::setInvalidFinderResult
 * @see TActiveRecordConfig::setInvalidFinderResult
 * @see TActiveRecord::setInvalidFinderResult
 * @since 3.1.5
 */
enum TActiveRecordInvalidFinderResult: string
{
	case Null = 'Null';
	case Exception = 'Exception';
}
