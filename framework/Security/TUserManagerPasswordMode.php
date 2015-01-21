<?php
/**
 * TUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Security
 */

namespace Prado\Security;

/**
 * TUserManagerPasswordMode class.
 * TUserManagerPasswordMode defines the enumerable type for the possible modes
 * that user passwords can be specified for a {@link TUserManager}.
 *
 * The following enumerable values are defined:
 * - Clear: the password is in plain text
 * - MD5: the password is recorded as the MD5 hash value of the original password
 * - SHA1: the password is recorded as the SHA1 hash value of the original password
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.0.4
 */
class TUserManagerPasswordMode extends TEnumerable
{
	const Clear='Clear';
	const MD5='MD5';
	const SHA1='SHA1';
}