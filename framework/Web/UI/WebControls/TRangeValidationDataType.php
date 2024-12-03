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
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TRangeValidationDataType extends TValidationDataType
{
	public const StringLength = 'StringLength';
}
