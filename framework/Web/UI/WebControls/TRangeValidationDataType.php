<?php

/**
 * TRangeValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRangeValidationDataType class.
 * TRangeValidationDataType defines the enumerable type for the possible data types that
 * a range validator can validate upon.
 *
 * The following enumerable values are defined:
 * - Integer
 * - Float
 * - Date
 * - String
 * - StringLength
 * - Time: an HTML time value, `HH:mm`, `HH:mm:ss`, or `HH:mm:ss.sss` (@since 4.4.0)
 * - Month: an HTML month value, `yyyy-MM` (@since 4.4.0)
 * - Week: an HTML week value, `yyyy-Www`, an ISO 8601 week (@since 4.4.0)
 * - DateTimeLocal: an HTML local date and time value, `yyyy-MM-ddTHH:mm`, with
 *   optional seconds (@since 4.4.0)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TRangeValidationDataType extends TValidationDataType
{
	public const StringLength = 'StringLength';
	public const Time = 'Time';
	public const Month = 'Month';
	public const Week = 'Week';
	public const DateTimeLocal = 'DateTimeLocal';
}
