<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TXmlRpcClient class
 *
 * Note: When using setIsNotification(true), *every* following request is also
 * considered to be a notification until you use setIsNotification(false).
 *
 * Usage:
 * <pre>
 * $_rpcClient = new TXmlRpcClient('http://remotehost/rpcserver');
 * $_rpcClient->remoteMethod($param, $otherParam);
 * </pre>
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @package Prado\Util
 * @since 3.2
 */

class TXmlRpcClient extends TRpcClient
{
	// magics

	/**
	 * @param string $method RPC method name
	 * @param array $parameters RPC method parameters
	 * @throws TRpcClientRequestException if the client fails to connect to the server
	 * @throws TRpcClientResponseException if the response represents an RPC fault
	 * @return mixed RPC request result
	 */
	public function __call($method, $parameters)
	{
		// send request
		$_response = $this->performRequest($this->getServerUrl(), $this->encodeRequest($method, $parameters), 'text/xml');

		// skip response handling if the request was just a notification request
		if ($this->isNotification) {
			return true;
		}

		// decode response
		if (($_response = xmlrpc_decode($_response)) === null) {
			throw new TRpcClientResponseException('Empty response received');
		}

		// handle error response
		if (xmlrpc_is_fault($_response)) {
			throw new TRpcClientResponseException($_response['faultString'], $_response['faultCode']);
		}

		return $_response;
	}

	// methods

	/**
	 * @param string $method method name
	 * @param array $parameters method parameters
	 */
	public function encodeRequest($method, $parameters)
	{
		return xmlrpc_encode_request($method, $parameters);
	}

	/**
	 * Creates an instance of TXmlRpcClient
	 * @param mixed $type unused
	 * @param string $serverUrl url of the rpc server
	 * @param bool $isNotification whether the requests are considered to be notifications (completely ignoring the response) (default: false)
	 */
	public static function create($type, $serverUrl, $isNotification = false)
	{
		return new self($serverUrl, $isNotification);
	}
}
