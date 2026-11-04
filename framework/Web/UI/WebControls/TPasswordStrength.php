<?php

/**
 * TPasswordStrength class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPasswordStrength class.
 *
 * TPasswordStrength defines the enumerable type for the strength levels that
 * {@see TPasswordStrengthValidator} estimates, from weakest to strongest. Each
 * level starts at an estimated entropy in bits, listed in
 * {@see TPasswordStrengthValidator::STRENGTH_ENTROPY}:
 * - VeryWeak: below 40 bits
 * - Weak: 40 bits
 * - Medium: 60 bits
 * - Strong: 80 bits
 * - VeryStrong: 100 bits
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPasswordStrength extends \Prado\TEnumerable
{
	public const VeryWeak = 'VeryWeak';
	public const Weak = 'Weak';
	public const Medium = 'Medium';
	public const Strong = 'Strong';
	public const VeryStrong = 'VeryStrong';
}
