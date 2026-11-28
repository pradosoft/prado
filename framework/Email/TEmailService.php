<?php

/**
 * TEmailService class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEmailService class.
 *
 * TEmailService serves the web requests that the emails of a {@see TEmailModule}
 * link to.  The service parameter is the tracking ID of the email, and the first
 * of these query parameters selects the request:
 *
 * | Query | Request |
 * |---|---|
 * | `open` | Counts an open and returns a 1x1 transparent GIF. |
 * | `click=N` | Counts a click and redirects to tracked link N of the email. |
 * | `unsubscribe` | GET asks for confirmation; POST unsubscribes, which is also the RFC 8058 one-click request of a mail client. |
 * | (none) | Shows the email in the browser, when {@see setShowEmails ShowEmails} is true. |
 *
 * The module registers the service from its nested `<service>` element; the
 * attributes of the element are properties of the service.
 *
 * The tracking ID is the credential of the requests.  A click redirects only to
 * an http or https link that the email registered, so the service cannot be used
 * to redirect to another site.  A {@see TEmail::getSecure Secure} email is shown
 * only to the logged-in user it belongs to.  A shown email is sandboxed by its
 * Content-Security-Policy: it runs no script and submits no form, and its
 * address is not sent to the sites it links to.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailService extends \Prado\TService
{
	/** The 1x1 transparent GIF of an open, base64 encoded. */
	public const TRACKER_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

	/** The form of a tracking ID. */
	protected const TRACKING_ID = '/^[A-Za-z0-9_\-]{16,64}$/';

	/** @var null|string|TEmailModule the email module or its ID. */
	private $_emailModule;

	/** @var bool whether emails are shown in the browser. */
	private bool $_showEmails = true;

	/**
	 * Initializes the service.
	 * @param mixed $config the service configuration.
	 */
	public function init($config)
	{
	}

	/**
	 * Serves the request that the query parameters select.
	 * @throws THttpException 404 when the email or the link is unknown; 403 when a secure email belongs to another user.
	 */
	public function run()
	{
		$request = $this->getRequest();
		$trackingId = (string) $request->getServiceParameter();
		$known = preg_match(self::TRACKING_ID, $trackingId) === 1;
		$module = $this->getEmailModule();
		if ($request->contains('open')) {
			if ($known) {
				$module->recordOpen($trackingId);
			}
			$this->sendTrackerImage();
		} elseif (!$known) {
			throw new THttpException(404, 'emailservice_email_unknown');
		} elseif ($request->contains('click')) {
			$link = $request->itemAt('click');
			$url = ctype_digit((string) $link) ? $module->recordClick($trackingId, (int) $link) : null;
			if ($url === null || !preg_match('/^https?:\/\//i', $url)) {
				throw new THttpException(404, 'emailservice_link_unknown');
			}
			$this->getResponse()->redirect($url);
		} elseif ($request->contains('unsubscribe')) {
			$this->runUnsubscribe($trackingId);
		} elseif ($this->getShowEmails()) {
			$this->runView($trackingId);
		} else {
			throw new THttpException(404, 'emailservice_email_unknown');
		}
	}

	/**
	 * Writes the 1x1 transparent GIF with headers that prevent caching.
	 */
	protected function sendTrackerImage(): void
	{
		$response = $this->getResponse();
		$response->setContentType('image/gif');
		$response->appendHeader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		$response->write(base64_decode(self::TRACKER_GIF));
	}

	/**
	 * Shows the email in the browser.
	 * @param string $trackingId the tracking ID of the email.
	 * @throws THttpException 404 when the email is unknown; 403 when a secure email belongs to another user.
	 */
	protected function runView(string $trackingId): void
	{
		$store = $this->getEmailModule()->getStore();
		if ($store === null || ($email = $store->getEmail($trackingId)) === null) {
			throw new THttpException(404, 'emailservice_email_unknown');
		}
		if ($email->getSecure()) {
			$user = $this->getApplication()->getUser();
			if ($user === null || $user->getIsGuest() || $email->getUsername() === null || $user->getName() !== $email->getUsername()) {
				throw new THttpException(403, 'emailservice_email_forbidden');
			}
		}
		$response = $this->getResponse();
		$response->appendHeader("Content-Security-Policy: sandbox; script-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
		$response->appendHeader('Referrer-Policy: no-referrer');
		$response->appendHeader('X-Content-Type-Options: nosniff');
		$response->appendHeader('X-Robots-Tag: noindex');
		$response->appendHeader('Cache-Control: private, no-store');
		if (($html = $email->getHtml()) !== null && $html !== '') {
			$response->setContentType('text/html');
			$response->write($html);
		} else {
			$this->writePage($email->getSubject(), '<pre>' . static::encode((string) $email->getText()) . '</pre>');
		}
	}

	/**
	 * Asks for confirmation on GET and unsubscribes on POST.
	 * @param string $trackingId the tracking ID of the email.
	 * @throws THttpException 404 when the email is unknown.
	 */
	protected function runUnsubscribe(string $trackingId): void
	{
		$module = $this->getEmailModule();
		if ($this->getRequest()->getRequestType() === 'POST') {
			if ($module->recordUnsubscribe($trackingId) === null) {
				throw new THttpException(404, 'emailservice_email_unknown');
			}
			$title = Prado::localize('Unsubscribed');
			$this->writePage($title, '<h1>' . static::encode($title) . '</h1><p>' . static::encode(Prado::localize('You will no longer receive these emails.')) . '</p>');
			return;
		}
		if ($module->getStore()?->getEmailInfo($trackingId) === null) {
			throw new THttpException(404, 'emailservice_email_unknown');
		}
		$title = Prado::localize('Unsubscribe');
		$this->writePage($title, '<h1>' . static::encode($title) . '</h1>'
			. '<form method="post" action="' . static::encode($module->getTrackingUrl($trackingId, ['unsubscribe' => 1])) . '">'
			. '<input type="hidden" name="List-Unsubscribe" value="One-Click">'
			. '<p>' . static::encode(Prado::localize('Stop receiving these emails?')) . '</p>'
			. '<button type="submit">' . static::encode($title) . '</button>'
			. '</form>');
	}

	/**
	 * Writes a small HTML page with a language, a title, and a main landmark.
	 * @param string $title the page title, plain text.
	 * @param string $content the page content, HTML.
	 */
	protected function writePage(string $title, string $content): void
	{
		$response = $this->getResponse();
		$response->setContentType('text/html');
		$response->appendHeader('Cache-Control: private, no-store');
		$response->appendHeader('Referrer-Policy: no-referrer');
		$response->appendHeader('X-Robots-Tag: noindex');
		$language = static::encode($this->getApplication()->getGlobalization(false)?->getCulture() ?: 'en');
		$response->write('<!DOCTYPE html><html lang="' . $language . '"><head><meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1">'
			. '<title>' . static::encode($title) . '</title></head><body><main>' . $content . '</main></body></html>');
	}

	/**
	 * @param string $text plain text.
	 * @return string the text as HTML, with "&", "<", ">", and both quotes encoded.
	 */
	protected static function encode(string $text): string
	{
		return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}

	/**
	 * @throws TConfigurationException when the EmailModule is not a TEmailModule or its ID.
	 * @return TEmailModule the email module.
	 */
	public function getEmailModule(): TEmailModule
	{
		$module = is_string($this->_emailModule) ? $this->getApplication()->getModule($this->_emailModule) : $this->_emailModule;
		if (!($module instanceof TEmailModule)) {
			throw new TConfigurationException('emailservice_module_required');
		}
		return $this->_emailModule = $module;
	}

	/**
	 * @param string|TEmailModule $value the email module or its ID.
	 */
	public function setEmailModule($value)
	{
		$this->_emailModule = $value;
	}

	/**
	 * @return bool whether emails are shown in the browser. Defaults to true.
	 */
	public function getShowEmails(): bool
	{
		return $this->_showEmails;
	}

	/**
	 * @param bool|string $value whether emails are shown in the browser.
	 */
	public function setShowEmails($value)
	{
		$this->_showEmails = TPropertyValue::ensureBoolean($value);
	}
}
