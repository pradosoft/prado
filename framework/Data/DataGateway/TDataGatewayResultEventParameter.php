<?php
/**
 * TDataGatewayCommand, TDataGatewayEventParameter and TDataGatewayResultEventParameter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\DataGateway
 */

namespace Prado\Data\DataGateway;

/**
 * TDataGatewayResultEventParameter contains the TDbCommand executed and the resulting
 * data returned from the database. The data can be changed by changing the
 * {@link setResult Result} property.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\DataGateway
 * @since 3.1
 */
class TDataGatewayResultEventParameter extends \Prado\TEventParameter
{
	private $_command;
	private $_result;

	public function __construct($command, $result)
	{
		$this->_command = $command;
		$this->_result = $result;
	}

	/**
	 * @return TDbCommand database command executed.
	 */
	public function getCommand()
	{
		return $this->_command;
	}

	/**
	 * @return mixed result returned from executing the command.
	 */
	public function getResult()
	{
		return $this->_result;
	}

	/**
	 * @param mixed $value change the result returned by the gateway.
	 */
	public function setResult($value)
	{
		$this->_result = $value;
	}
}
