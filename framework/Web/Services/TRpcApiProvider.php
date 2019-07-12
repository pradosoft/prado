<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

/**
 * TRpcApiProvider class
 *
 * TRpcApiProvider is an abstract class the can be subclasses in order to implement an
 * api for a {@link TRpcService}. A subclass of TRpcApiProvider must implement the
 * {@link registerMethods} method in order to declare the available methods, their
 * names and the associated callback.
 *
 * <code>
 * public function registerMethods()
 * {
 *   return array(
 *     'apiMethodName1' => array('method' => array($this, 'objectMethodName1')),
 *     'apiMethodName2' => array('method' => array('ClassName', 'staticMethodName')),
 *   );
 * }
 * </code>
 *
 * In this example, two api method have been defined. The first refers to an object
 * method that must be implemented in the same class, the second to a static method
 * implemented in a 'ClassName' class.
 * In both cases, the method implementation will receive the request parameters as its
 * method parameters. Since the number of received parameters depends on
 * external-supplied data, it's adviced to use php's func_get_args() funtion to
 * validate them.
 *
 * Providers must be registered in the service configuration in order to be available,
 * as explained in {@link TRpcService}'s documentation.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @package Prado\Web\Services
 * @since 3.2
 */
abstract class TRpcApiProvider extends \Prado\TModule
{
	/**
	 * @var TRpcServer instance
	 */
	protected $rpcServer;

	/**
	 * Must return an array of the available methods
	 * @abstract
	 */
	abstract public function registerMethods();

	/**
	 * Constructor: informs the rpc server of the registered methods
	 * @param TRpcServer $rpcServer
	 */
	public function __construct(TRpcServer $rpcServer)
	{
		$this->rpcServer = $rpcServer;

		foreach ($this->registerMethods() as $_methodName => $_methodDetails) {
			$this->rpcServer->addRpcMethod($_methodName, $_methodDetails);
		}
	}

	/**
	 * Processes the request using the server
	 * @return processed request
	 */
	public function processRequest()
	{
		return $this->rpcServer->processRequest();
	}

	/**
	 * @return TRpcServer rpc server instance
	 */
	public function getRpcServer()
	{
		return $this->rpcServer;
	}
}
