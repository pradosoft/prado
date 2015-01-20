<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link http://www.pradosoft.com/
 * @copyright 2010 Bigpoint GmbH
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.2
 * @package System.Web.Services
 */

/**
 * TRpcProtocol class
 *
 * TRpcProtocol is the base class used to implement a protocol in a {@link TRpcService}.
 * Prado already implements two protocols: {@link TXmlRpcProtocol} for Xml-Rpc request
 * and {@link TJsonRpcProtocol} for JSON-Rpc requests.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 **/
abstract class TRpcProtocol
{
	/**
	 * @var array containing the mapping from RPC method names to the actual handlers
	 */
	protected $rpcMethods = array();

	// abstracts

	/**
	 * @param string request payload
	 * Processed the request ans returns the response, if any
	 * @return processed response
	 * @abstract
	 */
	abstract public function callMethod($requestPayload);
	/**
	 * @param TRpcException the exception with error details
	 * Creates a proper response for an error condition
	 * @return a response representing the error
	 * @abstract
	 */
	abstract public function createErrorResponse(TRpcException $exception);
	/**
	 * @param response
	 * Sets the needed headers for the response (eg: content-type, charset)
	 * @abstract
	 */
	abstract public function createResponseHeaders($response);
	/**
	 * Encodes the response
	 * @param mixed reponse data
	 * @return string encoded response
	 * @abstract
	 */
	abstract public function encode($data);
	/**
	 * Decodes the request payload
	 * @param string request payload
	 * @return mixed decoded request
	 * @abstract
	 */
	abstract public function decode($data);

	// methods

	/**
	 * Registers a new RPC method and handler details
	 * @param string $methodName
	 * @param array $handlerDetails containing the callback handler
	 */
	public function addMethod($methodName, $handlerDetails)
	{
		$this->rpcMethods[$methodName] = $handlerDetails;
	}

	/**
	 * Calls the callback handler for the given method
	 * @param string $methodName of the RPC
	 * @param array $parameters for the callback handler as provided by the client
	 * @return mixed whatever the callback handler returns
	 */
	public function callApiMethod($methodName, $parameters)
	{
		if(!isset($this->rpcMethods[$methodName]))
			throw new TRpcException('Method "'.$methodName.'" not found');

		if($parameters === null)
			$parameters = array();

		if(!is_array($parameters))
			$parameters = array($parameters);
		return call_user_func_array($this->rpcMethods[$methodName]['method'], $parameters);
	}
}