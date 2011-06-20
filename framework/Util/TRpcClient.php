<?php

/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link http://www.pradosoft.com/
 * @copyright 2010 Bigpoint GmbH
 * @license http://www.pradosoft.com/license/
 * @version $Id: TRpcClient.php 137 2010-03-27 22:13:36Z rrogge $
 * @since 3.2
 * @package System.Util
 */

/**
 * TRpcClient class
 *
 * Note: When using setIsNotification(true), *every* following request is also
 * considered to be a notification until you use setIsNotification(false).
 *
 * Usage:
 *
 * First, you can use the factory:
 * <pre>
 * $_rpcClient = TRpcClient::create('xml', 'http://host/server');
 * $_result = $_rpcClient->remoteMethodName($param, $otherParam);
 * </pre>
 *
 * or as oneliner:
 * <pre>
 * $_result = TRpcClient::create('json', 'http://host/server')->remoteMethod($param, ...);
 * </pre>
 *
 * Second, you can also use the specific implementation directly:
 * <pre>
 * $_rpcClient = new TXmlRpcClient('http://host/server');
 * $_result = $_rpcClient->remoteMethod($param, ...);
 * </pre>
 *
 * or as oneliner:
 * <pre>
 * $_result = TXmlRpcClient('http://host/server')->hello();
 * </pre>
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Util
 * @since 3.2
 */

class TRpcClient extends TApplicationComponent
{
	/**
	 * @var string url of the RPC server
	 */
	private $_serverUrl;

	/**
	 * @var boolean whether the request is a notification and therefore should not care about the result (default: false)
	 */
	private $_isNotification = false;

	// magics

	/**
	 * @param string url to RPC server
	 * @param boolean whether requests are considered to be notifications (completely ignoring the response) (default: false)
	 */
	public function __construct($serverUrl, $isNotification = false)
	{
		$this->_serverUrl = $serverUrl;
		$this->_isNotification = TPropertyValue::ensureBoolean($isNotification);
	}

	// methods

	/**
	 * Creates an instance of the requested RPC client type
	 * @return TRpcClient instance
	 * @throws TApplicationException if an unsupported RPC client type was specified
	 */
	public static function create($type, $serverUrl, $isNotification = false)
	{
		if(($_handler = constant('TRpcClientTypesEnumerable::'.strtoupper($type))) === null)
			throw new TApplicationException('rpcclient_unsupported_handler');

		return new $_handler($serverUrl, $isNotification);
	}

	/**
	 * Creates a stream context resource
	 * @param mixed $content
	 * @param string $contentType mime type
	 */
	protected function createStreamContext($content, $contentType)
	{
		return stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-Type: {$contentType}",
				'content' => $content
			)
		));
	}

	/**
	 * Performs the actual request
	 * @param string RPC server URL
	 * @param array payload data
	 * @param string request mime type
	 */
	protected function performRequest($serverUrl, $payload, $mimeType)
	{
		if(($_response = @file_get_contents($serverUrl, false, $this->createStreamContext($payload, $mimeType))) === false)
			throw new TRpcClientRequestException('Request failed ("'.$http_response_header[0].'")');

		return $_response;
	}

	// getter/setter

	/**
	 * @return boolean whether requests are considered to be notifications (completely ignoring the response)
	 */
	public function getIsNotification()
	{
		return $this->_isNotification;
	}

	/**
	 * @param string boolean whether the requests are considered to be notifications (completely ignoring the response) (default: false)
	 */
	public function setIsNotification($bool)
	{
		$this->_isNotification = TPropertyValue::ensureBoolean($bool);
	}

	/**
	 * @return string url of the RPC server
	 */
	public function getServerUrl()
	{
		return $this->_serverUrl;
	}

	/**
	 * @param string url of the RPC server
	 */
	public function setServerUrl($value)
	{
		$this->_serverUrl = $value;
	}
}

/**
 * TRpcClientTypesEnumerable class
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Util
 * @since 3.2
 */
 
class TRpcClientTypesEnumerable extends TEnumerable
{
	const JSON = 'TJsonRpcClient';
	const XML = 'TXmlRpcClient';
}

/**
 * TRpcClientRequestException class
 *
 * This Exception is fired if the RPC request fails because of transport problems e.g. when
 * there is no RPC server responding on the given remote host.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Util
 * @since 3.2
 */
 
class TRpcClientRequestException extends TApplicationException
{
}

/**
 * TRpcClientResponseException class
 *
 * This Exception is fired when the 
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Util
 * @since 3.2
 */

class TRpcClientResponseException extends TApplicationException
{
	/**
	 * @param string error message
	 * @param integer error code (optional)
	 */
	public function __construct($errorMessage, $errorCode = null)
	{
		$this->setErrorCode($errorCode);

		parent::__construct($errorMessage);
	}
}

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
 * @version $Id$
 * @package System.Util
 * @since 3.2
 */

class TXmlRpcClient extends TRpcClient
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
		$_response = $this->performRequest($this->getServerUrl(), $this->encodeRequest($method, $parameters), 'text/xml');

		// skip response handling if the request was just a notification request
		if($this->isNotification)
			return true;
	
		// decode response
		if(($_response = xmlrpc_decode($_response)) === null)
			throw new TRpcClientResponseException('Empty response received');

		// handle error response
		if(xmlrpc_is_fault($_response))
			throw new TRpcClientResponseException($_response['faultString'], $_response['faultCode']);

		return $_response;
	}

	// methods

	/**
	 * @param string method name
	 * @param array method parameters
	 */
	public function encodeRequest($method, $parameters)
	{
		return xmlrpc_encode_request($method, $parameters);
	}

	/**
	 * Creates an instance of TXmlRpcClient
	 * @param string url of the rpc server
	 * @param boolean whether the requests are considered to be notifications (completely ignoring the response) (default: false)
	 */
	public static function create($type, $serverUrl, $isNotification = false)
	{
		return new self($serverUrl, $isNotification);
	}
}
