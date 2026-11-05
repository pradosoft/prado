<?php

/**
 * TDataGatewayCommand, TDataGatewayEventParameter and TDataGatewayResultEventParameter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\DataGateway;

/**
 * TDataGatewayEventParameter class
 *
 * TDataGatewayEventParameter class contains the TDbCommand to be executed as
 * well as the criteria object.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TDataGatewayEventParameter extends \Prado\TEventParameter
{
	private $_command;
	private $_criteria;

	public function __construct($command, $criteria)
	{
		$this->_command = $command;
		$this->_criteria = $criteria;
		parent::__construct();
	}

	/**
	 * The data command to be executed.  Do not rebind the parameters or change
	 * the query string.  The runtime type is the connection's command class —
	 * {@see \Prado\Data\TDbCommand} for SQL drivers — but handlers that need
	 * to stay driver-agnostic should type-hint the {@see \Prado\Data\IDataCommand}
	 * interface only.
	 * @return \Prado\Data\IDataCommand command to be executed.
	 */
	public function getCommand()
	{
		return $this->_command;
	}

	/**
	 * @return TSqlCriteria criteria used to bind the sql query parameters.
	 */
	public function getCriteria()
	{
		return $this->_criteria;
	}
}
