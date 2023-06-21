<?php
/**
 * TRequestConnectionUpgrade class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Behaviors;

use Prado\Prado;

/**
 * TRequestConnectionUpgrade class.
 *
 * This is a behavior for {@see THttpRequest} that adds the "Connection: Upgrade"
 * header "Upgrade: <service>" to the URL Parameters of the request.
 *
 * For example, a URL parameter could be used to select the PRADO "websocket" service.
 * But this class allows selection of the "websocket" service from the injection of the
 * "Upgrade" HTTP headers into the URL parameters without the need for specifying
 * URL parameters.
 *
 * ```xml
 *		<behavior name="httpUpgrader" Class="Prado\Web\Behaviors\TRequestConnectionUpgrade" AttachToClass="Prado\Web\THttpRequest" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TRequestConnectionUpgrade extends \Prado\Util\TBehavior
{
	public function events()
	{
		return ['onResolveRequest' => 'processHeaders'];
	}

	/**
	 * Move the contents of the Upgrade header to the urlParams.
	 * The Service can select on the "Upgrade" HTTP header like "websocket".
	 * @param \Prado\Web\THttpRequest $request
	 * @param \Prado\Web\THttpRequestParameter $param
	 */
	public function processHeaders($request, $param)
	{
		$urlParams = $param->getParameter();
		if (!is_array($urlParams)) {
			return;
		}
		$headers = $request->getHeaders(CASE_LOWER);
		if (!isset($headers['connection'])) {
			return;
		}
		$connections = array_map('trim', explode(',', strtolower($headers['connection'])));
		if (!in_array('upgrade', $connections)) {
			return;
		}
		if (!isset($headers['upgrade'])) {
			Prado::log("'Connection: Upgrade' without 'Upgrade' Header from " . $_SERVER['REMOTE_ADDR'], \Prado\Util\TLogger::NOTICE, static::class);
			return;
		}
		$upgrade = $headers['upgrade'];
		if (is_array($upgrade)) {
			$upgrade = implode(',', $upgrade);
		}
		$requestedUpgrade = array_map('trim', explode(',', $upgrade));

		if ($requestedUpgrade) {
			$urlParams = array_merge(array_flip($requestedUpgrade), $urlParams);
			$param->setParameter($urlParams); // Forward results
			return $urlParams;	 // return for use
		}
	}
}
