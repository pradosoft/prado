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
 * TRpcService class
 *
 * The TRpcService class is a generic class that can be extended and used to implement
 * rpc services using different servers and protocols.
 * 
 * A server is a {@link TModule} that must subclass {@link TRpcServer}: its role is
 * to be an intermediate, moving data between the service and the provider. The base
 * {@link TRpcServer} class should suit the most common needs, but can be sublassed for
 * logging and debugging purposes, or to filter and modify the request/response on the fly.
 * 
 * A protocol is a {@link TModule} that must subclass {@link TRpcProtocol}: its role is
 * to implement the protocol that exposes the rpc api. Prado already implements two
 * protocols: {@link TXmlRpcProtocol} for Xml-Rpc request and {@link TJsonRpcProtocol} for
 * JSON-Rpc requests.
 *
 * A provider is a {@link TModule} that must subclass {@link TRpcApiProvider}: its role is
 * to implement the methods that are available through the api. Each defined api must be
 * a sublass of the abstract class {@link TRpcApiProvider} and implement its methods.
 *
 * The flow of requests and reponses is the following:
 * Request <-> TRpcService <-> TRpcServer <-> TRpcProtocol <-> TRpcApiProvider <-> Response
 *
 * To define an rpc service, add the proper application configuration:
 *
 * <code>
 * <service id="rpc" class="System.Web.Services.TRpcService">
 * 	 <rpcapi id="customers" class="Application.Api.CustomersApi" />
 *   <modules>
 *     <!--  register any module needed by the service here -->
 *   </modules>
 * </service>
 * </code>
 *
 * An api can be registered adding a proper <rpcapi ..> definition inside the service
 * configuration. Each api definition must contain an id property and a class name
 * expressed in namespace format. When the service receives a request for that api,
 * the specified class will be instanciated in order to satisfy the request.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 **/
class TRpcService extends TService
{
	/**
	 * const string base api provider class which every API must extend
	 */
	const BASE_API_PROVIDER = 'TRpcApiProvider';

	/**
	 * const string base RPC server implementation
	 */
	const BASE_RPC_SERVER = 'TRpcServer';

	/**
	 * @var array containing mimetype to protocol handler mappings
	 */
	protected $protocolHandlers = array(
		'application/json' => 'TJsonRpcProtocol',
		'text/xml' => 'TXmlRpcProtocol'
	);

	/**
	 * @var array containing API provider and their configured properties
	 */
	protected $apiProviders = array();

	// methods

	/**
	 * Creates the API provider instance for the current request
	 * @param TRpcProtocol $protocolHandler instance
	 * @param string $providerId
	 */
	public function createApiProvider(TRpcProtocol $protocolHandler, $providerId)
	{
		$_properties = $this->apiProviders[$providerId];

		if(($_providerClass = $_properties->remove('class')) === null)
			throw new TConfigurationException('rpcservice_apiprovider_required');

		Prado::using($_providerClass);

		$_providerClassName = ($_pos = strrpos($_providerClass, '.')) !== false ? substr($_providerClass, $_pos + 1) : $_providerClass;
		if(!is_subclass_of($_providerClassName, self::BASE_API_PROVIDER))
			throw new TConfigurationException('rpcservice_apiprovider_invalid');

		if(($_rpcServerClass = $_properties->remove('server')) === null)
			$_rpcServerClass = self::BASE_RPC_SERVER;

		Prado::using($_rpcServerClass);

		$_rpcServerClassName = ($_pos = strrpos($_rpcServerClass, '.')) !== false ? substr($_rpcServerClass, $_pos + 1) : $_rpcServerClass;
		if($_rpcServerClassName!==self::BASE_RPC_SERVER && !is_subclass_of($_rpcServerClassName, self::BASE_RPC_SERVER))
			throw new TConfigurationException('rpcservice_rpcserver_invalid');

		$_apiProvider = new $_providerClassName(new $_rpcServerClassName($protocolHandler));
		$_apiProvider->setId($providerId);

		foreach($_properties as $_key => $_value)
			$_apiProvider->setSubProperty($_key, $_value);

		return $_apiProvider;
	}

	/**
	 * Initializes the service
	 * @param TXmlElement $config containing the module configuration
	 */
	public function init($config)
	{
		$this->loadConfig($config);
	}

	/**
	 * Loads the service configuration
	 * @param TXmlElement $xml configuration
	 */
	public function loadConfig(TXmlElement $xml)
	{
		foreach($xml->getElementsByTagName('rpcapi') as $_apiProviderXml)
		{
			$_properties = $_apiProviderXml->getAttributes();

			if(($_id = $_properties->remove('id')) === null || $_id == "")
				throw new TConfigurationException('rpcservice_apiproviderid_required');

			if(isset($this->apiProviders[$_id]))
				throw new TConfigurationException('rpcservice_apiproviderid_duplicated');

			$this->apiProviders[$_id] = $_properties;
		}
	}

	/**
	 * Runs the service
	 */
	public function run()
	{
		$_request = $this->getRequest();

		if(($_providerId = $_request->getServiceParameter()) == "")
			throw new THttpException(400, 'RPC API-Provider id required');
		
		if(($_method = $_request->getRequestType()) != 'POST')
			throw new THttpException(405, 'Invalid request method "'.$_method.'"!'); // TODO Exception muss "Allow POST" Header setzen

		if(($_mimeType = $_request->getContentType()) === null)
			throw new THttpException(406, 'Content-Type is missing!'); // TODO Exception muss gültige Content-Type werte zurück geben

		if(!in_array($_mimeType, array_keys($this->protocolHandlers)))
			throw new THttpException(406, 'Unsupported Content-Type!'); // TODO see previous

		$_protocolHandlerClass = $this->protocolHandlers[$_mimeType];
		$_protocolHandler = new $_protocolHandlerClass;

		if(($_result = $this->createApiProvider($_protocolHandler, $_providerId)->processRequest()) !== null)
		{
			$_response = $this->getResponse();
			$_protocolHandler->createResponseHeaders($_response);
			$_response->write($_result);
		}
	}
}

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

