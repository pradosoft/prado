<?php

/**
 * TEmailEventParameter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email;

use Prado\Util\Email\TEmail;

/**
 * TEmailEventParameter class
 *
 * TEmailEventParameter encapsulates the parameter data for
 * email sending events. The email of the event is specified via
 * {@link setEmail Email} property while the many parameter is via
 * {@link setMany Many} property.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailEventParameter extends \Prado\TEventParameter
{
	private $_email;
	private $_many;

	/**
	 * Constructor.
	 * @param string $name name of the broadcast event
	 * @param null|mixed $parameter parameter of the broadcast event
	 * @param null|TEmail $email
	 * @param null|mixed $many
	 */
	public function __construct(TEmail $email = null, $many = null)
	{
		$this->_email = $email;
		$this->_many = $many;
		parent::__construct();
	}

	/**
	 * @return \Prade\Util\Email\UI\TEmail The email being sent
	 */
	public function getEmail()
	{
		return $this->_email;
	}

	/**
	 * @param \Prade\Util\Email\UI\TEmail $email The email being sent
	 */
	public function setEmail($email)
	{
		$this->_email = $value;
	}

	/**
	 * @return bool Is processing many emails.
	 */
	public function getMany()
	{
		return $this->_many;
	}

	/**
	 * @param bool $many Is processing many emails
	 */
	public function setMany($many)
	{
		$this->_many = $many;
	}
}
