<?php

/**
 * TEmailerBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Behaviors;

use Prado\Util\Email\TEmailModule;

/**
 * TEmailerBehavior class.
 *
 * TEmailerBehavior is used by TEmailModule to extend TApplication
 * with global email functionality.  It implements createEmail and sendMail.
 *
 * .
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailerBehavior extends \Prado\Util\TBehavior
{
	/**
	 * @var TEmailModule The handler for functionality
	 */
	private $_module;

	/**
	 *
	 *
	 * @param string $emailPath path to email in Email directory
	 * @param string $to address the email is going to, default null
	 * @param string $culture culture string, null to use current culture
	 */
	public function createEmail($emailPath, $to = null, $culture = null)
	{
		return $this->_module->createEmail($emailPath, $to, $culture);
	}

	/**
	 * Sends a TEmail.  The Custom Data in the body (and text) adds a '{{'
	 * before and '}}' after the key.  eg '{{CODE}}' ['CODE' =>  'value'] is
	 * replaced by 'value'.
	 * @param \Prado\Web\UI\TEmail|\Prado\Web\UI\TEmailPage $email the email to be sent
	 * @pparam null|array substitution replacement variables. keys are replaced by value
	 * @param bool $many are there many emails to be sent, default false
	 * @param null|mixed $customData
	 */
	public function sendEmail($email, $customData = null, $many = false)
	{
		return $this->_module->sendEmail($email, $customData, $many);
	}

	/**
	 * This method places no-cache meta in the head.
	 * @param array<string>|string $to The email To Address(es).
	 * @param string $subject Subject of the Email, no returns
	 * @param array<string>|string $message The string message or an array of [$html, $text].
	 * @param array $headers Email Headers, array keys are Header names and array values are
	 *  the Header Values.
	 * @param array $attachments File paths to the attachment, the data itself, or
	 * array('path'=>'/path/to/file/file.jpg','mime'=>'image/jpeg','data'=>$filedata, 'name'=>'filename.jpg').
	 * Keys are the new "cid" reference email [file] names or can be specified in the array.
	 * 'name' is required. 'data' and 'mime' is optional. either 'path' or 'data' must be provided.
	 * @param bool $many is this one of many emails, default false.
	 */
	public function sendMail($to, $subject, $message, $headers = [], $attachments = [], $many = false)
	{
		return $this->_module->sendMail($to, $subject, $message, $headers, $attachments, $many);
	}



	/**
	 * @return TEmailModule Emailer module
	 */
	public function getEmailerModule()
	{
		return $this->_module;
	}

	/**
	 * @param TEmailModule $value Emailer module
	 */
	public function setEmailerModule(TEmailModule $value)
	{
		$this->_module = $value;
	}
}
