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
 * Usage:
 * <service id="rpc" class="TRpcService">
 *     <rpcapi id="myapi" Class="MyApi" />
 * </service>
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
	 * @param string $methodName
	 * @param array $methodDetails
	 */
	public function addRpcMethod($methodName, $methodDetails)
	{
		$this->handler->addMethod($methodName, $methodDetails);
	}

	/**
	 * @return string request payload
	 */
	public function getPayload()
	{
		return file_get_contents('php://input');
	}

	/**
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

	// abstracts

	abstract public function registerMethods();

	// methods

	public function __construct(TRpcServer $rpcServer)
	{
		$this->rpcServer = $rpcServer;

		foreach($this->registerMethods() as $_methodName => $_methodDetails)
			$this->rpcServer->addRpcMethod($_methodName, $_methodDetails);
	}

	public function processRequest()
	{
		return $this->rpcServer->processRequest();
	}

	// getter/setter

	public function getRpcServer()
	{
		return $this->rpcServer;
	}
}

/**
 * TRpcProtocol class
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package System.Web.Services
 * @since 3.2
 **/
abstract class TRpcProtocol
{
	/**
	 * @var array containis the mapping from RPC method names to the actual handlers
	 */
	protected $rpcMethods = array();

	// abstracts

	abstract public function callMethod($requestPayload);
	abstract public function createErrorResponse(TRpcException $exception);
	abstract public function createResponseHeaders($response);
	abstract public function encode($data);
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
 * Implements the JSON RPC protocol
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
 * Implements the XML RPC protocol
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
