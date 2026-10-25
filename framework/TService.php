<?php

/**
 * TService class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TService class.
 *
 * TService implements the basic methods required by IService and may be
 * used as the basic class for application services.
 *
 * {@see setEnableCsrfValidation EnableCsrfValidation} has a service refuse a POST,
 * PUT, PATCH, or DELETE request without the CSRF token of
 * {@see \Prado\Web\THttpRequest::getCsrfToken()}. It is off by default. A service
 * calls {@see ensureCsrfToken()} at the start of {@see run()}; the built-in
 * {@see \Prado\Web\Services\TJsonService}, {@see \Prado\Web\Services\TSoapService},
 * and {@see \Prado\Web\Services\TFeedService} do. {@see \Prado\Web\Services\TPageService}
 * leaves the check to {@see \Prado\Web\UI\TPage::setEnableCsrfValidation()}, which is
 * on by default.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method void dyInit(mixed $config)
 */
abstract class TService extends \Prado\TApplicationComponent implements IService
{
	/**
	 * @var string service id
	 */
	private $_id;
	/**
	 * @var bool whether the service is enabled
	 */
	private $_enabled = true;
	/**
	 * @var bool whether a state-changing request must carry a valid CSRF token
	 * @since 4.4.0
	 */
	private $_enableCsrfValidation = false;


	/**
	 * Returns the active service instance if it is an instance of the called class,
	 * or `null` otherwise (wrong service type, no service started, or no application).
	 *
	 * Because the return type is `static`, calling `MyService::getInstance()` only
	 * returns a `MyService`; calling `TService::getInstance()` matches any `TService`
	 * subclass. Services are available from the `onInitComplete` application event
	 * onwards:
	 *
	 * ```php
	 * $app->onInitComplete[] = function () {
	 *		TPageService::getInstance()?->onPreRunPage[] = function(TPageService $sender, TPage $page): mixed {
	 *			$page->onLoad[] = [$this, 'pageHandlerInModule'];
	 *		};
	 * };
	 * ```
	 *
	 * @param ?\Prado\TApplication $app defaults to {@see \Prado\Prado::getApplication()}
	 * @return ?static the active service instance, or `null`
	 * @since 4.3.3
	 */
	public static function getInstance(?\Prado\TApplication $app = null): ?static
	{
		$app ??= Prado::getApplication();
		$service = $app?->getService();
		return ($service instanceof static) ? $service : null;
	}

	/**
	 * Initializes the service and attaches {@see run} to the RunService event of application.
	 * This method is required by IService and is invoked by application.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$this->dyInit($config);
	}

	/**
	 * @return string id of this service
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value id of this service
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}

	/**
	 * @return bool whether the service is enabled
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param bool $value whether the service is enabled
	 */
	public function setEnabled($value)
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether a request other than GET, HEAD, or OPTIONS must carry a valid
	 *   CSRF token. Defaults to false.
	 * @since 4.4.0
	 */
	public function getEnableCsrfValidation(): bool
	{
		return $this->_enableCsrfValidation;
	}

	/**
	 * @param bool $value whether a request other than GET, HEAD, or OPTIONS must carry a
	 *   valid CSRF token.
	 * @since 4.4.0
	 */
	public function setEnableCsrfValidation($value)
	{
		$this->_enableCsrfValidation = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Refuses a request other than GET, HEAD, or OPTIONS without a valid CSRF token
	 * while {@see getEnableCsrfValidation() EnableCsrfValidation} is on.  The token is
	 * read by {@see \Prado\Web\THttpRequest::validateCsrfToken()}.  A service that changes
	 * state calls this at the start of {@see run()}.
	 * @throws \Prado\Exceptions\THttpException 400 when the token is missing or invalid.
	 * @since 4.4.0
	 */
	protected function ensureCsrfToken(): void
	{
		if (!$this->getEnableCsrfValidation()) {
			return;
		}
		$request = $this->getRequest();
		if (in_array($request->getRequestType(), ['GET', 'HEAD', 'OPTIONS'], true)) {
			return;
		}
		if (!$request->validateCsrfToken()) {
			throw new \Prado\Exceptions\THttpException(400, 'service_csrf_token_invalid', $this->getID());
		}
	}

	/**
	 * Runs the service.
	 */
	public function run()
	{
	}
}
