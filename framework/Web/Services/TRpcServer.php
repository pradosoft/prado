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
 * TRpcServer class
 *
 * TRpcServer is a class 
 *
 * TRpcServer is the base class used to creare a server to be used in conjunction with
 * {@link TRpcService}.
 * The role of TRpcServer is to be an intermediate, moving data between the service and
 * the provider. This base class should suit the most common needs, but can be sublassed for
 * logging and debugging purposes, or to filter and modify the request/response on the fly.
 * 
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 **/
class TRpcServer extends TModule
{
	/**
	 * @var TRpcProtocol instance
	 */
	protected $handler;

	/**
	 * Constructor
	 * @param TRpcProtocol $protocolHandler instance
	 */
	public function __construct(TRpcProtocol $protocolHandler)
	{
		$this->handler = $protocolHandler;
	}
	
	/**
	 * Registers the method in the protocol handler
	 * @param string $methodName
	 * @param array $methodDetails
	 */
	public function addRpcMethod($methodName, $methodDetails)
	{
		$this->handler->addMethod($methodName, $methodDetails);
	}

	/**
	 * Retrieves the request payload
	 * @return string request payload
	 */
	public function getPayload()
	{
		return file_get_contents('php://input');
	}

	/**
	 * Passes the request payload to the protocol handler and returns the result
	 * @return string rpc response
	 */
	public function processRequest()
	{
		try
		{
			return $this->handler->callMethod($this->getPayload());
		}
		catch(TRpcException $e)
		{
			return $this->handler->createErrorResponse($e);
		}
	}
}