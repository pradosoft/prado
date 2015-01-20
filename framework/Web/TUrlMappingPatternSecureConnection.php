<?php
/**
 * TUrlMapping, TUrlMappingPattern and TUrlMappingPatternSecureConnection class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web
 */

/**
 * TUrlMappingPatternSecureConnection class
 *
 * TUrlMappingPatternSecureConnection defines the enumerable type for the possible SecureConnection
 * URL prefix behavior that can be used by {@link TUrlMappingPattern::constructUrl()}.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @package System.Web
 * @since 3.2
 */
class TUrlMappingPatternSecureConnection extends TEnumerable
{
	/**
	 * Keep current SecureConnection status
	 * means no prefixing
	 */
	const Automatic = 'Automatic';

	/**
	 * Force use secured connection
	 * always prefixing with https://example.com/path/to/app
	 */
	const Enable = 'Enable';

	/**
	 * Force use unsecured connection
	 * always prefixing with http://example.com/path/to/app
	 */
	const Disable = 'Disable';

	/**
	 * Force use secured connection, if in unsecured mode
	 * prefixing with https://example.com/path/to/app
	 */
	const EnableIfNotSecure = 'EnableIfNotSecure';

	/**
	 * Force use unsecured connection, if in secured mode
	 * prefixing with https://example.com/path/to/app
	 */
	const DisableIfSecure = 'DisableIfSecure';
}