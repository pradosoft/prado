<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Exceptions\TApplicationException;
use Prado\TPropertyValue;

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
 * @package Prado\Util
 * @since 3.2
 */

class TRpcClient extends \Prado\TApplicationComponent
{
	/**
	 * @var string url of the RPC server
	 */
	private $_serverUrl;

	/**
	 * @var bool whether the request is a notification and therefore should not care about the result (default: false)
	 */
	private $_isNotification = false;

	// magics

	/**
	 * @param string $serverUrl url to RPC server
	 * @param bool $isNotification whether requests are considered to be notifications (completely ignoring the response) (default: false)
	 */
	public function __construct($serverUrl, $isNotification = false)
	{
		$this->_serverUrl = $serverUrl;
		$this->_isNotification = TPropertyValue::ensureBoolean($isNotification);
	}

	// methods

	/**
	 * Creates an instance of the requested RPC client type
	 * @param mixed $type
	 * @param mixed $serverUrl
	 * @param mixed $isNotification
	 * @throws TApplicationException if an unsupported RPC client type was specified
	 * @return TRpcClient instance
	 */
	public static function create($type, $serverUrl, $isNotification = false)
	{
		if (($_handler = constant('TRpcClientTypesEnumerable::' . strtoupper($type))) === null) {
			throw new TApplicationException('rpcclient_unsupported_handler');
		}

		return new $_handler($serverUrl, $isNotification);
	}

	/**
	 * Creates a stream context resource
	 * @param mixed $content
	 * @param string $contentType mime type
	 */
	protected function createStreamContext($content, $contentType)
	{
		return stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: {$contentType}",
				'content' => $content
			]
		]);
	}

	/**
	 * Performs the actual request
	 * @param string $serverUrl RPC server URL
	 * @param array $payload payload data
	 * @param string $mimeType request mime type
	 */
	protected function performRequest($serverUrl, $payload, $mimeType)
	{
		if (($_response = @file_get_contents($serverUrl, false, $this->createStreamContext($payload, $mimeType))) === false) {
			throw new TRpcClientRequestException('Request failed ("' . $http_response_header[0] . '")');
		}

		return $_response;
	}

	// getter/setter

	/**
	 * @return bool whether requests are considered to be notifications (completely ignoring the response)
	 */
	public function getIsNotification()
	{
		return $this->_isNotification;
	}

	/**
	 * @param string $bool boolean whether the requests are considered to be notifications (completely ignoring the response) (default: false)
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
	 * @param string $value url of the RPC server
	 */
	public function setServerUrl($value)
	{
		$this->_serverUrl = $value;
	}
}
