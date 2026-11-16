<?php

/**
 * TDbEmailService class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TDbEmailService class.
 *
 * TDbEmailService serves the web requests of the emails logged by {@see TDbEmailModule}.
 * The service parameter is the tracking ID of the email.  The first query parameter
 * present selects the request:
 *
 * | Parameter | Response |
 * |---|---|
 * | `open` | A 1x1 transparent GIF; counts an open. |
 * | `click=N` | A redirect to tracked link N of the email; counts a click. Unknown links are 404. |
 * | `unsubscribe` | GET: a confirmation form. POST: unsubscribes and confirms. |
 * | `file=hash/name` | A published asset file. |
 * | none | The email, viewed in the browser. |
 *
 * A {@see TEmail::getSecure Secure} email is viewed only by its logged-in owner.
 * The viewed email is sent with a Content-Security-Policy that blocks scripts.
 * {@see TDbEmailModule} registers this service from its nested service element.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDbEmailService extends \Prado\TService
{
	/** The 1x1 transparent GIF returned for an open. */
	public const TRACKER_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

	/** @var null|string|TDbEmailModule the module, or its ID, that logs the emails. */
	private $_emailer;

	/** @var bool whether emails can be viewed in the browser. */
	private $_showEmails = true;

	/**
	 * Initializes the service.
	 * @param mixed $config the service configuration, unused.
	 */
	public function init($config)
	{
	}

	/**
	 * Serves the request selected by the query parameters.
	 * @throws THttpException 404 when the email, link, or file is unknown; 403 when a secure email is not the viewer's.
	 */
	public function run()
	{
		$request = $this->getRequest();
		$trackingId = (string) $request->getServiceParameter();
		$emailer = $this->getEmailerModule();
		if ($emailer === null) {
			throw new TConfigurationException('dbemailservice_no_dbemailmodule');
		}
		if ($request->contains('open')) {
			$emailer->recordOpen($trackingId);
			$this->sendTrackerImage();
		} elseif ($request->contains('click')) {
			if (($url = $emailer->recordClick($trackingId, $request->itemAt('click'))) === null) {
				throw new THttpException(404, 'dbemailservice_link_unknown', $trackingId);
			}
			$this->getResponse()->redirect($url);
		} elseif ($request->contains('unsubscribe')) {
			$this->runUnsubscribe($trackingId);
		} elseif ($request->contains('file')) {
			$this->sendAssetFile((string) $request->itemAt('file'));
		} elseif ($this->getShowEmails()) {
			$this->runView($trackingId);
		} else {
			throw new THttpException(404, 'dbemailservice_email_unknown', $trackingId);
		}
	}

	/**
	 * Writes the 1x1 transparent GIF with headers that prevent caching.
	 */
	protected function sendTrackerImage()
	{
		$response = $this->getResponse();
		$response->setContentType('image/gif');
		$response->appendHeader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		$response->write(base64_decode(self::TRACKER_GIF));
	}

	/**
	 * Writes a published asset file.  The reference is "hash/name" within the asset path.
	 * @param string $fileRef the asset reference.
	 * @throws THttpException 404 when the reference is malformed or not a file in the asset path.
	 */
	protected function sendAssetFile(string $fileRef)
	{
		if (!preg_match('/^[\da-fA-F]{8}\/[^\/\\\\]+$/', $fileRef)) {
			throw new THttpException(404, 'dbemailservice_file_unknown', $fileRef);
		}
		$assetsPath = realpath($this->getApplication()->getAssetManager()->getBasePath());
		$fullPath = realpath($assetsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileRef));
		if ($assetsPath === false || $fullPath === false || !str_starts_with($fullPath, $assetsPath . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
			throw new THttpException(404, 'dbemailservice_file_unknown', $fileRef);
		}
		$this->getResponse()->writeFile($fullPath, null, null, null, false);
	}

	/**
	 * Shows the email in the browser.
	 * @param string $trackingId the tracking ID of the email.
	 * @throws THttpException 404 when the email is unknown; 403 when a secure email is not the viewer's.
	 */
	protected function runView(string $trackingId)
	{
		$row = $this->getAuthorizedEmail($trackingId);
		$email = $this->getEmailerModule()->createEmailFromRow($row);
		$response = $this->getResponse();
		$response->appendHeader("Content-Security-Policy: script-src 'none'; object-src 'none'; frame-ancestors 'self'");
		$response->appendHeader('Cache-Control: private, no-store');
		if (($body = $email->getBody()) !== null && $body !== '') {
			$response->setContentType('text/html');
			$response->write($body);
		} else {
			$this->writePage((string) $email->getSubject(), '<pre>' . THttpUtility::htmlEncode((string) $email->getTextBody()) . '</pre>');
		}
	}

	/**
	 * Confirms an unsubscribe on GET and unsubscribes on POST.  Mail clients send the
	 * RFC 8058 one-click POST with the body "List-Unsubscribe=One-Click".
	 * @param string $trackingId the tracking ID of the email.
	 * @throws THttpException 404 when the email is unknown.
	 */
	protected function runUnsubscribe(string $trackingId)
	{
		$emailer = $this->getEmailerModule();
		if ($this->getRequest()->getRequestType() === 'POST') {
			if ($emailer->recordUnsubscribe($trackingId) === null) {
				throw new THttpException(404, 'dbemailservice_email_unknown', $trackingId);
			}
			$this->writePage('Unsubscribed', '<h1>Unsubscribed</h1><p>You will no longer receive these emails.</p>');
			return;
		}
		if ($emailer->getEmailByTrackingId($trackingId) === null) {
			throw new THttpException(404, 'dbemailservice_email_unknown', $trackingId);
		}
		$action = THttpUtility::htmlEncode($emailer->getTrackingUrl($trackingId, ['unsubscribe' => 1]));
		$this->writePage('Unsubscribe', '<h1>Unsubscribe</h1>'
			. '<form method="post" action="' . $action . '">'
			. '<input type="hidden" name="List-Unsubscribe" value="One-Click">'
			. '<p>Stop receiving these emails?</p>'
			. '<button type="submit">Unsubscribe</button>'
			. '</form>');
	}

	/**
	 * Writes a minimal accessible HTML page.
	 * @param string $title the page title, plain text.
	 * @param string $content the page body, HTML.
	 */
	protected function writePage(string $title, string $content)
	{
		$response = $this->getResponse();
		$response->setContentType('text/html');
		$response->appendHeader('Cache-Control: private, no-store');
		$lang = THttpUtility::htmlEncode($this->getApplication()->getGlobalization(false)?->getCulture() ?: 'en');
		$response->write('<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1">'
			. '<title>' . THttpUtility::htmlEncode($title) . '</title></head><body><main>' . $content . '</main></body></html>');
	}

	/**
	 * Finds the email and checks that the viewer may see it.
	 * @param string $trackingId the tracking ID of the email.
	 * @throws THttpException 404 when the email is unknown; 403 when a secure email is not the viewer's.
	 * @return array the logged email row.
	 */
	protected function getAuthorizedEmail(string $trackingId): array
	{
		if (!($row = $this->getEmailerModule()->getEmailByTrackingId($trackingId))) {
			throw new THttpException(404, 'dbemailservice_email_unknown', $trackingId);
		}
		if ($row['secure']) {
			$user = $this->getApplication()->getUser();
			if (!$user || $user->getIsGuest() || $row['username'] === null || $user->getName() !== $row['username']) {
				throw new THttpException(403, 'dbemailservice_email_forbidden', $trackingId);
			}
		}
		return $row;
	}

	/**
	 * @throws TConfigurationException when the module is not a TDbEmailModule.
	 * @return ?TDbEmailModule the module that logs the emails.
	 */
	public function getEmailerModule()
	{
		if (is_string($this->_emailer)) {
			$module = $this->getApplication()->getModule($this->_emailer);
			if (!($module instanceof TDbEmailModule)) {
				throw new TConfigurationException('dbemailservice_no_dbemailmodule');
			}
			$this->_emailer = $module;
		}
		return $this->_emailer;
	}

	/**
	 * @param null|string|TDbEmailModule $emailer the module, or its ID, that logs the emails.
	 */
	public function setEmailerModule($emailer)
	{
		$this->_emailer = $emailer;
	}

	/**
	 * @return bool whether emails can be viewed in the browser. Defaults to true.
	 */
	public function getShowEmails()
	{
		return $this->_showEmails;
	}

	/**
	 * @param bool $value whether emails can be viewed in the browser.
	 */
	public function setShowEmails($value)
	{
		$this->_showEmails = TPropertyValue::ensureBoolean($value);
	}
}
