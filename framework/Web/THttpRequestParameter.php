<?php

/**
 * THttpRequestParameter classes
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * THttpRequestParameter class.
 *
 * This is the Event Parameter for {@see \Prado\Web\THttpRequest::onResolveRequest()} for encapsulating
 * the service IDs and URL parameters as part of the event parameter.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class THttpRequestParameter extends \Prado\TEventParameter
{
	/**
	 * @var array The service IDs associated with the request.
	 */
	private array $_serviceIDs;

	/**
	 * Constructor.
	 *
	 * @param array $serviceIDs The service IDs associated with the request.
	 * @param array $urlParams The URL parameters of the request.
	 */
	public function __construct(array $serviceIDs, array $urlParams)
	{
		$this->setServiceIDs($serviceIDs);
		parent::__construct($urlParams);
	}

	/**
	 * @return array The Service IDs available in the application.
	 */
	public function getServiceIDs(): array
	{
		return $this->_serviceIDs;
	}

	/**
	 * @param array $value The Service IDs available in the application.
	 * @return static $this
	 */
	public function setServiceIDs(array $value): static
	{
		$this->_serviceIDs = $value;

		return $this;
	}
}
