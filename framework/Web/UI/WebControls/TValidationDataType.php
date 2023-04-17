<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TValidationDataType enum.
 * TValidationDataType defines the enumerable type for the possible data types that
 * a comparison validator can validate upon.
 *
 * The following enumerable values are defined:
 * - Integer
 * - Float
 * - Date
 * - String
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TValidationDataType: string
{
	case Integer = 'Integer';
	case Float = 'Float';
	case Date = 'Date';
	case String = 'String';
}
