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
 * TJsonRpcProtocol class
 *
 * TJsonRpcProtocol is a class that implements JSON-Rpc protocol in {@link TRpcService}.
 * Both version 1.0 and 2.0 of the specification are implemented, and the server will try
 * to answer using the same version of the protocol used by the requesting client.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package Prado\Web\Services
 * @since 3.2
 */
class TJsonRpcProtocol extends TRpcProtocol
{
	protected $_id;
	protected $_specificationVersion = 1.0;

	/**
	 * Handles the RPC request
	 * @param string $requestPayload $requestPayload
	 * @return string JSON RPC response
	 */
	public function callMethod($requestPayload)
	{
		try {
			$_request = $this->decode($requestPayload);

			if (isset($_request['jsonrpc'])) {
				$this->_specificationVersion = $_request['jsonrpc'];
				if ($this->_specificationVersion > 2.0) {
					throw new TRpcException('Unsupported specification version', '-32600');
				}
			}

			if (isset($_request['id'])) {
				$this->_id = $_request['id'];
			}

			if (!isset($_request['method'])) {
				throw new TRpcException('Missing request method', '-32600');
			}

			if (!isset($_request['params'])) {
				$parameters = [];
			} else {
				$parameters = $_request['params'];
			}

			if (!is_array($parameters)) {
				$parameters = [$parameters];
			}

			// a request without an id is a notification that doesn't need a response
			if ($this->_id !== null) {
				if ($this->_specificationVersion == 2.0) {
					return $this->encode([
						'jsonrpc' => '2.0',
						'id' => $this->_id,
						'result' => $this->callApiMethod($_request['method'], $parameters),
					]);
				} else {
					return $this->encode([
						'id' => $this->_id,
						'result' => $this->callApiMethod($_request['method'], $_request['params']),
						'error' => null
					]);
				}
			}
		} catch (TRpcException $e) {
			return $this->createErrorResponse($e);
		} catch (THttpException $e) {
			throw $e;
		} catch (\Exception $e) {
			return $this->createErrorResponse(new TRpcException('An internal error occured', '-32603'));
		}
	}

	/**
	 * Turns the given exception into an JSON RPC fault
	 * @param TRpcException $exception
	 * @return string JSON RPC fault
	 */
	public function createErrorResponse(TRpcException $exception)
	{
		if ($this->_specificationVersion == 2.0) {
			return $this->encode([
				'id' => $this->_id,
				'result' => null,
				'error' => [
					'code' => $exception->getCode(),
					'message' => $exception->getMessage(),
					'data' => null,
					]
			]);
		} else {
			return $this->encode([
				'id' => $this->_id,
				'error' => [
					'code' => $exception->getCode(),
					'message' => $exception->getMessage(),
					'data' => null,
					]
			]);
		}
	}

	/**
	 * Sets the correct response headers
	 * @param THttpResponse $response
	 */
	public function createResponseHeaders($response)
	{
		$response->setContentType('application/json');
		$response->setCharset('UTF-8');
	}

	/**
	 * Decodes JSON encoded data into PHP data
	 * @param string $data $data in JSON format
	 * @return array PHP data
	 */
	public function decode($data)
	{
		$s = json_decode($data, true);
		self::checkJsonError();
		return $s;
	}

	/**
	 * Encodes PHP data into JSON data
	 * @param mixed $data PHP data
	 * @return string JSON encoded PHP data
	 */
	public function encode($data)
	{
		$s = json_encode($data);
		self::checkJsonError();
		return $s;
	}

	private static function checkJsonError()
	{
		$errnum = json_last_error();
		if ($errnum != JSON_ERROR_NONE) {
			throw new \Exception("JSON error: $msg", $err);
		}
	}

	/**
	 * Calls the callback handler for the given method
	 * Overrides parent implementation to correctly handle error codes
	 * @param string $methodName of the RPC
	 * @param array $parameters for the callback handler as provided by the client
	 * @return mixed whatever the callback handler returns
	 */
	public function callApiMethod($methodName, $parameters)
	{
		if (!isset($this->rpcMethods[$methodName])) {
			throw new TRpcException('Method "' . $methodName . '" not found', '-32601');
		}

		return call_user_func_array($this->rpcMethods[$methodName]['method'], $parameters);
	}
}
