<?php
/**
 * TSecurityManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Security
 */

namespace Prado\Security;

/**
 * TSecurityManagerValidationMode class.
 *
 * This class has been deprecated since version 3.2.1.
 *
 * TSecurityManagerValidationMode defines the enumerable type for the possible validation modes
 * that can be used by {@link TSecurityManager}.
 *
 * The following enumerable values are defined:
 * - MD5: an MD5 hash is generated from the data and used for validation.
 * - SHA1: an SHA1 hash is generated from the data and used for validation.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.0.4
 */
class TSecurityManagerValidationMode extends TEnumerable
{
	const MD5 = 'MD5';
	const SHA1 = 'SHA1';
}