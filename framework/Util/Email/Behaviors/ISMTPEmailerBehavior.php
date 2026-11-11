<?php

/**
 * ISMTPEmailerBehavior class
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Behaviors;

use Prado\Util\IBehavior;

/**
 * ISMTPEmailerBehavior interface.
 *
 * This defines the Common parameters for an SMTP Email Behavior extension
 * to TEmailModule.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @since 4.3.3
 */
interface ISMTPEmailerBehavior extends IBehavior
{
	public function getDNS();
	public function setDNS($dns);
	public function getScheme();
	public function setScheme($scheme);
	public function getHost();
	public function setHost($host);
	public function getPort();
	public function setPort($port);
	public function getUsername();
	public function setUsername($username);
	public function getPassword();
	public function setPassword($password);
}
