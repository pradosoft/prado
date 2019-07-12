<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

use Prado\Exceptions\THttpException;

/**
 * TXmlRpcProtocol class
 *
 * TXmlRpcProtocol is a class that implements XML-Rpc protocol in {@link TRpcService}.
 * It's basically a wrapper to the xmlrpc_server_* family of php methods.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @package Prado\Web\Services
 * @since 3.2
 */
class TXmlRpcProtocol extends TRpcProtocol
{
	/**
	 * @var XML RPC server resource
	 */
	private $_xmlrpcServer;

	// magics

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_xmlrpcServer = xmlrpc_server_create();
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		xmlrpc_server_destroy($this->_xmlrpcServer);
	}

	// methods

	/**
	 * Registers a new RPC method and handler details
	 * @param string $methodName
	 * @param array $methodDetails Details containing the callback handler
	 */
	public function addMethod($methodName, $methodDetails)
	{
		parent::addMethod($methodName, $methodDetails);

		xmlrpc_server_register_method($this->_xmlrpcServer, $methodName, [$this, 'callApiMethod']);
	}

	// methods

	/**
	 * Handles the RPC request
	 * @param string $requestPayload $requestPayload
	 * @return string XML RPC response
	 */
	public function callMethod($requestPayload)
	{
		try {
			return xmlrpc_server_call_method($this->_xmlrpcServer, $requestPayload, null);
		} catch (TRpcException $e) {
			return $this->createErrorResponse($e);
		} catch (THttpException $e) {
			throw $e;
		} catch (\Exception $e) {
			return $this->createErrorResponse(new TRpcException('An internal error occured'));
		}
	}

	/**
	 * Turns the given exception into an XML RPC fault
	 * @param TRpcException $exception
	 * @return string XML RPC fault
	 */
	public function createErrorResponse(TRpcException $exception)
	{
		return $this->encode([
			'faultCode' => $exception->getCode(),
			'faultString' => $exception->getMessage()
		]);
	}

	/**
	 * Sets the correct response headers
	 * @param THttpResponse $response
	 */
	public function createResponseHeaders($response)
	{
		$response->setContentType('text/xml');
		$response->setCharset('UTF-8');
	}

	/**
	 * Decodes XML encoded data into PHP data
	 * @param string $data $data in XML format
	 * @return array PHP data
	 */
	public function decode($data)
	{
		return xmlrpc_decode($data);
	}

	/**
	 * Encodes PHP data into XML data
	 * @param mixed $data PHP data
	 * @return string XML encoded PHP data
	 */
	public function encode($data)
	{
		return xmlrpc_encode($data);
	}
}
