<?php

/**
 * TEmailAddress class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Xml\TXmlElement;
use Prado\Xml\TXmlDocument;

/**
 * TEmailAddress class
 *
 * This is a utility container class for email addresses.  It can convert
 * a name with email into a string and parse a string into a name and email.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailAddress
{
	protected $_name;
	protected $_email;

	public function __construct($address = null, $name = null)
	{
		if ($name != null) {
			$this->_email = $address;
			$this->_name = $name;
		} else {
			$this->parse($address);
		}
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($name)
	{
		$this->_name = $name;
	}

	public function getEmail()
	{
		return $this->_email;
	}

	public function setEmail($email)
	{
		$this->_email = $email;
	}

	public function parse($address)
	{
		if (is_string($address)) {
			if (preg_match('/^(?:\s*("?)(.*?)\1\s*<)?\s*(.*?)\s*>?\s*$/', $address, $matches)) {
				$this->_name = $matches[2] ?? null;
				$this->_email = $matches[3];
			} else {
				$this->_email = $address;
			}
		} elseif (is_array($address)) {
			[$this->_email, $this->_name] = $address;
		}
	}

	public function format()
	{
		return ($this->_name ? '"' . $this->_name . '" ' : '') . '<' . $this->_email . '>';
	}

	public function __toString()
	{
		return $this->format();
	}
}
/*
johndoe@example.com
Name: nil
Email: johndoe@example.com

John <johndoe@example.com>
Name: John
Email: johndoe@example.com

John Doe <johndoe@example.com>
Name: John Doe
Email: johndoe@example.com

"John Doe" <johndoe@example.com>
Name: John Doe
Email: johndoe@example.com
*/