/**
 * TRpcException class
 *
 * A TRpcException represents a RPC fault i.e. an error that is caused by the input data
 * sent from the client.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 */
class TRpcException extends TException
{
	public function __construct($message, $errorCode = -1)
	{
		$this->setErrorCode($errorCode);

		parent::__construct($message);
	}
}

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
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 */
abstract class TRpcApiProvider extends TModule
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
	 */
	public function __construct(TRpcServer $rpcServer)
	{
		$this->rpcServer = $rpcServer;

		foreach($this->registerMethods() as $_methodName => $_methodDetails)
			$this->rpcServer->addRpcMethod($_methodName, $_methodDetails);
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
	 * @return rpc server instance
	 */
	public function getRpcServer()
	{
		return $this->rpcServer;
	}
}

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

/**
 * TJsonRpcProtocol class
 *
 * TJsonRpcProtocol is a class that implements JSON-Rpc protocol in {@link TRpcService}.
 * Both version 1.0 and 2.0 of the specification are implemented, and the server will try
 * to answer using the same version of the protocol used by the requesting client.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 */
class TJsonRpcProtocol extends TRpcProtocol
{
	protected $_id=null;
	protected $_specificationVersion=1.0;

	/**
	 * Handles the RPC request
	 * @param string $requestPayload
	 * @return string JSON RPC response
	 */
	public function callMethod($requestPayload)
	{
		try
		{
			$_request = $this->decode($requestPayload);

			if(isset($_request['jsonrpc']))
			{
				$this->_specificationVersion=$_request['jsonrpc'];
				if($this->_specificationVersion > 2.0)
					throw new TRpcException('Unsupported specification version', '-32600');
			}

			if(isset($_request['id']))
				$this->_id=$_request['id'];

			if(!isset($_request['method']))
					throw new TRpcException('Missing request method', '-32600');

			if(!isset($_request['params']))
				$parameters = array();
			else
				$parameters = $_request['params'];

			if(!is_array($parameters))
				$parameters = array($parameters);

			$ret = $this->callApiMethod($_request['method'], $parameters);
			// a request without an id is a notification that doesn't need a response
			if($this->_id !== null)
			{
				if($this->_specificationVersion==2.0)
				{
					return $this->encode(array(
						'jsonrpc' => '2.0',
						'id' => $this->_id,
						'result' => $ret
					));
				} else {
					return $this->encode(array(
						'id' => $this->_id,
						'result' => $this->callApiMethod($_request['method'], $_request['params']),
						'error' => null
					));
				}
			}
		}
		catch(TRpcException $e)
		{
			return $this->createErrorResponse($e);
		}
		catch(THttpException $e)
		{
			throw $e;
		}
		catch(Exception $e)
		{
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
		if($this->_specificationVersion==2.0)
		{
			return $this->encode(array(
				'id' => $this->_id,
				'result' => null,
				'error'=> array(
					'code' => $exception->getCode(),
					'message'=> $exception->getMessage(),
					'data' => null,
					)
			));
		} else {
			return $this->encode(array(
				'id' => $this->_id,
				'error'=> array(
					'code' => $exception->getCode(),
					'message'=> $exception->getMessage(),
					'data' => null,
					)
			));			
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
	 * @param string $data in JSON format
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
	 * @param mixed PHP data
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
		if($errnum != JSON_ERROR_NONE)
			throw new Exception("JSON error: $msg", $err);
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
		if(!isset($this->rpcMethods[$methodName]))
			throw new TRpcException('Method "'.$methodName.'" not found', '-32601');

		return call_user_func_array($this->rpcMethods[$methodName]['method'], $parameters);
	}
}

/**
 * TXmlRpcProtocol class
 *
 * TXmlRpcProtocol is a class that implements XML-Rpc protocol in {@link TRpcService}.
 * It's basically a wrapper to the xmlrpc_server_* family of php methods.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Web.Services
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
	 * @param array $handlerDetails containing the callback handler
	 */
	public function addMethod($methodName, $methodDetails)
	{
		parent::addMethod($methodName, $methodDetails);

		xmlrpc_server_register_method($this->_xmlrpcServer, $methodName, array($this, 'callApiMethod'));
	}

	// methods

	/**
	 * Handles the RPC request
	 * @param string $requestPayload
	 * @return string XML RPC response
	 */
	public function callMethod($requestPayload)
	{
		try
		{
			return xmlrpc_server_call_method($this->_xmlrpcServer, $requestPayload, null);
		}
		catch(TRpcException $e)
		{
			return $this->createErrorResponse($e);
		}
		catch(THttpException $e)
		{
			throw $e;
		}
		catch(Exception $e)
		{
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
		return $this->encode(array(
			'faultCode' => $exception->getCode(),
			'faultString' => $exception->getMessage()
		));
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
	 * @param string $data in XML format
	 * @return array PHP data
	 */
	public function decode($data)
	{
		return xmlrpc_decode($data);
	}

	/**
	 * Encodes PHP data into XML data
	 * @param mixed PHP data
	 * @return string XML encoded PHP data
	 */
	public function encode($data)
	{
		return xmlrpc_encode($data);
	}
}
