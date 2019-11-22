<?php
/**
 * TJsonService and TJsonResponse class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

use Prado\TApplication;
use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TJsonService class provides to end-users javascript content response in
 * JSON format.
 *
 * TJsonService manages a set of {@link TJsonResponse}, each
 * representing specific response with javascript content.
 * The service parameter, referring to the ID of the service, specifies
 * which javascript content to be provided to end-users.
 *
 * To use TJsonService, configure it in application configuration as follows,
 * <code>
 *  <service id="json" class="Prado\Web\Services\TJsonService">
 *    <json id="get_article" class="Path\To\JsonResponseClass1" .../>
 *    <json id="register_rating" class="Path\To\JsonResponseClass2" .../>
 *  </service>
 * </code>
 * where each JSON response is specified via a &lt;json&gt; element.
 * Initial property values can be configured in a &lt;json&gt; element.
 *
 *
 * PHP configuration style:
 * <code>
 *  'services' => array(
 *    'get_article' => array(
 *     'class' => 'Path\To\JsonResponseClass1',
 *     'properties' => array(
 *       ...
 *	    )
 *    )
 *  )
 * </code>
 *
 * To retrieve the JSON content provided by "get_article", use the URL
 * <code>index.php?json=get_article</code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Web\Services
 * @since 3.1
 */
class TJsonService extends \Prado\TService
{
	/**
	 * @var array registered services
	 */
	private $_services = [];

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param mixed $xml configuration for this module, can be null
	 */
	public function init($xml)
	{
		$this->loadJsonServices($xml);
	}

	/**
	 * Load the service definitions.
	 * @param mixed $config configuration for this module, can be null
	 */
	protected function loadJsonServices($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			if (is_array($config)) {
				foreach ($config['json'] as $id => $json) {
					$this->_services[$id] = $json;
				}
			}
		} else {
			foreach ($config->getElementsByTagName('json') as $json) {
				if (($id = $json->getAttribute('id')) !== null) {
					$this->_services[$id] = $json;
				} else {
					throw new TConfigurationException('jsonservice_id_required');
				}
			}
		}
	}

	/**
	 * Runs the service.
	 * This method is invoked by application automatically.
	 */
	public function run()
	{
		$id = $this->getRequest()->getServiceParameter();
		if (isset($this->_services[$id])) {
			$serviceConfig = $this->_services[$id];
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				if (isset($serviceConfig['class'])) {
					$service = Prado::createComponent($serviceConfig['class']);
					if ($service instanceof TJsonResponse) {
						$properties = $serviceConfig['properties'] ?? [];
						$this->createJsonResponse($service, $properties, $serviceConfig);
					} else {
						throw new TConfigurationException('jsonservice_response_type_invalid', $id);
					}
				} else {
					throw new TConfigurationException('jsonservice_class_required', $id);
				}
			} else {
				$properties = $serviceConfig->getAttributes();
				if (($class = $properties->remove('class')) !== null) {
					$service = Prado::createComponent($class);
					if ($service instanceof TJsonResponse) {
						$this->createJsonResponse($service, $properties, $serviceConfig);
					} else {
						throw new TConfigurationException('jsonservice_response_type_invalid', $id);
					}
				} else {
					throw new TConfigurationException('jsonservice_class_required', $id);
				}
			}
		} else {
			throw new THttpException(404, 'jsonservice_provider_unknown', $id);
		}
	}

	/**
	 * Renders content provided by TJsonResponse::getJsonContent() as
	 * javascript in JSON format.
	 * @param mixed $service
	 * @param mixed $properties
	 * @param mixed $config
	 */
	protected function createJsonResponse($service, $properties, $config)
	{
		// init service properties
		foreach ($properties as $name => $value) {
			$service->setSubproperty($name, $value);
		}
		$service->init($config);

		//send content if not null
		if (($content = $service->getJsonContent()) !== null) {
			$response = $this->getResponse();
			$response->setContentType('application/json');
			$response->setCharset('UTF-8');
			//send content
			$response->write(TJavaScript::jsonEncode($content));
		}
	}
}
