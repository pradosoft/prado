<?php

/**
 * THttpHeader class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\TPropertyValue;

/**
 * THttpHeader class
 *
 * THttpHeader is a class representing a simple HTTP header
 * in the form of a Name and a textual Value.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.3.3
 */
class THttpHeader extends \Prado\TComponent
{
	/**
	 * @var THttpHeadersManager the headers manager instance
	 */
	private $_manager;
	/**
	 * @var string Name
	 */
	protected $_name;
	/**
	 * @var string Value
	 */
	protected $_value;

	/**
	 * Constructor.
	 * @param THttpHeadersManager $manager the headers manager instance
	 */
	public function __construct(THttpHeadersManager $manager)
	{
		$this->_manager = $manager;
		parent::__construct();
	}

	/**
	 * @return THttpHeadersManager the headers manager instance
	 */
	public function getManager()
	{
		return $this->_manager;
	}

	/**
	 * Initializes the header.
	 * @param array|\Prado\Xml\TXmlElement $config configuration for this module.
	 */
	public function init($config)
	{
	}

	/**
	 * Renders the header as a `Name: Value` string.
	 * @return string the header line
	 */
	public function __toString(): string
	{
		return $this->getName() . ': ' . $this->getValue();
	}

	/**
	 * @return string the textual name of the header.
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Sets the textual name of the header
	 * @param string $name the textual name
	 */
	public function setName($name)
	{
		$this->_name = TPropertyValue::ensureString($name);
	}

	/**
	 * @return string the textual value of the header.
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Sets the textual value of the header
	 * @param string $value the textual value
	 */
	public function setValue($value)
	{
		$this->_value = TPropertyValue::ensureString($value);
	}
}
