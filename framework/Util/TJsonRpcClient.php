<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link http://www.pradosoft.com/
 * @copyright 2010 Bigpoint GmbH
 * @license http://www.pradosoft.com/license/
 * @since 3.2
 * @package System.Util
 */

/**
 * TJsonRpcClient class
 *
 * Note: When using setIsNotification(true), *every* following request is also
 * considered to be a notification until you use setIsNotification(false).
 *
 * Usage:
 * <pre>
 * $_rpcClient = new TJsonRpcClient('http://host/server');
 * $_result = $_rpcClient->remoteMethod($param, $otherParam);
 * // or
 * $_result = TJsonRpcClient::create('http://host/server')->remoteMethod($param, $otherParam);
 * </pre>
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Util
 * @since 3.2
 */

class TJsonRpcClient extends TRpcClient
{
	// magics

	/**
	 * @param string RPC method name
	 * @param array RPC method parameters
	 * @return mixed RPC request result
	 * @throws TRpcClientRequestException if the client fails to connect to the server
	 * @throws TRpcClientResponseException if the response represents an RPC fault
	 */
	public function __call($method, $parameters)
	{
		// send request
		$_response = $this->performRequest($this->getServerUrl(), $this->encodeRequest($method, $parameters), 'application/json');

		// skip response handling if the request was just a notification request
		if($this->isNotification)
			return true;

		// decode response
		if(($_response = json_decode($_response, true)) === null)
			throw new TRpcClientResponseException('Empty response received');

		// handle error response
		if(!is_null($_response['error']))
			throw new TRpcClientResponseException($_response['error']);

		return $_response['result'];
	}

	// methods

	/**
	 * @param string method name
	 * @param array method parameters
	 */
	public function encodeRequest($method, $parameters)
	{
		static $_requestId;
		$_requestId = ($_requestId === null) ? 1 : $_requestId + 1;

		return json_encode(array(
			'method' => $method,
			'params' => $parameters,
			'id' => $this->isNotification ? null : $_requestId
		));
	}

	/**
	 * Creates an instance of TJsonRpcClient
	 * @param string url of the rpc server
	 * @param boolean whether the requests are considered to be notifications (completely ignoring the response) (default: false)
	 */
	public static function create($type, $serverUrl, $isNotification = false)
	{
		return new self($serverUrl, $isNotification);
	}
}