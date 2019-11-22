<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\Xml\TXmlElement;

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
 * <service id="rpc" class="Prado\Web\Services\TRpcService">
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
 * @package Prado\Web\Services
 * @since 3.2
 **/
class TRpcService extends \Prado\TService
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
	protected $protocolHandlers = [
		'application/json' => 'TJsonRpcProtocol',
		'text/xml' => 'TXmlRpcProtocol'
	];

	/**
	 * @var array containing API provider and their configured properties
	 */
	protected $apiProviders = [];

	// methods

	/**
	 * Creates the API provider instance for the current request
	 * @param TRpcProtocol $protocolHandler instance
	 * @param string $providerId
	 */
	public function createApiProvider(TRpcProtocol $protocolHandler, $providerId)
	{
		$_properties = $this->apiProviders[$providerId];

		if (($_providerClass = $_properties->remove('class')) === null) {
			throw new TConfigurationException('rpcservice_apiprovider_required');
		}

		Prado::using($_providerClass);

		$_providerClassName = ($_pos = strrpos($_providerClass, '.')) !== false ? substr($_providerClass, $_pos + 1) : $_providerClass;
		if (!is_subclass_of($_providerClassName, self::BASE_API_PROVIDER)) {
			throw new TConfigurationException('rpcservice_apiprovider_invalid');
		}

		if (($_rpcServerClass = $_properties->remove('server')) === null) {
			$_rpcServerClass = self::BASE_RPC_SERVER;
		}

		Prado::using($_rpcServerClass);

		$_rpcServerClassName = ($_pos = strrpos($_rpcServerClass, '.')) !== false ? substr($_rpcServerClass, $_pos + 1) : $_rpcServerClass;
		if ($_rpcServerClassName !== self::BASE_RPC_SERVER && !is_subclass_of($_rpcServerClassName, self::BASE_RPC_SERVER)) {
			throw new TConfigurationException('rpcservice_rpcserver_invalid');
		}

		$_apiProvider = new $_providerClassName(new $_rpcServerClassName($protocolHandler));
		$_apiProvider->setId($providerId);

		foreach ($_properties as $_key => $_value) {
			$_apiProvider->setSubProperty($_key, $_value);
		}

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
		foreach ($xml->getElementsByTagName('rpcapi') as $_apiProviderXml) {
			$_properties = $_apiProviderXml->getAttributes();

			if (($_id = $_properties->remove('id')) === null || $_id == "") {
				throw new TConfigurationException('rpcservice_apiproviderid_required');
			}

			if (isset($this->apiProviders[$_id])) {
				throw new TConfigurationException('rpcservice_apiproviderid_duplicated');
			}

			$this->apiProviders[$_id] = $_properties;
		}
	}

	/**
	 * Runs the service
	 */
	public function run()
	{
		$_request = $this->getRequest();

		if (($_providerId = $_request->getServiceParameter()) == "") {
			throw new THttpException(400, 'RPC API-Provider id required');
		}

		if (($_method = $_request->getRequestType()) != 'POST') {
			throw new THttpException(405, 'Invalid request method "' . $_method . '"!');
		} // TODO Exception muss "Allow POST" Header setzen

		if (($_mimeType = $_request->getContentType()) === null) {
			throw new THttpException(406, 'Content-Type is missing!');
		} // TODO Exception muss gültige Content-Type werte zurück geben

		if (!in_array($_mimeType, array_keys($this->protocolHandlers))) {
			throw new THttpException(406, 'Unsupported Content-Type!');
		} // TODO see previous

		$_protocolHandlerClass = $this->protocolHandlers[$_mimeType];
		$_protocolHandler = new $_protocolHandlerClass;

		if (($_result = $this->createApiProvider($_protocolHandler, $_providerId)->processRequest()) !== null) {
			$_response = $this->getResponse();
			$_protocolHandler->createResponseHeaders($_response);
			$_response->write($_result);
		}
	}
}
