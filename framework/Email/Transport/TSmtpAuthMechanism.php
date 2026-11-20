<?php

/**
 * TSmtpAuthMechanism class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

/**
 * TSmtpAuthMechanism class.
 *
 * TSmtpAuthMechanism is the SASL mechanism that {@see TSmtpTransport} authenticates with.
 *
 * | Value | Mechanism |
 * |---|---|
 * | Auto | XOAUTH2 when an access token is set; otherwise PLAIN, or LOGIN when the server does not offer PLAIN. |
 * | Plain | AUTH PLAIN with the user name and password. |
 * | Login | AUTH LOGIN with the user name and password. |
 * | XOAuth2 | AUTH XOAUTH2 with the user name and an OAuth 2 access token. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSmtpAuthMechanism extends \Prado\TEnumerable
{
	public const Auto = 'Auto';
	public const Plain = 'Plain';
	public const Login = 'Login';
	public const XOAuth2 = 'XOAuth2';
}
